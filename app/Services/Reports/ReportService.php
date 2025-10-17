<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getTrialBalance(?string $date = null): array
    {
        $date = $date ?: now()->toDateString();

        $lines = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id')
            ->whereDate('j.date', '<=', $date)
            ->selectRaw('a.id, a.code, a.name, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit, 
                        GROUP_CONCAT(DISTINCT c.code ORDER BY c.code SEPARATOR ", ") as currencies')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        $rows = $lines->map(function ($r) {
            $balance = (float)$r->debit - (float)$r->credit;
            return [
                'account_id' => (int)$r->id,
                'code' => $r->code,
                'name' => $r->name,
                'type' => $r->type,
                'debit' => (float)$r->debit,
                'credit' => (float)$r->credit,
                'balance' => $balance,
                'currencies' => $r->currencies ?: 'IDR', // Default to IDR if no currency info
            ];
        })->toArray();

        return [
            'as_of' => $date,
            'rows' => $rows,
            'totals' => [
                'debit' => array_sum(array_column($rows, 'debit')),
                'credit' => array_sum(array_column($rows, 'credit')),
            ],
        ];
    }

    public function getGlDetail(array $filters = []): array
    {
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id')
            ->select(
                'j.date',
                'j.description as journal_desc',
                'a.code as account_code',
                'a.name as account_name',
                'jl.debit',
                'jl.credit',
                'jl.memo',
                'c.code as currency_code',
                'jl.exchange_rate',
                'jl.debit_foreign',
                'jl.credit_foreign'
            );

        if (!empty($filters['account_id'])) {
            $query->where('a.id', $filters['account_id']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('j.date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('j.date', '<=', $filters['to']);
        }
        if (!empty($filters['project_id'])) {
            $query->where('jl.project_id', $filters['project_id']);
        }
        if (!empty($filters['fund_id'])) {
            $query->where('jl.fund_id', $filters['fund_id']);
        }
        if (!empty($filters['dept_id'])) {
            $query->where('jl.dept_id', $filters['dept_id']);
        }

        $rows = $query->orderBy('j.date')->orderBy('j.id')->get()->toArray();

        return [
            'filters' => $filters,
            'rows' => array_map(function ($r) {
                return [
                    'date' => $r->date,
                    'journal_desc' => $r->journal_desc,
                    'account_code' => $r->account_code,
                    'account_name' => $r->account_name,
                    'debit' => (float)$r->debit,
                    'credit' => (float)$r->credit,
                    'memo' => $r->memo,
                    'currency_code' => $r->currency_code ?: 'IDR',
                    'exchange_rate' => (float)$r->exchange_rate ?: 1.000000,
                    'debit_foreign' => (float)$r->debit_foreign,
                    'credit_foreign' => (float)$r->credit_foreign,
                ];
            }, $rows),
        ];
    }

    public function getArAging(?string $asOf = null, array $options = []): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $invoices = DB::table('sales_invoices as si')
            ->leftJoin('customers as c', 'c.id', '=', 'si.customer_id')
            ->where('status', 'posted')
            ->whereDate(DB::raw('COALESCE(si.due_date, si.date)'), '<=', $asOfDate)
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->leftJoin('sales_receipts as sr', function ($join) {
                $join->on('sr.id', '=', 'sra.receipt_id')->where('sr.status', '=', 'posted');
            })
            ->select('si.id', 'si.customer_id', DB::raw('COALESCE(si.due_date, si.date) as effective_date'), 'si.total_amount', DB::raw('COALESCE(SUM(sra.amount),0) as settled_amount'), 'c.name as customer_name')
            ->groupBy('si.id', 'si.customer_id', 'effective_date', 'si.total_amount', 'c.name')
            ->get();

        $buckets = [];
        foreach ($invoices as $inv) {
            $days = \Carbon\Carbon::parse($inv->effective_date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
            $bucket = $this->bucketLabel($days);
            $key = (int) $inv->customer_id;
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['customer_id' => $key, 'customer_name' => $inv->customer_name, 'current' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd91_plus' => 0, 'total' => 0];
            }
            $net = max(0, (float)$inv->total_amount - (float)$inv->settled_amount);
            if ($net <= 0) {
                continue;
            }
            switch ($bucket) {
                case 'current':
                    $buckets[$key]['current'] += $net;
                    break;
                case '31-60':
                    $buckets[$key]['d31_60'] += $net;
                    break;
                case '61-90':
                    $buckets[$key]['d61_90'] += $net;
                    break;
                default:
                    $buckets[$key]['d91_plus'] += $net;
                    break;
            }
            $buckets[$key]['total'] += $net;
        }

        $rows = array_values($buckets);
        if (!empty($options['overdue_only'])) {
            $rows = array_values(array_filter($rows, function ($r) {
                return ($r['d31_60'] + $r['d61_90'] + $r['d91_plus']) > 0;
            }));
        }

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => [
                'current' => array_sum(array_column($rows, 'current')),
                'd31_60' => array_sum(array_column($rows, 'd31_60')),
                'd61_90' => array_sum(array_column($rows, 'd61_90')),
                'd91_plus' => array_sum(array_column($rows, 'd91_plus')),
                'total' => array_sum(array_column($rows, 'total')),
            ],
        ];
    }

    public function getApAging(?string $asOf = null, array $options = []): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $invoices = DB::table('purchase_invoices as pi')
            ->leftJoin('vendors as v', 'v.id', '=', 'pi.vendor_id')
            ->where('status', 'posted')
            ->whereDate(DB::raw('COALESCE(pi.due_date, pi.date)'), '<=', $asOfDate)
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->leftJoin('purchase_payments as pp', function ($join) {
                $join->on('pp.id', '=', 'ppa.payment_id')->where('pp.status', '=', 'posted');
            })
            ->select('pi.id', 'pi.vendor_id', DB::raw('COALESCE(pi.due_date, pi.date) as effective_date'), 'pi.total_amount', DB::raw('COALESCE(SUM(ppa.amount),0) as settled_amount'), 'v.name as vendor_name')
            ->groupBy('pi.id', 'pi.vendor_id', 'effective_date', 'pi.total_amount', 'v.name')
            ->get();

        $buckets = [];
        foreach ($invoices as $inv) {
            $days = \Carbon\Carbon::parse($inv->effective_date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
            $bucket = $this->bucketLabel($days);
            $key = (int) $inv->vendor_id;
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['vendor_id' => $key, 'vendor_name' => $inv->vendor_name, 'current' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd91_plus' => 0, 'total' => 0];
            }
            $net = max(0, (float)$inv->total_amount - (float)$inv->settled_amount);
            if ($net <= 0) {
                continue;
            }
            switch ($bucket) {
                case 'current':
                    $buckets[$key]['current'] += $net;
                    break;
                case '31-60':
                    $buckets[$key]['d31_60'] += $net;
                    break;
                case '61-90':
                    $buckets[$key]['d61_90'] += $net;
                    break;
                default:
                    $buckets[$key]['d91_plus'] += $net;
                    break;
            }
            $buckets[$key]['total'] += $net;
        }

        $rows = array_values($buckets);
        if (!empty($options['overdue_only'])) {
            $rows = array_values(array_filter($rows, function ($r) {
                return ($r['d31_60'] + $r['d61_90'] + $r['d91_plus']) > 0;
            }));
        }

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => [
                'current' => array_sum(array_column($rows, 'current')),
                'd31_60' => array_sum(array_column($rows, 'd31_60')),
                'd61_90' => array_sum(array_column($rows, 'd61_90')),
                'd91_plus' => array_sum(array_column($rows, 'd91_plus')),
                'total' => array_sum(array_column($rows, 'total')),
            ],
        ];
    }

    public function getCashLedger(array $filters = []): array
    {
        $accountId = !empty($filters['account_id']) ? (int)$filters['account_id'] : (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $q = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->select('j.date', 'j.description', 'jl.debit', 'jl.credit')
            ->where('jl.account_id', $accountId);
        if (!empty($filters['from'])) {
            $q->whereDate('j.date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('j.date', '<=', $filters['to']);
        }
        $rows = $q->orderBy('j.date')->orderBy('j.id')->get()->toArray();
        $opening = 0.0;
        if (!empty($filters['from'])) {
            $openQ = DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->where('jl.account_id', $accountId)
                ->whereDate('j.date', '<', $filters['from'])
                ->selectRaw('COALESCE(SUM(jl.debit - jl.credit),0) as bal')
                ->value('bal');
            $opening = (float) $openQ;
        }
        $balance = $opening;
        $out = [];
        if ($opening !== 0.0) {
            $out[] = [
                'date' => $filters['from'] ?? '',
                'description' => 'Opening Balance',
                'debit' => 0.0,
                'credit' => 0.0,
                'balance' => round($opening, 2),
            ];
        }
        foreach ($rows as $r) {
            $balance += (float)$r->debit - (float)$r->credit;
            $out[] = [
                'date' => $r->date,
                'description' => $r->description,
                'debit' => (float)$r->debit,
                'credit' => (float)$r->credit,
                'balance' => round($balance, 2),
            ];
        }
        return ['rows' => $out, 'filters' => $filters, 'account_id' => $accountId, 'opening_balance' => round($opening, 2)];
    }

    private function bucketLabel(int $days): string
    {
        if ($days <= 30) return 'current';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        return '91+';
    }

    public function getArBalances(): array
    {
        $inv = DB::table('sales_invoices')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $rcp = DB::table('sales_receipts')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $rows = [];
        $partnerIds = array_unique(array_merge(array_keys($inv->toArray()), array_keys($rcp->toArray())));
        foreach ($partnerIds as $pid) {
            $invt = (float) ($inv[$pid] ?? 0);
            $rcpt = (float) ($rcp[$pid] ?? 0);
            $name = DB::table('business_partners')->where('id', $pid)->value('name');
            $rows[] = ['customer_id' => (int)$pid, 'customer_name' => $name, 'invoices' => $invt, 'receipts' => $rcpt, 'balance' => round($invt - $rcpt, 2)];
        }
        return ['rows' => $rows, 'totals' => [
            'invoices' => array_sum(array_column($rows, 'invoices')),
            'receipts' => array_sum(array_column($rows, 'receipts')),
            'balance' => array_sum(array_column($rows, 'balance')),
        ]];
    }

    public function getApBalances(): array
    {
        $inv = DB::table('purchase_invoices')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $pay = DB::table('purchase_payments')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $rows = [];
        $partnerIds = array_unique(array_merge(array_keys($inv->toArray()), array_keys($pay->toArray())));
        foreach ($partnerIds as $pid) {
            $invt = (float) ($inv[$pid] ?? 0);
            $payt = (float) ($pay[$pid] ?? 0);
            $name = DB::table('business_partners')->where('id', $pid)->value('name');
            $rows[] = ['vendor_id' => (int)$pid, 'vendor_name' => $name, 'invoices' => $invt, 'payments' => $payt, 'balance' => round($invt - $payt, 2)];
        }
        return ['rows' => $rows, 'totals' => [
            'invoices' => array_sum(array_column($rows, 'invoices')),
            'payments' => array_sum(array_column($rows, 'payments')),
            'balance' => array_sum(array_column($rows, 'balance')),
        ]];
    }

    public function getWithholdingRecap(array $filters = []): array
    {
        // Per-invoice rounding: first compute each invoice's withholding, then sum by vendor
        $invoiceQuery = DB::table('purchase_invoice_lines as pil')
            ->join('purchase_invoices as pi', 'pi.id', '=', 'pil.invoice_id')
            ->join('tax_codes as t', 't.id', '=', 'pil.tax_code_id')
            ->where('pi.status', 'posted')
            ->whereRaw('LOWER(t.type) = ?', ['withholding']);

        if (!empty($filters['from'])) {
            $invoiceQuery->whereDate('pi.date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $invoiceQuery->whereDate('pi.date', '<=', $filters['to']);
        }
        if (!empty($filters['vendor_id'])) {
            $invoiceQuery->where('pi.business_partner_id', (int) $filters['vendor_id']);
        }

        $invoiceQuery = $invoiceQuery
            ->select('pi.id as invoice_id', 'pi.business_partner_id as vendor_id', DB::raw('ROUND(SUM(pil.amount * t.rate), 2) as inv_withholding'))
            ->groupBy('pi.id', 'pi.business_partner_id');

        $q = DB::query()->fromSub($invoiceQuery, 'w')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'w.vendor_id')
            ->select('w.vendor_id', 'bp.name as vendor_name', DB::raw('SUM(w.inv_withholding) as withholding_total'))
            ->groupBy('w.vendor_id', 'bp.name');

        $rows = $q->get()->map(function ($r) {
            return [
                'vendor_id' => (int) $r->vendor_id,
                'vendor_name' => $r->vendor_name,
                'withholding_total' => round((float) $r->withholding_total, 2),
            ];
        })->toArray();

        return [
            'filters' => $filters,
            'rows' => $rows,
            'totals' => [
                'withholding_total' => array_sum(array_column($rows, 'withholding_total')),
            ],
        ];
    }
}
