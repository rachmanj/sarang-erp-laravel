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

    public function getArAging(?string $asOf = null): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $invoices = DB::table('sales_invoices')
            ->where('status', 'posted')
            ->whereDate('date', '<=', $asOfDate)
            ->select('customer_id', 'date', 'total_amount')
            ->get();

        $buckets = [];
        foreach ($invoices as $inv) {
            $days = \Carbon\Carbon::parse($inv->date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
            $bucket = $this->bucketLabel($days);
            $key = (int) $inv->customer_id;
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['customer_id' => $key, 'current' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd91_plus' => 0, 'total' => 0];
            }
            switch ($bucket) {
                case 'current':
                    $buckets[$key]['current'] += (float)$inv->total_amount;
                    break;
                case '31-60':
                    $buckets[$key]['d31_60'] += (float)$inv->total_amount;
                    break;
                case '61-90':
                    $buckets[$key]['d61_90'] += (float)$inv->total_amount;
                    break;
                default:
                    $buckets[$key]['d91_plus'] += (float)$inv->total_amount;
                    break;
            }
            $buckets[$key]['total'] += (float)$inv->total_amount;
        }

        return [
            'as_of' => $asOfDate,
            'rows' => array_values($buckets),
            'totals' => [
                'current' => array_sum(array_column($buckets, 'current')),
                'd31_60' => array_sum(array_column($buckets, 'd31_60')),
                'd61_90' => array_sum(array_column($buckets, 'd61_90')),
                'd91_plus' => array_sum(array_column($buckets, 'd91_plus')),
                'total' => array_sum(array_column($buckets, 'total')),
            ],
        ];
    }

    public function getApAging(?string $asOf = null): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $invoices = DB::table('purchase_invoices')
            ->where('status', 'posted')
            ->whereDate('date', '<=', $asOfDate)
            ->select('vendor_id', 'date', 'total_amount')
            ->get();

        $buckets = [];
        foreach ($invoices as $inv) {
            $days = \Carbon\Carbon::parse($inv->date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
            $bucket = $this->bucketLabel($days);
            $key = (int) $inv->vendor_id;
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['vendor_id' => $key, 'current' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd91_plus' => 0, 'total' => 0];
            }
            switch ($bucket) {
                case 'current':
                    $buckets[$key]['current'] += (float)$inv->total_amount;
                    break;
                case '31-60':
                    $buckets[$key]['d31_60'] += (float)$inv->total_amount;
                    break;
                case '61-90':
                    $buckets[$key]['d61_90'] += (float)$inv->total_amount;
                    break;
                default:
                    $buckets[$key]['d91_plus'] += (float)$inv->total_amount;
                    break;
            }
            $buckets[$key]['total'] += (float)$inv->total_amount;
        }

        return [
            'as_of' => $asOfDate,
            'rows' => array_values($buckets),
            'totals' => [
                'current' => array_sum(array_column($buckets, 'current')),
                'd31_60' => array_sum(array_column($buckets, 'd31_60')),
                'd61_90' => array_sum(array_column($buckets, 'd61_90')),
                'd91_plus' => array_sum(array_column($buckets, 'd91_plus')),
                'total' => array_sum(array_column($buckets, 'total')),
            ],
        ];
    }

    public function getCashLedger(array $filters = []): array
    {
        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $q = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->select('j.date', 'j.description', 'jl.debit', 'jl.credit')
            ->where('jl.account_id', $cashAccountId);
        if (!empty($filters['from'])) {
            $q->whereDate('j.date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('j.date', '<=', $filters['to']);
        }
        $rows = $q->orderBy('j.date')->orderBy('j.id')->get()->toArray();
        $balance = 0.0;
        $out = [];
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
        return ['rows' => $out, 'filters' => $filters];
    }

    private function bucketLabel(int $days): string
    {
        if ($days <= 30) return 'current';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        return '91+';
    }
}
