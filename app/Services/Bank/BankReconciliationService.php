<?php

namespace App\Services\Bank;

use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankReconciliationMatch;
use App\Models\Bank\BankStatement;
use App\Models\Bank\BankStatementLine;
use App\Services\Accounting\PostingService;
use App\Services\CompanyEntityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    public function __construct(
        private PostingService $postingService,
        private BankReconciliationOpenRouterClient $aiClient,
        private CompanyEntityService $companyEntityService,
    ) {}

    public function createSessionFromStatement(BankStatement $statement): BankReconciliation
    {
        if ($statement->reconciliation) {
            return $statement->reconciliation;
        }

        $statement->loadMissing('bankAccount.account');

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $statement->bank_account_id,
            'bank_statement_id' => $statement->id,
            'period_start' => $statement->period_start,
            'period_end' => $statement->period_end,
            'statement_opening' => $statement->opening_balance,
            'statement_closing' => $statement->closing_balance,
            'book_balance' => $this->calculateBookBalance($statement),
            'status' => 'open',
            'company_entity_id' => $statement->company_entity_id ?? $this->companyEntityService->getDefaultEntity()->id,
        ]);

        $statement->update(['status' => 'reconciling']);

        return $reconciliation->fresh(['bankAccount.account', 'statement.lines']);
    }

    public function calculateBookBalance(BankStatement $statement): float
    {
        $accountId = $statement->bankAccount?->account_id;
        if (! $accountId) {
            return 0.0;
        }

        $opening = $this->openingBalance((int) $accountId, $statement->period_start->toDateString());
        $movement = (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '>=', $statement->period_start)
            ->whereDate('j.date', '<=', $statement->period_end)
            ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) as movement')
            ->value('movement');

        return round($opening + $movement, 2);
    }

    /**
     * @return Collection<int, object>
     */
    public function getBookCandidates(BankReconciliation $reconciliation): Collection
    {
        $accountId = $reconciliation->bankAccount?->account_id;
        if (! $accountId) {
            return collect();
        }

        $matchedJournalLineIds = BankReconciliationMatch::query()
            ->whereHas('reconciliation', fn ($q) => $q->where('bank_account_id', $reconciliation->bank_account_id))
            ->whereNotNull('journal_line_id')
            ->pluck('journal_line_id');

        return DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '>=', $reconciliation->period_start)
            ->whereDate('j.date', '<=', $reconciliation->period_end)
            ->when($matchedJournalLineIds->isNotEmpty(), fn ($q) => $q->whereNotIn('jl.id', $matchedJournalLineIds))
            ->select([
                'jl.id as journal_line_id',
                'jl.journal_id',
                'j.date',
                'j.description',
                'j.source_type',
                'j.source_id',
                'jl.debit',
                'jl.credit',
                'jl.memo',
            ])
            ->orderBy('j.date')
            ->orderBy('jl.id')
            ->get();
    }

    public function autoMatch(BankReconciliation $reconciliation, int $dateToleranceDays = 3): int
    {
        $this->assertOpen($reconciliation);

        $bookLines = $this->getBookCandidates($reconciliation);
        $bankLines = $reconciliation->statement->lines()->where('match_status', 'unmatched')->get();
        $matched = 0;

        foreach ($bankLines as $bankLine) {
            $candidate = $this->findDeterministicMatch($bankLine, $bookLines, $dateToleranceDays);
            if (! $candidate) {
                continue;
            }

            $this->createMatch($reconciliation, $bankLine, $candidate, 'auto', null);
            $bookLines = $bookLines->reject(fn ($row) => (int) $row->journal_line_id === (int) $candidate->journal_line_id);
            $matched++;
        }

        return $matched;
    }

    public function aiSuggestMatches(BankReconciliation $reconciliation): int
    {
        $this->assertOpen($reconciliation);

        $bankLines = $reconciliation->statement->lines()->where('match_status', 'unmatched')->get();
        $bookLines = $this->getBookCandidates($reconciliation);

        if ($bankLines->isEmpty() || $bookLines->isEmpty()) {
            return 0;
        }

        $model = (string) config('services.bank_reconciliation.model', 'openai/gpt-4o-mini');
        $payload = [
            'bank_lines' => $bankLines->map(fn (BankStatementLine $line) => [
                'id' => $line->id,
                'date' => $line->posting_date->toDateString(),
                'amount' => (float) $line->amount,
                'direction' => $line->direction,
                'description' => $line->description,
                'reference_no' => $line->reference_no,
            ])->values()->all(),
            'book_lines' => $bookLines->map(fn ($line) => [
                'journal_line_id' => $line->journal_line_id,
                'date' => $line->date,
                'amount' => BankReconciliationSupport::bookLineAmount($line),
                'side' => (float) $line->debit > 0 ? 'debit' : 'credit',
                'description' => trim(($line->description ?? '').' '.($line->memo ?? '')),
                'source_type' => $line->source_type,
            ])->values()->all(),
        ];

        $response = $this->aiClient->chatCompletion($model, [
            ['role' => 'system', 'content' => 'Match Indonesian bank statement lines to ERP ledger lines. Return JSON: {"matches":[{"bank_line_id":1,"journal_line_id":2,"confidence":0.95}]}. Only include high-confidence matches. Statement credit maps to book debit; statement debit maps to book credit.'],
            ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)],
        ]);

        $content = data_get($response, 'choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;
        if (! is_array($decoded) || ! isset($decoded['matches']) || ! is_array($decoded['matches'])) {
            return 0;
        }

        $matched = 0;
        foreach ($decoded['matches'] as $match) {
            $bankLineId = (int) ($match['bank_line_id'] ?? 0);
            $journalLineId = (int) ($match['journal_line_id'] ?? 0);
            $confidence = isset($match['confidence']) ? (float) $match['confidence'] : null;

            if ($bankLineId <= 0 || $journalLineId <= 0 || ($confidence !== null && $confidence < 0.7)) {
                continue;
            }

            $bankLine = $bankLines->firstWhere('id', $bankLineId);
            $bookLine = $bookLines->firstWhere('journal_line_id', $journalLineId);
            if (! $bankLine || ! $bookLine) {
                continue;
            }

            if (! $this->amountsCompatible($bankLine, $bookLine)) {
                continue;
            }

            $this->createMatch($reconciliation, $bankLine, $bookLine, 'ai', $confidence);
            $matched++;
        }

        return $matched;
    }

    public function manualMatch(BankReconciliation $reconciliation, int $bankStatementLineId, int $journalLineId): BankReconciliationMatch
    {
        $this->assertOpen($reconciliation);

        $bankLine = $reconciliation->statement->lines()->whereKey($bankStatementLineId)->firstOrFail();
        if ($bankLine->match_status !== 'unmatched') {
            throw new \RuntimeException('Bank statement line is already matched.');
        }

        $bookLine = $this->getBookCandidates($reconciliation)->firstWhere('journal_line_id', $journalLineId);
        if (! $bookLine) {
            throw new \RuntimeException('Selected book line is not available for matching.');
        }

        if (! $this->amountsCompatible($bankLine, $bookLine)) {
            throw new \RuntimeException('Amount or direction does not match.');
        }

        return $this->createMatch($reconciliation, $bankLine, $bookLine, 'manual', null);
    }

    public function createAdjustment(
        BankReconciliation $reconciliation,
        int $bankStatementLineId,
        int $counterAccountId,
        ?string $memo = null,
    ): BankReconciliationMatch {
        $this->assertOpen($reconciliation);

        $bankLine = $reconciliation->statement->lines()->whereKey($bankStatementLineId)->firstOrFail();
        if ($bankLine->match_status !== 'unmatched') {
            throw new \RuntimeException('Bank statement line is already matched.');
        }

        $bankAccountCoaId = (int) $reconciliation->bankAccount?->account_id;
        if ($bankAccountCoaId <= 0) {
            throw new \RuntimeException('Bank account is not linked to a COA account.');
        }

        $amount = (float) $bankLine->amount;
        $lines = [];

        if ($bankLine->direction === 'credit') {
            $lines[] = ['account_id' => $bankAccountCoaId, 'debit' => $amount, 'credit' => 0, 'memo' => $memo ?? $bankLine->description];
            $lines[] = ['account_id' => $counterAccountId, 'debit' => 0, 'credit' => $amount, 'memo' => $memo ?? $bankLine->description];
        } else {
            $lines[] = ['account_id' => $bankAccountCoaId, 'debit' => 0, 'credit' => $amount, 'memo' => $memo ?? $bankLine->description];
            $lines[] = ['account_id' => $counterAccountId, 'debit' => $amount, 'credit' => 0, 'memo' => $memo ?? $bankLine->description];
        }

        $journalId = $this->postingService->postJournal([
            'date' => $bankLine->posting_date->toDateString(),
            'description' => 'Bank reconciliation adjustment: '.($bankLine->description ?: 'Statement line #'.$bankLine->id),
            'source_type' => 'bank_reconciliation',
            'source_id' => $reconciliation->id,
            'posted_by' => Auth::id(),
            'lines' => $lines,
        ]);

        $journalLineId = (int) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $bankAccountCoaId)
            ->orderBy('id')
            ->value('id');

        return $this->createMatch($reconciliation, $bankLine, (object) [
            'journal_line_id' => $journalLineId,
            'journal_id' => $journalId,
        ], 'adjustment', null);
    }

    public function ignoreLine(BankReconciliation $reconciliation, int $bankStatementLineId): void
    {
        $this->assertOpen($reconciliation);

        $bankLine = $reconciliation->statement->lines()->whereKey($bankStatementLineId)->firstOrFail();
        if ($bankLine->match_status !== 'unmatched') {
            throw new \RuntimeException('Only unmatched lines can be ignored.');
        }

        $bankLine->update(['match_status' => 'ignored']);
    }

    public function finalize(BankReconciliation $reconciliation): BankReconciliation
    {
        $this->assertOpen($reconciliation);

        $reconciliation->loadMissing('statement.lines', 'bankAccount.account');
        $unmatched = $reconciliation->statement->lines()->where('match_status', 'unmatched')->count();
        if ($unmatched > 0) {
            throw new \RuntimeException("Cannot finalize while {$unmatched} statement line(s) remain unmatched.");
        }

        $computedClosing = $this->calculateReconciledClosingBalance($reconciliation);
        if (round($computedClosing, 2) !== round((float) $reconciliation->statement_closing, 2)) {
            throw new \RuntimeException(
                'Reconciled book balance does not match statement closing balance. Expected '
                .number_format((float) $reconciliation->statement_closing, 2)
                .', computed '
                .number_format($computedClosing, 2)
            );
        }

        $reconciliation->update([
            'book_balance' => $computedClosing,
            'status' => 'finalized',
            'finalized_by' => Auth::id(),
            'finalized_at' => now(),
        ]);

        $reconciliation->statement->update(['status' => 'reconciled']);

        return $reconciliation->fresh(['bankAccount.account', 'statement.lines', 'matches']);
    }

    public function calculateReconciledClosingBalance(BankReconciliation $reconciliation): float
    {
        $accountId = (int) $reconciliation->bankAccount?->account_id;
        if ($accountId <= 0) {
            return 0.0;
        }

        $opening = $this->openingBalance($accountId, $reconciliation->period_start->toDateString());
        $movement = (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $accountId)
            ->whereNotNull('j.posted_at')
            ->whereDate('j.date', '>=', $reconciliation->period_start)
            ->whereDate('j.date', '<=', $reconciliation->period_end)
            ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) as movement')
            ->value('movement');

        return round($opening + $movement, 2);
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

    private function findDeterministicMatch(BankStatementLine $bankLine, Collection $bookLines, int $dateToleranceDays): ?object
    {
        $targetAmount = round((float) $bankLine->amount, 2);
        $bankDate = $bankLine->posting_date->toDateString();

        $exact = $bookLines->first(function ($bookLine) use ($bankLine, $targetAmount, $bankDate) {
            return $bookLine->date === $bankDate
                && $this->amountsCompatible($bankLine, $bookLine)
                && round(BankReconciliationSupport::bookLineAmount($bookLine), 2) === $targetAmount;
        });

        if ($exact) {
            return $exact;
        }

        return $bookLines->first(function ($bookLine) use ($bankLine, $targetAmount, $bankDate, $dateToleranceDays) {
            if (! $this->amountsCompatible($bankLine, $bookLine)) {
                return false;
            }

            if (round(BankReconciliationSupport::bookLineAmount($bookLine), 2) !== $targetAmount) {
                return false;
            }

            $days = abs(strtotime((string) $bookLine->date) - strtotime($bankDate)) / 86400;

            return $days <= $dateToleranceDays;
        });
    }

    private function amountsCompatible(BankStatementLine $bankLine, object $bookLine): bool
    {
        if (! BankReconciliationSupport::bookLineMatchesStatementDirection($bookLine, $bankLine->direction)) {
            return false;
        }

        return round(BankReconciliationSupport::bookLineAmount($bookLine), 2) === round((float) $bankLine->amount, 2);
    }

    private function createMatch(
        BankReconciliation $reconciliation,
        BankStatementLine $bankLine,
        object $bookLine,
        string $matchType,
        ?float $confidence,
    ): BankReconciliationMatch {
        $match = BankReconciliationMatch::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_statement_line_id' => $bankLine->id,
            'journal_line_id' => $bookLine->journal_line_id ?? null,
            'journal_id' => $bookLine->journal_id ?? null,
            'match_type' => $matchType,
            'amount' => $bankLine->amount,
            'confidence' => $confidence,
            'created_by' => Auth::id(),
        ]);

        $bankLine->update(['match_status' => $matchType === 'adjustment' ? 'adjustment' : 'matched']);

        return $match;
    }

    private function assertOpen(BankReconciliation $reconciliation): void
    {
        if ($reconciliation->status !== 'open') {
            throw new \RuntimeException('Reconciliation session is not open.');
        }
    }
}
