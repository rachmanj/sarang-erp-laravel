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
}
