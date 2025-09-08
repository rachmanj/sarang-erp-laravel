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
            ->whereDate('j.date', '<=', $date)
            ->selectRaw('a.id, a.code, a.name, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
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
            ->select('j.date', 'j.description as journal_desc', 'a.code as account_code', 'a.name as account_name', 'jl.debit', 'jl.credit', 'jl.memo');

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
}
