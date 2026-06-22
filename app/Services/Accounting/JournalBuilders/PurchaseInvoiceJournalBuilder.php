<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\PurchaseInvoice;
use App\Services\Accounting\HeaderDiscountAllocation;
use App\Services\Accounting\PurchaseInvoiceLineTaxMath;
use App\Services\PurchaseInvoiceService;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceJournalBuilder
{
    public function __construct(
        private PurchaseInvoiceService $purchaseInvoiceService,
    ) {}

    public function build(PurchaseInvoice $invoice): JournalDraft
    {
        $invoice->loadMissing(['lines.inventoryItem']);

        if ($invoice->payment_method === 'cash' && $invoice->is_direct_purchase) {
            return $this->buildDirectCashPurchase($invoice);
        }

        return $this->buildCreditPurchase($invoice);
    }

    private function buildDirectCashPurchase(PurchaseInvoice $invoice): JournalDraft
    {
        $cashAccountId = $invoice->cash_account_id;
        if (! $cashAccountId) {
            $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        }
        $ppnInputId = (int) (DB::table('accounts')->where('code', '1.1.4.01')->value('id') ?? DB::table('accounts')->where('code', '1.1.6')->value('id') ?? 0);

        $totalAmount = 0.0;
        $ppnTotal = 0.0;
        $withholdingTotal = 0.0;
        $journalLines = [];
        $expenseByAccount = [];
        $dppByLineId = $this->scaledDppByPurchaseInvoiceLineId($invoice);

        foreach ($invoice->lines as $line) {
            $lineAmount = $dppByLineId[$line->id]
                ?? (($line->net_amount > 0) ? (float) $line->net_amount : (float) $line->amount);
            $totalAmount += $lineAmount;

            if ($invoice->is_opening_balance) {
                $accountId = (int) $line->account_id;
                if (! isset($expenseByAccount[$accountId])) {
                    $expenseByAccount[$accountId] = [
                        'amount' => 0,
                        'project_id' => $line->project_id,
                        'dept_id' => $line->dept_id,
                    ];
                }
                $expenseByAccount[$accountId]['amount'] += $lineAmount;
            }

            if (! empty($line->tax_code_id)) {
                $tax = DB::table('tax_codes')->where('id', $line->tax_code_id)->first();
                $ppnTotal += PurchaseInvoiceLineTaxMath::ppnAmount($lineAmount, $tax);
            }

            $wtaxRate = (float) ($line->wtax_rate ?? 0);
            $tax = ! empty($line->tax_code_id)
                ? DB::table('tax_codes')->where('id', $line->tax_code_id)->first()
                : null;
            $withholdingTotal += PurchaseInvoiceLineTaxMath::withholdingAmount($lineAmount, $tax, $wtaxRate);

            if (! $invoice->is_opening_balance) {
                $accountId = $line->account_id;
                if (empty($accountId) && $line->inventory_item_id && $line->inventoryItem) {
                    $accountId = $this->purchaseInvoiceService->getAccountIdForItem($line->inventoryItem);
                }
                if (empty($accountId)) {
                    throw new \Exception("Line {$line->id} missing account_id. Please set account or ensure inventory item has a product category with inventory account.");
                }
                $journalLines[] = [
                    'account_id' => $accountId,
                    'debit' => $lineAmount,
                    'credit' => 0,
                    'project_id' => $line->project_id,
                    'dept_id' => $line->dept_id,
                    'memo' => $line->description ?? 'Direct cash purchase',
                ];
            }
        }

        if ($invoice->is_opening_balance) {
            foreach ($expenseByAccount as $accountId => $data) {
                $journalLines[] = [
                    'account_id' => $accountId,
                    'debit' => $data['amount'],
                    'credit' => 0,
                    'project_id' => $data['project_id'],
                    'dept_id' => $data['dept_id'],
                    'memo' => 'Opening Balance - Direct Cash Purchase',
                ];
            }
        }

        if ($ppnTotal > 0) {
            if (! $ppnInputId) {
                throw new \Exception('PPN Input account (1.1.4.01 or 1.1.6) not found. Please create the account in Chart of Accounts.');
            }
            $journalLines[] = [
                'account_id' => $ppnInputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Masukan',
            ];
        }

        if ($withholdingTotal > 0) {
            $withholdingPayableId = $this->resolveWithholdingPayableAccountId();
            if ($withholdingPayableId) {
                $journalLines[] = [
                    'account_id' => $withholdingPayableId,
                    'debit' => 0,
                    'credit' => $withholdingTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'Withholding Tax Payable',
                ];
            }
        }

        $totalCashCredit = ($totalAmount + $ppnTotal) - $withholdingTotal;
        $journalLines[] = [
            'account_id' => $cashAccountId,
            'debit' => 0,
            'credit' => $totalCashCredit,
            'project_id' => null,
            'dept_id' => null,
            'memo' => $invoice->is_opening_balance
                ? 'Cash payment for opening balance invoice #'.$invoice->invoice_no
                : 'Cash payment for purchase invoice #'.$invoice->invoice_no,
        ];

        return new JournalDraft(
            description: $invoice->is_opening_balance
                ? 'Direct Cash Purchase Invoice (Opening Balance) #'.$invoice->invoice_no
                : 'Direct Cash Purchase Invoice #'.$invoice->invoice_no,
            lines: $journalLines,
            date: $invoice->date->toDateString(),
        );
    }

    private function buildCreditPurchase(PurchaseInvoice $invoice): JournalDraft
    {
        $apUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '2.1.1.03')->value('id');
        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id');
        $ppnInputId = (int) (DB::table('accounts')->where('code', '1.1.4.01')->value('id') ?? DB::table('accounts')->where('code', '1.1.6')->value('id') ?? 0);

        $expenseTotal = 0.0;
        $ppnTotal = 0.0;
        $withholdingTotal = 0.0;
        $lines = [];
        $expenseByAccount = [];
        $useLineAccounts = $invoice->is_opening_balance || ! $invoice->isLinkedToGoodsReceiptPo();
        $dppByLineId = $this->scaledDppByPurchaseInvoiceLineId($invoice);

        foreach ($invoice->lines as $l) {
            $lineAmount = $dppByLineId[$l->id]
                ?? (($l->net_amount > 0) ? (float) $l->net_amount : (float) $l->amount);
            $expenseTotal += $lineAmount;

            if ($useLineAccounts) {
                $accountId = (int) $l->account_id;
                if (! isset($expenseByAccount[$accountId])) {
                    $expenseByAccount[$accountId] = 0;
                }
                $expenseByAccount[$accountId] += $lineAmount;
            }

            if (! empty($l->tax_code_id)) {
                $tax = DB::table('tax_codes')->where('id', $l->tax_code_id)->first();
                $ppnTotal += PurchaseInvoiceLineTaxMath::ppnAmount($lineAmount, $tax);
            }

            $wtaxRate = (float) ($l->wtax_rate ?? 0);
            $tax = ! empty($l->tax_code_id)
                ? DB::table('tax_codes')->where('id', $l->tax_code_id)->first()
                : null;
            $withholdingTotal += PurchaseInvoiceLineTaxMath::withholdingAmount($lineAmount, $tax, $wtaxRate);
        }

        if ($ppnTotal > 0) {
            if (! $ppnInputId) {
                throw new \Exception('PPN Input account (1.1.4.01 or 1.1.6) not found. Please create the account in Chart of Accounts.');
            }
            $lines[] = [
                'account_id' => $ppnInputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Masukan',
            ];
        }

        if ($withholdingTotal > 0) {
            $withholdingPayableId = $this->resolveWithholdingPayableAccountId();
            if ($withholdingPayableId) {
                $lines[] = [
                    'account_id' => $withholdingPayableId,
                    'debit' => 0,
                    'credit' => $withholdingTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'Withholding Tax Payable',
                ];
            }
        }

        if ($useLineAccounts) {
            foreach ($expenseByAccount as $accountId => $amount) {
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => $invoice->is_opening_balance ? 'Opening Balance - Expense/Inventory' : 'Expense/Inventory',
                ];
            }
        } else {
            $lines[] = [
                'account_id' => $apUnInvoiceAccountId,
                'debit' => $expenseTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Reduce AP UnInvoice',
            ];
        }

        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => 0,
            'credit' => ($expenseTotal + $ppnTotal) - $withholdingTotal,
            'project_id' => null,
            'dept_id' => null,
            'memo' => $invoice->is_opening_balance ? 'Accounts Payable - Opening Balance' : 'Accounts Payable',
        ];

        return new JournalDraft(
            description: $invoice->is_opening_balance
                ? 'Post AP Invoice (Opening Balance) #'.$invoice->invoice_no
                : 'Post AP Invoice #'.$invoice->invoice_no,
            lines: $lines,
            date: $invoice->date->toDateString(),
        );
    }

    /**
     * @return array<int, float>
     */
    private function scaledDppByPurchaseInvoiceLineId(PurchaseInvoice $invoice): array
    {
        $invoice->load(['lines' => fn ($q) => $q->orderBy('id')]);
        $map = [];
        foreach (HeaderDiscountAllocation::purchaseInvoiceLineScaled($invoice) as $row) {
            $map[$row['line_id']] = (float) $row['dpp'];
        }

        return $map;
    }

    private function resolveWithholdingPayableAccountId(string $taxCode = 'PPH23'): int
    {
        $accountCode = match (strtoupper($taxCode)) {
            'PPH21' => '2.1.2.02',
            'PPH22' => '2.1.2.03',
            default => '2.1.2.04',
        };

        return (int) DB::table('accounts')->where('code', $accountCode)->value('id');
    }
}
