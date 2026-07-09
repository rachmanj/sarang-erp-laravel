<?php

namespace App\Services\Bank;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatement;
use App\Models\Bank\BankStatementLine;
use App\Services\CompanyEntityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class BankReconciliationService
{
    public function __construct(
        private CompanyEntityService $companyEntityService,
        private ReconciliationBalanceService $balanceService,
        private BankBookLineFetcher $bookLineFetcher,
    ) {}

    public function createManualSession(BankAccount $bankAccount, string $periode): BankReconciliation
    {
        $monthStart = date('Y-m-01', strtotime($periode));

        $this->assertUniquePeriod($bankAccount->id, $monthStart);

        return BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => null,
            'periode' => $monthStart,
            'period_start' => $monthStart,
            'period_end' => date('Y-m-t', strtotime($monthStart)),
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'opening_balance_bank' => 0,
            'closing_balance_bank' => 0,
            'created_by' => Auth::id(),
            'company_entity_id' => $this->companyEntityService->getDefaultEntity()->id,
        ]);
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
            'currency' => 'IDR',
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'status' => 'imported',
            'imported_by' => Auth::id(),
            'company_entity_id' => $this->companyEntityService->getDefaultEntity()->id,
        ]);

        return BankReconciliation::create([
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

    public function finalize(BankReconciliation $reconciliation): BankReconciliation
    {
        if ($reconciliation->isLockedForEditing()) {
            throw new \RuntimeException('Reconciliation session is already completed.');
        }

        if (! $this->balanceService->isBalanced($reconciliation)) {
            throw new \RuntimeException(
                'Cannot finalize while difference is '
                .number_format($this->balanceService->difference($reconciliation), 2)
                .'. Exclude lines or create matches until balanced.'
            );
        }

        $reconciliation->update([
            'status' => BankReconciliation::STATUS_COMPLETED,
            'finalized_by' => Auth::id(),
            'finalized_at' => now(),
            'closing_balance_book' => $this->balanceService->bookNet($reconciliation),
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
        return $this->bookLineFetcher->fetchAndReplace($reconciliation);
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
