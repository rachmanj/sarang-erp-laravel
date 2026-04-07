<?php

namespace App\Services;

use App\Models\BusinessPartner;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessPartnerAccountStatementService
{
    public function __construct(
        protected BusinessPartner $businessPartner
    ) {}

    /**
     * Account statement: one row per posted journal line(s) on trade AR/AP (or partner sub-account) tied to this partner.
     * Centralized posting uses Utang Dagang / Piutang Dagang, not necessarily business_partners.account_id.
     *
     * @return array{
     *     opening_balance: float,
     *     closing_balance: float,
     *     total_debits: float,
     *     total_credits: float,
     *     transactions: Collection,
     *     pagination: array{
     *         current_page: int,
     *         total_pages: int,
     *         total_records: int,
     *         per_page: int
     *     }
     * }
     */
    public function getStatement(?string $startDate = null, ?string $endDate = null, int $page = 1, int $perPage = 25): array
    {
        $tradeIds = $this->tradeControlAccountIds();
        if (count($tradeIds) === 0 && ! $this->businessPartner->account_id) {
            return $this->emptyPayload($perPage);
        }

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfYear();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfYear();

        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $openingBalance = $this->openingBalance($startStr);

        $periodTotals = $this->partnerJournalLinesBaseQuery()
            ->whereBetween('j.date', [$startStr, $endStr])
            ->selectRaw('COALESCE(SUM(jl.debit), 0) as total_debits, COALESCE(SUM(jl.credit), 0) as total_credits')
            ->first();

        $totalDebits = (float) ($periodTotals->total_debits ?? 0);
        $totalCredits = (float) ($periodTotals->total_credits ?? 0);
        $closingBalance = $openingBalance + $totalDebits - $totalCredits;

        $aggregated = $this->partnerJournalLinesBaseQuery()
            ->whereBetween('j.date', [$startStr, $endStr])
            ->leftJoin('users as u', 'j.posted_by', '=', 'u.id')
            ->groupBy([
                'j.id',
                'j.date',
                'j.created_at',
                'j.source_type',
                'j.source_id',
                'j.journal_no',
                'j.description',
                'u.name',
            ])
            ->orderBy('j.date')
            ->orderBy('j.id')
            ->select([
                'j.id as journal_id',
                'j.date as posting_date',
                'j.created_at as create_date',
                'j.date as document_date',
                'j.source_type',
                'j.source_id',
                'j.journal_no',
                'j.description',
                DB::raw('SUM(jl.debit) as debit'),
                DB::raw('SUM(jl.credit) as credit'),
                'u.name as created_by',
            ])
            ->get();

        $this->attachDocumentNumbers($aggregated);

        $runningBalance = $openingBalance;
        $withBalance = $aggregated->map(function ($row) use (&$runningBalance) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $runningBalance += $debit - $credit;
            $row->cumulative_balance = $runningBalance;

            return $row;
        });

        $totalRecords = $withBalance->count();
        $totalPages = $perPage > 0 ? (int) ceil($totalRecords / $perPage) : 0;
        $offset = ($page - 1) * $perPage;
        $pageRows = $withBalance->slice($offset, $perPage)->values();

        $transactions = $pageRows->map(function ($row) {
            return (object) [
                'posting_date' => $row->posting_date,
                'create_date' => $row->create_date,
                'document_date' => $row->document_date,
                'document_type' => $this->documentTypeLabel($row->source_type),
                'document_no' => $row->document_no,
                'journal_no' => $row->journal_no,
                'description' => $row->description,
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'cumulative_balance' => (float) $row->cumulative_balance,
                'created_by' => $row->created_by,
            ];
        });

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'transactions' => $transactions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Full statement for CSV/PDF export (same filters as the on-screen statement, no pagination cap in practice).
     *
     * @return array<string, mixed>
     */
    public function getExportData(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->getStatement($startDate, $endDate, 1, 100_000);
    }

    /**
     * AR/AP (and staging) control accounts used by posting — same codes as PurchaseInvoiceController / SalesInvoiceController.
     *
     * @return array<int, int>
     */
    protected function tradeControlAccountIds(): array
    {
        $codes = $this->businessPartner->is_supplier
            ? ['2.1.1.01', '2.1.1.03']
            : ['1.1.2.01', '1.1.2.04'];

        return DB::table('accounts')->whereIn('code', $codes)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    protected function partnerJournalLinesBaseQuery(): Builder
    {
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'jl.journal_id', '=', 'j.id');

        $this->applyPartnerStatementFilters($query);

        return $query;
    }

    protected function applyPartnerStatementFilters(Builder $query): void
    {
        $tradeIds = $this->tradeControlAccountIds();
        $partnerId = $this->businessPartner->id;

        $query->where(function (Builder $outer) use ($tradeIds, $partnerId) {
            if (count($tradeIds) > 0) {
                $outer->where(function (Builder $central) use ($tradeIds, $partnerId) {
                    $central->whereIn('jl.account_id', $tradeIds)
                        ->where(function (Builder $doc) use ($partnerId) {
                            $this->applyPartnerDocumentJournalConditions($doc, $partnerId);
                        });
                });
            }

            if ($this->businessPartner->account_id) {
                $outer->orWhere('jl.account_id', $this->businessPartner->account_id);
            }
        });
    }

    protected function applyPartnerDocumentJournalConditions(Builder $query, int $partnerId): void
    {
        $query->where(function (Builder $doc) use ($partnerId) {
            $doc->whereRaw('0 = 1');

            if ($this->businessPartner->is_supplier) {
                $doc->orWhere(function (Builder $s) use ($partnerId) {
                    $s->where(function (Builder $q) use ($partnerId) {
                        $q->where('j.source_type', 'purchase_invoice')
                            ->whereExists(function ($sub) use ($partnerId) {
                                $sub->select(DB::raw(1))
                                    ->from('purchase_invoices as pi')
                                    ->whereColumn('pi.id', 'j.source_id')
                                    ->where('pi.business_partner_id', $partnerId);
                            });
                    })->orWhere(function (Builder $q) use ($partnerId) {
                        $q->where('j.source_type', 'purchase_payment')
                            ->whereExists(function ($sub) use ($partnerId) {
                                $sub->select(DB::raw(1))
                                    ->from('purchase_payments as pp')
                                    ->whereColumn('pp.id', 'j.source_id')
                                    ->where('pp.business_partner_id', $partnerId);
                            });
                    });

                    if (DB::getSchemaBuilder()->hasTable('goods_receipt_po')) {
                        $s->orWhere(function (Builder $q) use ($partnerId) {
                            $q->where('j.source_type', 'goods_receipt_po')
                                ->whereExists(function ($sub) use ($partnerId) {
                                    $sub->select(DB::raw(1))
                                        ->from('goods_receipt_po as grpo')
                                        ->whereColumn('grpo.id', 'j.source_id')
                                        ->where('grpo.business_partner_id', $partnerId);
                                });
                        });
                    }
                });
            }

            if ($this->businessPartner->is_customer) {
                $doc->orWhere(function (Builder $c) use ($partnerId) {
                    $c->where(function (Builder $q) use ($partnerId) {
                        $q->where('j.source_type', 'sales_invoice')
                            ->whereExists(function ($sub) use ($partnerId) {
                                $sub->select(DB::raw(1))
                                    ->from('sales_invoices as si')
                                    ->whereColumn('si.id', 'j.source_id')
                                    ->where('si.business_partner_id', $partnerId);
                            });
                    })->orWhere(function (Builder $q) use ($partnerId) {
                        $q->where('j.source_type', 'sales_receipt')
                            ->whereExists(function ($sub) use ($partnerId) {
                                $sub->select(DB::raw(1))
                                    ->from('sales_receipts as sr')
                                    ->whereColumn('sr.id', 'j.source_id')
                                    ->where('sr.business_partner_id', $partnerId);
                            });
                    });
                });
            }
        });
    }

    protected function openingBalance(string $startDate): float
    {
        $row = $this->partnerJournalLinesBaseQuery()
            ->where('j.date', '<', $startDate)
            ->selectRaw('COALESCE(SUM(jl.debit), 0) - COALESCE(SUM(jl.credit), 0) as bal')
            ->first();

        return (float) ($row->bal ?? 0);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    protected function attachDocumentNumbers(Collection $rows): void
    {
        $byType = $rows->groupBy('source_type');

        foreach ($byType as $sourceType => $group) {
            $ids = $group->pluck('source_id')->filter()->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }

            $column = match ($sourceType) {
                'purchase_invoice' => 'invoice_no',
                'sales_invoice' => 'invoice_no',
                'purchase_payment' => 'payment_no',
                'sales_receipt' => 'receipt_no',
                'goods_receipt_po' => 'grn_no',
                'cash_expense' => 'expense_no',
                default => null,
            };

            $table = match ($sourceType) {
                'purchase_invoice' => 'purchase_invoices',
                'sales_invoice' => 'sales_invoices',
                'purchase_payment' => 'purchase_payments',
                'sales_receipt' => 'sales_receipts',
                'goods_receipt_po' => 'goods_receipt_po',
                'cash_expense' => 'cash_expenses',
                default => null,
            };

            if ($table === null || $column === null) {
                continue;
            }

            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $map = DB::table($table)->whereIn('id', $ids->all())->pluck($column, 'id');

            foreach ($group as $row) {
                if ($row->source_id && isset($map[$row->source_id])) {
                    $row->document_no = $map[$row->source_id];
                }
            }
        }

        foreach ($rows as $row) {
            if (! isset($row->document_no)) {
                $row->document_no = null;
            }
        }
    }

    protected function documentTypeLabel(?string $sourceType): string
    {
        if ($sourceType === null || $sourceType === '') {
            return 'Journal';
        }

        $slugMap = [
            'purchase_invoice' => 'Purchase Invoice',
            'purchase_payment' => 'Purchase Payment',
            'sales_invoice' => 'Sales Invoice',
            'sales_receipt' => 'Sales Receipt',
            'goods_receipt_po' => 'Goods Receipt PO',
            'manual_journal' => 'Manual Journal',
            'cash_expense' => 'Cash Expense',
            'currency_revaluation' => 'Currency Revaluation',
            'gr_gi' => 'GR/GI',
            'copy' => 'Journal Copy',
            'test' => 'Test Posting',
        ];

        if (isset($slugMap[$sourceType])) {
            return $slugMap[$sourceType];
        }

        if (str_contains($sourceType, '\\')) {
            $basename = class_basename($sourceType);

            $classMap = [
                'DeliveryOrder' => 'Delivery Order',
                'AssetDepreciationRun' => 'Asset Depreciation',
                'AssetDisposal' => 'Asset Disposal',
                'GoodsReceiptPO' => 'Goods Receipt PO',
            ];

            if (isset($classMap[$basename])) {
                return $classMap[$basename];
            }

            return Str::headline($basename);
        }

        return Str::headline(str_replace('_', ' ', $sourceType));
    }

    /**
     * @return array{
     *     opening_balance: float,
     *     closing_balance: float,
     *     total_debits: float,
     *     total_credits: float,
     *     transactions: Collection,
     *     pagination: array{
     *         current_page: int,
     *         total_pages: int,
     *         total_records: int,
     *         per_page: int
     *     }
     * }
     */
    protected function emptyPayload(int $perPage): array
    {
        return [
            'opening_balance' => 0,
            'closing_balance' => 0,
            'total_debits' => 0,
            'total_credits' => 0,
            'transactions' => collect(),
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 0,
                'total_records' => 0,
                'per_page' => $perPage,
            ],
        ];
    }
}
