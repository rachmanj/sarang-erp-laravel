<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use Illuminate\Support\Facades\DB;

class ReconciliationSnapshotIntegrityService
{
    /**
     * Flag book lines whose journal_line_id is missing, unposted, or amount-changed.
     *
     * @return int Number of stale lines found/updated
     */
    public function refreshStaleFlags(BankReconciliation $reconciliation): int
    {
        $stale = 0;

        $lines = BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->get();

        foreach ($lines as $line) {
            $reason = null;

            if ($line->journal_line_id === null) {
                if (in_array($line->match_status, [
                    BankBookLine::MATCH_MATCHED,
                    BankBookLine::MATCH_MANUAL,
                ], true) && ! $line->is_carried_forward) {
                    $reason = 'Source journal line deleted';
                }
            } else {
                $row = DB::table('journal_lines as jl')
                    ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                    ->where('jl.id', $line->journal_line_id)
                    ->select([
                        'jl.id',
                        'jl.debit',
                        'jl.credit',
                        'j.posted_at',
                    ])
                    ->first();

                if (! $row) {
                    $reason = 'Source journal line deleted';
                } elseif ($row->posted_at === null) {
                    $reason = 'Source journal unposted';
                } elseif (
                    abs(round((float) $row->debit, 2) - round((float) $line->debit, 2)) >= ReconciliationBalanceService::TOLERANCE
                    || abs(round((float) $row->credit, 2) - round((float) $line->credit, 2)) >= ReconciliationBalanceService::TOLERANCE
                ) {
                    $reason = 'Source journal line amounts changed';
                }
            }

            $isStale = $reason !== null;
            if ((bool) $line->is_stale !== $isStale || $line->stale_reason !== $reason) {
                $line->update([
                    'is_stale' => $isStale,
                    'stale_reason' => $reason,
                ]);
            }

            if ($isStale) {
                $stale++;
            }
        }

        return $stale;
    }

    /**
     * @return list<array{id: int, reason: string|null}>
     */
    public function staleLines(BankReconciliation $reconciliation): array
    {
        return BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('is_stale', true)
            ->get(['id', 'stale_reason'])
            ->map(fn (BankBookLine $line) => [
                'id' => $line->id,
                'reason' => $line->stale_reason,
            ])
            ->all();
    }
}
