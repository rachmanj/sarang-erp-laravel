<?php

namespace App\Services\Bank;

use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use App\Models\Bank\ReconciliationMatchAudit;
use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReconciliationAdjustmentService
{
    public function __construct(
        private PostingService $postingService,
        private BankBookLineFetcher $bookLineFetcher,
        private ReconciliationMatchingService $matchingService,
        private ReconciliationSnapshotIntegrityService $snapshotIntegrityService,
    ) {}

    /**
     * Post an adjusting journal from an unmatched bank statement line, then
     * re-fetch book lines and attempt to auto-match the new GL line.
     *
     * Bank debit (money out / charge) → Cr bank, Dr counter expense
     * Bank credit (money in / interest) → Dr bank, Cr counter income
     */
    public function postFromBankLine(
        BankReconciliation $reconciliation,
        BankStatementLine $line,
        int $counterAccountId,
        ?string $memo = null,
    ): int {
        if ($reconciliation->isLockedForEditing()) {
            throw new \RuntimeException('Reconciliation session is locked.');
        }

        if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Line does not belong to this session.');
        }

        if (! $line->isAvailableForMatching()) {
            throw new \RuntimeException('Only unmatched bank lines can be adjusted.');
        }

        if ($line->adjusting_journal_id) {
            throw new \RuntimeException('This bank line already has an adjusting journal.');
        }

        $bankAccountId = $reconciliation->bankAccount?->account_id;
        if (! $bankAccountId) {
            throw new \RuntimeException('Bank account is not linked to a COA account.');
        }

        $debit = round((float) $line->debit, 2);
        $credit = round((float) $line->credit, 2);
        $amount = max($debit, $credit);

        if ($amount <= 0) {
            throw new \RuntimeException('Bank line amount must be greater than zero.');
        }

        $description = $memo
            ?: ('Bank recon adjustment: '.($line->description ?: $line->reference_no ?: 'line #'.$line->id));

        if ($debit > 0) {
            $lines = [
                ['account_id' => $counterAccountId, 'debit' => $amount, 'credit' => 0, 'memo' => $description],
                ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => $amount, 'memo' => $description],
            ];
        } else {
            $lines = [
                ['account_id' => $bankAccountId, 'debit' => $amount, 'credit' => 0, 'memo' => $description],
                ['account_id' => $counterAccountId, 'debit' => 0, 'credit' => $amount, 'memo' => $description],
            ];
        }

        return DB::transaction(function () use ($reconciliation, $line, $lines, $description, $amount) {
            $journalId = $this->postingService->postJournal([
                'date' => $line->posting_date->toDateString(),
                'description' => $description,
                'source_type' => 'bank_reconciliation_adjustment',
                'source_id' => $reconciliation->id,
                'posted_by' => Auth::id(),
                'lines' => $lines,
            ]);

            $line->update(['adjusting_journal_id' => $journalId]);

            ReconciliationMatchAudit::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'action' => ReconciliationMatchAudit::ACTION_ADJUSTMENT,
                'bank_total' => $line->netAmount(),
                'bank_line_ids' => [$line->id],
                'performed_by' => Auth::id(),
                'notes' => "Posted adjusting journal #{$journalId} for amount ".number_format($amount, 2),
            ]);

            $this->bookLineFetcher->fetchAndReplace($reconciliation);
            $this->snapshotIntegrityService->refreshStaleFlags($reconciliation);
            $this->matchingService->autoMatch($reconciliation->fresh());

            return $journalId;
        });
    }
}
