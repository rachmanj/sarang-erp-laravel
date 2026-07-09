<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use App\Models\Bank\MatchGroupBankLine;
use App\Models\Bank\MatchGroupBookLine;
use App\Models\Bank\ReconciliationMatchGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReconciliationMatchingService
{
    private const AUTO_TYPES = [
        ReconciliationMatchGroup::TYPE_AUTO_EXACT,
        ReconciliationMatchGroup::TYPE_AUTO_FUZZY,
        ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
    ];

    public function __construct(
        private ReconciliationBalanceService $balanceService,
        private BankReconciliationOpenRouterClient $aiClient,
    ) {}

    public function clearAutoGroups(BankReconciliation $reconciliation): void
    {
        $groups = ReconciliationMatchGroup::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('match_type', self::AUTO_TYPES)
            ->get();

        foreach ($groups as $group) {
            $this->unmatchGroup($reconciliation, $group);
        }
    }

    public function autoMatch(BankReconciliation $reconciliation): int
    {
        $this->assertEditable($reconciliation);
        $this->clearAutoGroups($reconciliation);

        $matched = 0;
        $matched += $this->matchExact($reconciliation, 1);
        $matched += $this->matchFuzzy($reconciliation, 5);
        $matched += $this->matchSplitManyBookToOneBank($reconciliation, 5, 7);
        $matched += $this->matchSplitManyBankToOneBook($reconciliation, 5, 7);

        return $matched;
    }

    /**
     * @param  list<int>  $bankLineIds
     * @param  list<int>  $bookLineIds
     */
    public function manualMatch(
        BankReconciliation $reconciliation,
        array $bankLineIds,
        array $bookLineIds,
    ): ReconciliationMatchGroup {
        $this->assertEditable($reconciliation);

        if ($bankLineIds === [] || $bookLineIds === []) {
            throw new \RuntimeException('Select at least one bank line and one book line.');
        }

        $bankLines = BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('id', $bankLineIds)
            ->get();

        $bookLines = BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('id', $bookLineIds)
            ->get();

        if ($bankLines->count() !== count($bankLineIds) || $bookLines->count() !== count($bookLineIds)) {
            throw new \RuntimeException('One or more selected lines are invalid.');
        }

        foreach ($bankLines as $line) {
            if (! $line->isAvailableForMatching()) {
                throw new \RuntimeException('Bank line #'.$line->id.' is not available for matching.');
            }
        }

        foreach ($bookLines as $line) {
            if (! $line->isAvailableForMatching()) {
                throw new \RuntimeException('Book line #'.$line->id.' is not available for matching.');
            }
        }

        $bankTotal = round($bankLines->sum(fn (BankStatementLine $line) => $line->netAmount()), 2);
        $bookTotal = round($bookLines->sum(fn (BankBookLine $line) => $line->netAmount()), 2);

        if (! $this->balanceService->totalsAreBalanced($bankTotal, $bookTotal)) {
            throw new \RuntimeException(
                'Match group is not balanced. Bank net '
                .number_format($bankTotal, 2)
                .' + book net '
                .number_format($bookTotal, 2)
                .' must equal zero.'
            );
        }

        return $this->createGroup(
            $reconciliation,
            $bankLines,
            $bookLines,
            ReconciliationMatchGroup::TYPE_MANUAL,
            null,
            BankStatementLine::MATCH_MANUAL,
            BankBookLine::MATCH_MANUAL,
        );
    }

    public function unmatchGroup(BankReconciliation $reconciliation, ReconciliationMatchGroup $group): void
    {
        $this->assertEditable($reconciliation);

        if ((int) $group->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Match group does not belong to this session.');
        }

        DB::transaction(function () use ($group) {
            $bankLineIds = MatchGroupBankLine::query()
                ->where('reconciliation_match_group_id', $group->id)
                ->pluck('bank_statement_line_id');

            $bookLineIds = MatchGroupBookLine::query()
                ->where('reconciliation_match_group_id', $group->id)
                ->pluck('bank_book_line_id');

            BankStatementLine::query()->whereIn('id', $bankLineIds)->update([
                'match_status' => BankStatementLine::MATCH_UNMATCHED,
            ]);

            BankBookLine::query()->whereIn('id', $bookLineIds)->update([
                'match_status' => BankBookLine::MATCH_UNMATCHED,
            ]);

            $group->delete();
        });
    }

    private function matchExact(BankReconciliation $reconciliation, int $dateToleranceDays): int
    {
        return $this->matchByAmountAndDate($reconciliation, $dateToleranceDays, ReconciliationMatchGroup::TYPE_AUTO_EXACT, false);
    }

    private function matchFuzzy(BankReconciliation $reconciliation, int $dateToleranceDays): int
    {
        return $this->matchByAmountAndDate($reconciliation, $dateToleranceDays, ReconciliationMatchGroup::TYPE_AUTO_FUZZY, true);
    }

    private function matchByAmountAndDate(
        BankReconciliation $reconciliation,
        int $dateToleranceDays,
        string $matchType,
        bool $useDescriptionSimilarity,
    ): int {
        $matched = 0;
        $bankLines = $this->availableBankLines($reconciliation);
        $bookLines = $this->availableBookLines($reconciliation);

        foreach ($bankLines as $bankLine) {
            $candidate = $bookLines->first(function (BankBookLine $bookLine) use ($bankLine, $dateToleranceDays, $useDescriptionSimilarity) {
                if (! $this->amountsAreOpposite($bankLine, $bookLine)) {
                    return false;
                }

                $days = abs($bankLine->posting_date->diffInDays($bookLine->posting_date ?? $bookLine->doc_date));
                if ($days > $dateToleranceDays) {
                    return false;
                }

                if (! $useDescriptionSimilarity) {
                    return true;
                }

                similar_text(
                    strtolower((string) $bankLine->description),
                    strtolower((string) $bookLine->description),
                    $percent,
                );

                return $percent >= 40;
            });

            if (! $candidate) {
                continue;
            }

            $this->createGroup(
                $reconciliation,
                collect([$bankLine]),
                collect([$candidate]),
                $matchType,
                null,
                BankStatementLine::MATCH_MATCHED,
                BankBookLine::MATCH_MATCHED,
            );

            $bookLines = $bookLines->reject(fn (BankBookLine $line) => $line->id === $candidate->id);
            $matched++;
        }

        return $matched;
    }

    private function matchSplitManyBookToOneBank(BankReconciliation $reconciliation, int $maxLines, int $dateToleranceDays): int
    {
        $matched = 0;

        foreach ($this->availableBankLines($reconciliation) as $bankLine) {
            $bookLines = $this->availableBookLines($reconciliation)
                ->filter(fn (BankBookLine $line) => abs($bankLine->posting_date->diffInDays($line->posting_date ?? $line->doc_date)) <= $dateToleranceDays)
                ->take(20);

            $subset = $this->findSubsetSummingToTarget($bookLines, -1 * $bankLine->netAmount(), $maxLines);
            if ($subset === null) {
                continue;
            }

            $this->createGroup(
                $reconciliation,
                collect([$bankLine]),
                collect($subset),
                ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
                null,
                BankStatementLine::MATCH_MATCHED,
                BankBookLine::MATCH_MATCHED,
            );

            $matched++;
        }

        return $matched;
    }

    private function matchSplitManyBankToOneBook(BankReconciliation $reconciliation, int $maxLines, int $dateToleranceDays): int
    {
        $matched = 0;

        foreach ($this->availableBookLines($reconciliation) as $bookLine) {
            $bankLines = $this->availableBankLines($reconciliation)
                ->filter(fn (BankStatementLine $line) => abs($line->posting_date->diffInDays($bookLine->posting_date ?? $bookLine->doc_date)) <= $dateToleranceDays)
                ->take(20);

            $subset = $this->findSubsetSummingToTarget($bankLines, -1 * $bookLine->netAmount(), $maxLines);
            if ($subset === null) {
                continue;
            }

            $this->createGroup(
                $reconciliation,
                collect($subset),
                collect([$bookLine]),
                ReconciliationMatchGroup::TYPE_AUTO_SPLIT,
                null,
                BankStatementLine::MATCH_MATCHED,
                BankBookLine::MATCH_MATCHED,
            );

            $matched++;
        }

        return $matched;
    }

    /**
     * @param  Collection<int, BankStatementLine|BankBookLine>  $lines
     * @return list<BankStatementLine|BankBookLine>|null
     */
    private function findSubsetSummingToTarget(Collection $lines, float $target, int $maxSize): ?array
    {
        $items = $lines->values()->all();
        $count = count($items);

        for ($size = 2; $size <= min($maxSize, $count); $size++) {
            $result = $this->subsetSearch($items, $target, $size, 0, [], 0);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param  list<BankStatementLine|BankBookLine>  $items
     * @param  list<BankStatementLine|BankBookLine>  $current
     * @return list<BankStatementLine|BankBookLine>|null
     */
    private function subsetSearch(array $items, float $target, int $size, int $start, array $current, float $sum): ?array
    {
        if (count($current) === $size) {
            return abs($sum - $target) < ReconciliationBalanceService::TOLERANCE ? $current : null;
        }

        for ($i = $start; $i < count($items); $i++) {
            $current[] = $items[$i];
            $found = $this->subsetSearch($items, $target, $size, $i + 1, $current, $sum + $items[$i]->netAmount());
            if ($found !== null) {
                return $found;
            }
            array_pop($current);
        }

        return null;
    }

    private function amountsAreOpposite(BankStatementLine $bankLine, BankBookLine $bookLine): bool
    {
        return abs(round($bankLine->debit, 2) - round($bookLine->credit, 2)) < ReconciliationBalanceService::TOLERANCE
            && abs(round($bankLine->credit, 2) - round($bookLine->debit, 2)) < ReconciliationBalanceService::TOLERANCE;
    }

    /**
     * @param  Collection<int, BankStatementLine>  $bankLines
     * @param  Collection<int, BankBookLine>  $bookLines
     */
    private function createGroup(
        BankReconciliation $reconciliation,
        Collection $bankLines,
        Collection $bookLines,
        string $matchType,
        ?float $confidence,
        string $bankMatchStatus,
        string $bookMatchStatus,
    ): ReconciliationMatchGroup {
        $bankTotal = round($bankLines->sum(fn (BankStatementLine $line) => $line->netAmount()), 2);
        $bookTotal = round($bookLines->sum(fn (BankBookLine $line) => $line->netAmount()), 2);

        return DB::transaction(function () use (
            $reconciliation,
            $bankLines,
            $bookLines,
            $matchType,
            $confidence,
            $bankMatchStatus,
            $bookMatchStatus,
            $bankTotal,
            $bookTotal,
        ) {
            $group = ReconciliationMatchGroup::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'match_type' => $matchType,
                'confidence_score' => $confidence,
                'bank_total' => $bankTotal,
                'book_total' => $bookTotal,
                'difference' => round($bankTotal + $bookTotal, 2),
                'created_by' => Auth::id(),
            ]);

            foreach ($bankLines as $bankLine) {
                MatchGroupBankLine::create([
                    'reconciliation_match_group_id' => $group->id,
                    'bank_statement_line_id' => $bankLine->id,
                ]);
                $bankLine->update(['match_status' => $bankMatchStatus]);
            }

            foreach ($bookLines as $bookLine) {
                MatchGroupBookLine::create([
                    'reconciliation_match_group_id' => $group->id,
                    'bank_book_line_id' => $bookLine->id,
                ]);
                $bookLine->update(['match_status' => $bookMatchStatus]);
            }

            return $group->fresh(['bankLines', 'bookLines']);
        });
    }

    /** @return Collection<int, BankStatementLine> */
    private function availableBankLines(BankReconciliation $reconciliation): Collection
    {
        return BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankStatementLine::MATCH_UNMATCHED)
            ->orderBy('posting_date')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, BankBookLine> */
    private function availableBookLines(BankReconciliation $reconciliation): Collection
    {
        return BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankBookLine::MATCH_UNMATCHED)
            ->orderBy('posting_date')
            ->orderBy('id')
            ->get();
    }

    private function assertEditable(BankReconciliation $reconciliation): void
    {
        if ($reconciliation->isLockedForEditing()) {
            throw new \RuntimeException('Reconciliation session is locked.');
        }

        if ($reconciliation->status === BankReconciliation::STATUS_PROCESSING) {
            throw new \RuntimeException('Reconciliation session is still processing.');
        }
    }
}
