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

        $rows = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
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
                'jl.memo',
            ])
            ->orderBy('j.date')
            ->orderBy('jl.id')
            ->get();

        $matchedJournalLineIds = BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('match_status', [
                BankBookLine::MATCH_MATCHED,
                BankBookLine::MATCH_MANUAL,
            ])
            ->whereNotNull('journal_line_id')
            ->pluck('journal_line_id');

        BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankBookLine::MATCH_UNMATCHED)
            ->delete();

        $inserted = 0;
        foreach ($rows as $row) {
            if ($matchedJournalLineIds->contains($row->journal_line_id)) {
                continue;
            }

            $exists = BankBookLine::query()
                ->where('bank_reconciliation_id', $reconciliation->id)
                ->where('journal_line_id', $row->journal_line_id)
                ->exists();

            if ($exists) {
                continue;
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
                'debit' => $row->debit,
                'credit' => $row->credit,
                'match_status' => BankBookLine::MATCH_UNMATCHED,
            ]);

            $inserted++;
        }

        $opening = $this->openingBalance((int) $accountId, $start);
        $movement = (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '>=', $start)
            ->whereDate('j.date', '<=', $end)
            ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) as movement')
            ->value('movement');

        $reconciliation->update([
            'opening_balance_book' => round($opening, 2),
            'closing_balance_book' => round($opening + $movement, 2),
            'book_balance' => round($opening + $movement, 2),
        ]);

        return $inserted;
    }

    private function openingBalance(int $accountId, string $fromDate): float
    {
        return (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '<', $fromDate)
            ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) as bal')
            ->value('bal');
    }
}
