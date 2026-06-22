<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class JournalReportQueryBuilder
{
    public function base(bool $onlyPostedJournals = true): Builder
    {
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id');

        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }

        return $query;
    }

    public function withAccounts(bool $onlyPostedJournals = true): Builder
    {
        return $this->base($onlyPostedJournals)
            ->join('accounts as a', 'a.id', '=', 'jl.account_id');
    }

    /**
     * @return array{from: ?string, to: ?string, as_of: ?string, period_year: ?int, period_month: ?int, company_entity_id: ?int}
     */
    public function normalizeFilters(array $filters): array
    {
        $normalized = [
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'as_of' => $filters['as_of'] ?? $filters['date'] ?? null,
            'period_year' => isset($filters['period_year']) ? (int) $filters['period_year'] : null,
            'period_month' => isset($filters['period_month']) ? (int) $filters['period_month'] : null,
            'company_entity_id' => isset($filters['company_entity_id']) ? (int) $filters['company_entity_id'] : null,
            'account_id' => isset($filters['account_id']) ? (int) $filters['account_id'] : null,
            'project_id' => isset($filters['project_id']) ? (int) $filters['project_id'] : null,
            'dept_id' => isset($filters['dept_id']) ? (int) $filters['dept_id'] : null,
        ];

        if ($normalized['period_year'] && $normalized['period_month']) {
            $start = Carbon::create($normalized['period_year'], $normalized['period_month'], 1)->startOfMonth();
            $normalized['from'] = $start->toDateString();
            $normalized['to'] = $start->copy()->endOfMonth()->toDateString();
        } elseif ($normalized['period_year'] && ! $normalized['from'] && ! $normalized['to']) {
            $normalized['from'] = sprintf('%d-01-01', $normalized['period_year']);
            $normalized['to'] = sprintf('%d-12-31', $normalized['period_year']);
        }

        return $normalized;
    }

    public function applyCommonFilters(Builder $query, array $filters, bool $onlyPostedJournals = true): Builder
    {
        $normalized = $this->normalizeFilters($filters);

        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }

        if ($normalized['company_entity_id']) {
            $query->where('j.company_entity_id', $normalized['company_entity_id']);
        }

        if ($normalized['from']) {
            $query->whereDate('j.date', '>=', $normalized['from']);
        }

        if ($normalized['to']) {
            $query->whereDate('j.date', '<=', $normalized['to']);
        }

        if ($normalized['as_of'] && ! $normalized['from'] && ! $normalized['to']) {
            $query->whereDate('j.date', '<=', $normalized['as_of']);
        }

        if ($normalized['account_id']) {
            $query->where('jl.account_id', $normalized['account_id']);
        }

        if ($normalized['project_id']) {
            $query->where('jl.project_id', $normalized['project_id']);
        }

        if ($normalized['dept_id']) {
            $query->where('jl.dept_id', $normalized['dept_id']);
        }

        return $query;
    }

    public function defaultCashAccountId(): ?int
    {
        $prefixes = config('cash_flow.account_prefixes.cash_and_bank', ['1.1.1']);

        foreach ($prefixes as $prefix) {
            $id = DB::table('accounts')
                ->where('is_postable', true)
                ->where(function ($query) use ($prefix) {
                    $query->where('code', $prefix)
                        ->orWhere('code', 'like', $prefix.'.%');
                })
                ->orderBy('code')
                ->value('id');

            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }
}
