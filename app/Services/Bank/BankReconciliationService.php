<?php

namespace App\Services\Bank;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatement;
use App\Models\Bank\BankStatementLine;
use App\Models\Bank\ReconciliationMatchAudit;
use App\Services\CompanyEntityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class BankReconciliationService
{
    public function __construct(
        private CompanyEntityService $companyEntityService,
        private ReconciliationBalanceService $balanceService,
        private BankBookLineFetcher $bookLineFetcher,
        private ReconciliationCarryForwardService $carryForwardService,
        private ReconciliationSnapshotIntegrityService $snapshotIntegrityService,
    ) {}

    public function createManualSession(BankAccount $bankAccount, string $periode): BankReconciliation
    {
        $monthStart = date('Y-m-01', strtotime($periode));

        $this->assertUniquePeriod($bankAccount->id, $monthStart);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => null,
            'periode' => $monthStart,
            'period_start' => $monthStart,
            'period_end' => date('Y-m-t', strtotime($monthStart)),
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'opening_balance_bank' => 0,
            'closing_balance_bank' => 0,
            'statement_opening' => 0,
            'statement_closing' => 0,
            'created_by' => Auth::id(),
            'company_entity_id' => $this->companyEntityService->getDefaultEntity()->id,
        ]);

        $this->carryForwardService->importOutstandingInto($reconciliation);

        return $reconciliation;
    }

    public function createAiSession(BankAccount $bankAccount, string $periode, UploadedFile $file): BankReconciliation
    {
        $monthStart = date('Y-m-01', strtotime($periode));
        $this->assertUniquePeriod($bankAccount->id, $monthStart);

        $storedPath = $file->store('bank-statements');

        $statement = BankStatement::create([
            'bank_account_id' => $bankAccount->id,
            'period_start' => $monthStart,
            'period_end' => date('Y-m-t', strtotime($monthStart)),
            'opening_balance' => 0,
            'closing_balance' => 0,
            'currency' => $bankAccount->currency ?: 'IDR',
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'status' => 'imported',
            'imported_by' => Auth::id(),
            'company_entity_id' => $this->companyEntityService->getDefaultEntity()->id,
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'periode' => $monthStart,
            'period_start' => $monthStart,
            'period_end' => date('Y-m-t', strtotime($monthStart)),
            'source_mode' => BankReconciliation::SOURCE_AI,
            'status' => BankReconciliation::STATUS_PROCESSING,
            'created_by' => Auth::id(),
            'company_entity_id' => $statement->company_entity_id,
        ]);

        $this->carryForwardService->importOutstandingInto($reconciliation);

        return $reconciliation;
    }

    public function updateStatementBalances(
        BankReconciliation $reconciliation,
        float $opening,
        float $closing,
    ): void {
        $this->assertEditable($reconciliation);

        $reconciliation->update([
            'opening_balance_bank' => round($opening, 2),
            'closing_balance_bank' => round($closing, 2),
            'statement_opening' => round($opening, 2),
            'statement_closing' => round($closing, 2),
        ]);

        if ($reconciliation->statement) {
            $reconciliation->statement->update([
                'opening_balance' => round($opening, 2),
                'closing_balance' => round($closing, 2),
            ]);
        }
    }

    public function addBankLine(BankReconciliation $reconciliation, array $data): BankStatementLine
    {
        $this->assertEditable($reconciliation);

        $debit = round((float) ($data['debit'] ?? 0), 2);
        $credit = round((float) ($data['credit'] ?? 0), 2);

        return BankStatementLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_statement_id' => $reconciliation->bank_statement_id,
            'posting_date' => $data['posting_date'],
            'value_date' => $data['value_date'] ?? null,
            'description' => $data['description'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'amount' => max($debit, $credit),
            'direction' => $debit > 0 ? 'debit' : 'credit',
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $data['running_balance'] ?? null,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => BankReconciliationSupport::lineHash(
                $data['posting_date'],
                $debit > 0 ? 'debit' : 'credit',
                max($debit, $credit),
                $data['reference_no'] ?? null,
                $data['description'] ?? null,
            ),
            'is_ai_extracted' => false,
            'line_order' => ($reconciliation->bankLines()->max('line_order') ?? 0) + 1,
        ]);
    }

    public function updateBankLine(BankReconciliation $reconciliation, BankStatementLine $line, array $data): BankStatementLine
    {
        $this->assertEditable($reconciliation);

        if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Line does not belong to this session.');
        }

        if (! $line->isAvailableForMatching()) {
            throw new \RuntimeException('Only unmatched bank lines can be edited.');
        }

        $debit = round((float) ($data['debit'] ?? 0), 2);
        $credit = round((float) ($data['credit'] ?? 0), 2);

        $line->update([
            'posting_date' => $data['posting_date'],
            'value_date' => $data['value_date'] ?? null,
            'description' => $data['description'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'amount' => max($debit, $credit),
            'direction' => $debit > 0 ? 'debit' : 'credit',
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $data['running_balance'] ?? null,
        ]);

        return $line->fresh();
    }

    public function deleteBankLine(BankReconciliation $reconciliation, BankStatementLine $line): void
    {
        $this->assertEditable($reconciliation);

        if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Line does not belong to this session.');
        }

        if (! $line->isAvailableForMatching()) {
            throw new \RuntimeException('Only unmatched bank lines can be deleted.');
        }

        $line->delete();
    }

    public function setBankLineExcluded(BankReconciliation $reconciliation, BankStatementLine $line, bool $exclude, ?string $reason = null): void
    {
        $this->assertEditable($reconciliation);

        if ($exclude && blank($reason)) {
            throw new \RuntimeException('Exclude reason is required.');
        }

        if (! $line->isAvailableForMatching() && $line->match_status !== BankStatementLine::MATCH_EXCLUDED) {
            throw new \RuntimeException('Only unmatched or excluded lines can be toggled.');
        }

        $line->update([
            'match_status' => $exclude ? BankStatementLine::MATCH_EXCLUDED : BankStatementLine::MATCH_UNMATCHED,
            'exclude_reason' => $exclude ? $reason : null,
        ]);
    }

    public function setBookLineExcluded(BankReconciliation $reconciliation, BankBookLine $line, bool $exclude, ?string $reason = null): void
    {
        $this->assertEditable($reconciliation);

        if ($exclude && blank($reason)) {
            throw new \RuntimeException('Exclude reason is required.');
        }

        if (! $line->isAvailableForMatching() && $line->match_status !== BankBookLine::MATCH_EXCLUDED) {
            throw new \RuntimeException('Only unmatched or excluded lines can be toggled.');
        }

        $line->update([
            'match_status' => $exclude ? BankBookLine::MATCH_EXCLUDED : BankBookLine::MATCH_UNMATCHED,
            'exclude_reason' => $exclude ? $reason : null,
        ]);
    }

    public function setBankLineOutstanding(
        BankReconciliation $reconciliation,
        BankStatementLine $line,
        bool $outstanding,
        ?string $reason = null,
    ): void {
        $this->assertEditable($reconciliation);

        if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Line does not belong to this session.');
        }

        if (! $line->canMarkOutstanding()) {
            throw new \RuntimeException('Only unmatched or outstanding bank lines can be toggled.');
        }

        $line->update([
            'match_status' => $outstanding ? BankStatementLine::MATCH_OUTSTANDING : BankStatementLine::MATCH_UNMATCHED,
            'line_notes' => $outstanding ? ($reason ?: 'Outstanding timing difference') : null,
        ]);

        ReconciliationMatchAudit::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'action' => ReconciliationMatchAudit::ACTION_OUTSTANDING,
            'bank_line_ids' => [$line->id],
            'performed_by' => Auth::id(),
            'notes' => $outstanding ? ($reason ?: 'Marked outstanding') : 'Cleared outstanding',
        ]);
    }

    public function setBookLineOutstanding(
        BankReconciliation $reconciliation,
        BankBookLine $line,
        bool $outstanding,
        ?string $reason = null,
    ): void {
        $this->assertEditable($reconciliation);

        if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Line does not belong to this session.');
        }

        if (! $line->canMarkOutstanding()) {
            throw new \RuntimeException('Only unmatched or outstanding book lines can be toggled.');
        }

        $line->update([
            'match_status' => $outstanding ? BankBookLine::MATCH_OUTSTANDING : BankBookLine::MATCH_UNMATCHED,
            'line_notes' => $outstanding ? ($reason ?: 'Outstanding timing difference') : null,
        ]);

        ReconciliationMatchAudit::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'action' => ReconciliationMatchAudit::ACTION_OUTSTANDING,
            'book_line_ids' => [$line->id],
            'performed_by' => Auth::id(),
            'notes' => $outstanding ? ($reason ?: 'Marked outstanding') : 'Cleared outstanding',
        ]);
    }

    public function finalize(BankReconciliation $reconciliation): BankReconciliation
    {
        if ($reconciliation->isLockedForEditing()) {
            throw new \RuntimeException('Reconciliation session is already completed.');
        }

        $staleCount = $this->snapshotIntegrityService->refreshStaleFlags($reconciliation);
        if ($staleCount > 0) {
            throw new \RuntimeException(
                "Cannot finalize: {$staleCount} book line(s) reference missing or changed journals. Re-fetch book lines first."
            );
        }

        $crossFoot = $this->balanceService->statementCrossFoot($reconciliation);
        if ($crossFoot !== null && ! $crossFoot['valid']
            && abs($this->balanceService->statementClosing($reconciliation)) + abs($this->balanceService->statementOpening($reconciliation)) > ReconciliationBalanceService::TOLERANCE
        ) {
            throw new \RuntimeException(
                'Statement lines do not cross-foot to statement closing − opening. Difference: '
                .number_format($crossFoot['difference'], 2)
                .'. Check for missing or duplicated statement lines.'
            );
        }

        if (! $this->balanceService->isBalanced($reconciliation)) {
            $payload = $this->balanceService->statusPayload($reconciliation);
            $parts = [];
            if ($payload['unmatched_bank_count'] > 0 || $payload['unmatched_book_count'] > 0) {
                $parts[] = "unmatched lines remain (bank {$payload['unmatched_bank_count']}, book {$payload['unmatched_book_count']})";
            }
            if (abs($payload['difference']) >= ReconciliationBalanceService::TOLERANCE) {
                $parts[] = 'cleared net difference '.number_format($payload['difference'], 2);
            }
            if (abs($payload['reconciliation_difference']) >= ReconciliationBalanceService::TOLERANCE
                && $payload['has_statement_balances']
            ) {
                $parts[] = 'reconciliation identity difference '.number_format($payload['reconciliation_difference'], 2);
            }

            throw new \RuntimeException(
                'Cannot finalize: '.implode('; ', $parts ?: ['session is not balanced'])
                .'. Mark timing differences as Outstanding, exclude errors, or create matches/adjustments.'
            );
        }

        // Keep book closing from GL snapshot (opening + movement), do not overwrite with bookNet.
        $bookClosing = $this->balanceService->bookClosing($reconciliation);

        $reconciliation->update([
            'status' => BankReconciliation::STATUS_COMPLETED,
            'finalized_by' => Auth::id(),
            'finalized_at' => now(),
            'closing_balance_book' => $bookClosing,
            'book_balance' => $bookClosing,
        ]);

        if ($reconciliation->statement) {
            $reconciliation->statement->update(['status' => 'reconciled']);
        }

        return $reconciliation->fresh(['bankAccount.account', 'bankLines', 'bookLines', 'matchGroups']);
    }

    public function markFailed(BankReconciliation $reconciliation, string $message): void
    {
        $reconciliation->update([
            'status' => BankReconciliation::STATUS_FAILED,
            'notes' => $message,
        ]);
    }

    public function markInReview(BankReconciliation $reconciliation, array $balances = []): void
    {
        $reconciliation->update(array_merge([
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ], $balances));
    }

    public function fetchBookLines(BankReconciliation $reconciliation): int
    {
        $inserted = $this->bookLineFetcher->fetchAndReplace($reconciliation);
        $this->snapshotIntegrityService->refreshStaleFlags($reconciliation);

        return $inserted;
    }

    private function assertUniquePeriod(int $bankAccountId, string $periode): void
    {
        $exists = BankReconciliation::query()
            ->where('bank_account_id', $bankAccountId)
            ->whereDate('periode', $periode)
            ->exists();

        if ($exists) {
            throw new \RuntimeException('A reconciliation session already exists for this bank account and month.');
        }
    }

    private function assertEditable(BankReconciliation $reconciliation): void
    {
        if ($reconciliation->isLockedForEditing()) {
            throw new \RuntimeException('Reconciliation session is locked.');
        }
    }
}
