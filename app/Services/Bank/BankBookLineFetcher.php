<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use Illuminate\Support\Facades\DB;

class BankBookLineFetcher
{
    public function fetchAndReplace(BankReconciliation $reconciliation): int
    {
        $accountId = $reconciliation->bankAccount?->account_id;
        if (! $accountId) {
            return 0;
        }

        $start = $reconciliation->periodStartDate()->toDateString();
        $end = $reconciliation->periodEndDate()->toDateString();
        $currency = $reconciliation->bankAccount?->currency ?: 'IDR';
        $useForeign = strtoupper($currency) !== 'IDR';

        $rows = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '>=', $start)
            ->whereDate('j.date', '<=', $end)
            ->select([
                'jl.id as journal_line_id',
                'jl.journal_id',
                'j.date',
                'j.description as journal_description',
                'j.source_type',
                'j.source_id',
                'jl.debit',
                'jl.credit',
                'jl.debit_foreign',
                'jl.credit_foreign',
                'c.code as currency_code',
                'jl.memo',
            ])
            ->orderBy('j.date')
            ->orderBy('jl.id')
            ->get();

        $preserveStatuses = [
            BankBookLine::MATCH_MATCHED,
            BankBookLine::MATCH_MANUAL,
            BankBookLine::MATCH_OUTSTANDING,
            BankBookLine::MATCH_EXCLUDED,
        ];

        $preservedJournalLineIds = BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('match_status', $preserveStatuses)
            ->whereNotNull('journal_line_id')
            ->pluck('journal_line_id');

        // Keep carried-forward and preserved lines; only replace plain unmatched period lines.
        BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankBookLine::MATCH_UNMATCHED)
            ->where('is_carried_forward', false)
            ->delete();

        $inserted = 0;
        foreach ($rows as $row) {
            if ($preservedJournalLineIds->contains($row->journal_line_id)) {
                continue;
            }

            $exists = BankBookLine::query()
                ->where('bank_reconciliation_id', $reconciliation->id)
                ->where('journal_line_id', $row->journal_line_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $debit = round((float) $row->debit, 2);
            $credit = round((float) $row->credit, 2);
            $debitForeign = $row->debit_foreign !== null ? round((float) $row->debit_foreign, 2) : null;
            $creditForeign = $row->credit_foreign !== null ? round((float) $row->credit_foreign, 2) : null;

            if ($useForeign && ($debitForeign !== null || $creditForeign !== null)) {
                // For FX bank accounts, matching uses foreign amounts when available.
                $debit = $debitForeign ?? $debit;
                $credit = $creditForeign ?? $credit;
            }

            BankBookLine::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'journal_line_id' => $row->journal_line_id,
                'doc_date' => $row->date,
                'posting_date' => $row->date,
                'doc_num' => (string) $row->journal_id,
                'ref_doc_num' => $row->source_type,
                'transaction_id' => $row->source_id ? (string) $row->source_id : null,
                'description' => trim(($row->journal_description ?? '').' '.($row->memo ?? '')),
                'debit' => $debit,
                'credit' => $credit,
                'debit_foreign' => $debitForeign,
                'credit_foreign' => $creditForeign,
                'currency_code' => $row->currency_code ?: $currency,
                'match_status' => BankBookLine::MATCH_UNMATCHED,
                'is_stale' => false,
            ]);

            $inserted++;
        }

        $opening = $this->openingBalance((int) $accountId, $start, $useForeign);
        $movement = $this->periodMovement((int) $accountId, $start, $end, $useForeign);

        $reconciliation->update([
            'opening_balance_book' => round($opening, 2),
            'closing_balance_book' => round($opening + $movement, 2),
            'book_balance' => round($opening + $movement, 2),
        ]);

        return $inserted;
    }

    private function openingBalance(int $accountId, string $fromDate, bool $useForeign): float
    {
        $sumExpr = $useForeign
            ? 'COALESCE(SUM(COALESCE(jl.debit_foreign, jl.debit) - COALESCE(jl.credit_foreign, jl.credit)), 0)'
            : 'COALESCE(SUM(jl.debit - jl.credit), 0)';

        return (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '<', $fromDate)
            ->selectRaw($sumExpr.' as bal')
            ->value('bal');
    }

    private function periodMovement(int $accountId, string $start, string $end, bool $useForeign): float
    {
        $sumExpr = $useForeign
            ? 'COALESCE(SUM(COALESCE(jl.debit_foreign, jl.debit) - COALESCE(jl.credit_foreign, jl.credit)), 0)'
            : 'COALESCE(SUM(jl.debit - jl.credit), 0)';

        return (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '>=', $start)
            ->whereDate('j.date', '<=', $end)
            ->selectRaw($sumExpr.' as movement')
            ->value('movement');
    }
}
