<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use App\Models\Bank\MatchGroupBankLine;
use App\Models\Bank\MatchGroupBookLine;
use App\Models\Bank\ReconciliationMatchAudit;
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
        ReconciliationMatchGroup::TYPE_AUTO_REFERENCE,
    ];

    public function __construct(
        private ReconciliationBalanceService $balanceService,
    ) {}

    public function clearAutoGroups(BankReconciliation $reconciliation): void
    {
        $groups = ReconciliationMatchGroup::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereIn('match_type', self::AUTO_TYPES)
            ->get();

        foreach ($groups as $group) {
            $this->unmatchGroup($reconciliation, $group, audit: false);
        }
    }

    public function autoMatch(BankReconciliation $reconciliation): int
    {
        $this->assertEditable($reconciliation);
        $this->clearAutoGroups($reconciliation);

        $matched = 0;
        $matched += $this->matchByReference($reconciliation);
        $matched += $this->matchExact($reconciliation, 1);
        $matched += $this->matchFuzzy($reconciliation, 5);
        $matched += $this->matchSplitManyBookToOneBank($reconciliation, 5, 7);
        $matched += $this->matchSplitManyBankToOneBook($reconciliation, 5, 7);

        if ($matched > 0) {
            ReconciliationMatchAudit::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'action' => ReconciliationMatchAudit::ACTION_AUTO_MATCH,
                'performed_by' => Auth::id(),
                'notes' => "Auto-matched {$matched} group(s)",
            ]);
        }

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

        return DB::transaction(function () use ($reconciliation, $bankLineIds, $bookLineIds) {
            $bankLines = BankStatementLine::query()
                ->where('bank_reconciliation_id', $reconciliation->id)
                ->whereIn('id', $bankLineIds)
                ->lockForUpdate()
                ->get();

            $bookLines = BankBookLine::query()
                ->where('bank_reconciliation_id', $reconciliation->id)
                ->whereIn('id', $bookLineIds)
                ->lockForUpdate()
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
        });
    }

    public function unmatchGroup(
        BankReconciliation $reconciliation,
        ReconciliationMatchGroup $group,
        bool $audit = true,
    ): void {
        $this->assertEditable($reconciliation);

        if ((int) $group->bank_reconciliation_id !== (int) $reconciliation->id) {
            throw new \RuntimeException('Match group does not belong to this session.');
        }

        DB::transaction(function () use ($reconciliation, $group, $audit) {
            $bankLineIds = MatchGroupBankLine::query()
                ->where('reconciliation_match_group_id', $group->id)
                ->pluck('bank_statement_line_id')
                ->all();

            $bookLineIds = MatchGroupBookLine::query()
                ->where('reconciliation_match_group_id', $group->id)
                ->pluck('bank_book_line_id')
                ->all();

            BankStatementLine::query()
                ->whereIn('id', $bankLineIds)
                ->lockForUpdate()
                ->update(['match_status' => BankStatementLine::MATCH_UNMATCHED]);

            BankBookLine::query()
                ->whereIn('id', $bookLineIds)
                ->lockForUpdate()
                ->update(['match_status' => BankBookLine::MATCH_UNMATCHED]);

            if ($audit) {
                ReconciliationMatchAudit::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'reconciliation_match_group_id' => $group->id,
                    'action' => ReconciliationMatchAudit::ACTION_UNMATCH,
                    'match_type' => $group->match_type,
                    'bank_total' => $group->bank_total,
                    'book_total' => $group->book_total,
                    'bank_line_ids' => $bankLineIds,
                    'book_line_ids' => $bookLineIds,
                    'performed_by' => Auth::id(),
                ]);
            }

            $group->delete();
        });
    }

    /**
     * Suggest unmatched book lines for a bank line (amount + date window).
     *
     * @return list<array{book_line_id: int, score: float, reason: string}>
     */
    public function suggestMatches(BankReconciliation $reconciliation, BankStatementLine $bankLine, int $limit = 5): array
    {
        $suggestions = [];

        foreach ($this->availableBookLines($reconciliation) as $bookLine) {
            $score = 0.0;
            $reasons = [];

            if ($this->referencesMatch($bankLine, $bookLine)) {
                $score += 50;
                $reasons[] = 'reference';
            }

            if ($this->amountsAreOpposite($bankLine, $bookLine)) {
                $score += 40;
                $reasons[] = 'amount';
            }

            $days = abs($bankLine->posting_date->diffInDays($bookLine->posting_date ?? $bookLine->doc_date));
            if ($days <= 1) {
                $score += 10;
                $reasons[] = 'date±1';
            } elseif ($days <= 5) {
                $score += 5;
                $reasons[] = 'date±5';
            }

            similar_text(
                strtolower((string) $bankLine->description),
                strtolower((string) $bookLine->description),
                $percent,
            );
            if ($percent >= 40) {
                $score += min(10, $percent / 10);
                $reasons[] = 'desc';
            }

            if ($score < 40) {
                continue;
            }

            $suggestions[] = [
                'book_line_id' => $bookLine->id,
                'score' => round($score, 1),
                'reason' => implode(',', $reasons),
            ];
        }

        usort($suggestions, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($suggestions, 0, $limit);
    }

    private function matchByReference(BankReconciliation $reconciliation): int
    {
        $matched = 0;
        $bankLines = $this->availableBankLines($reconciliation);
        $bookByKey = $this->bucketBookLinesByAmount($this->availableBookLines($reconciliation));

        foreach ($bankLines as $bankLine) {
            $ref = $this->normalizeReference($bankLine->reference_no);
            if ($ref === '') {
                continue;
            }

            $candidates = $this->candidatesForBankLine($bankLine, $bookByKey);
            $best = null;
            $bestScore = -1;

            foreach ($candidates as $bookLine) {
                if (! $this->amountsAreOpposite($bankLine, $bookLine)) {
                    continue;
                }
                if (! $this->referencesMatch($bankLine, $bookLine)) {
                    continue;
                }

                $days = abs($bankLine->posting_date->diffInDays($bookLine->posting_date ?? $bookLine->doc_date));
                $score = 100 - min($days, 30);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $bookLine;
                }
            }

            if (! $best) {
                continue;
            }

            $this->createGroup(
                $reconciliation,
                collect([$bankLine]),
                collect([$best]),
                ReconciliationMatchGroup::TYPE_AUTO_REFERENCE,
                0.95,
                BankStatementLine::MATCH_MATCHED,
                BankBookLine::MATCH_MATCHED,
            );

            $this->removeFromBuckets($bookByKey, $best);
            $matched++;
        }

        return $matched;
    }

    private function matchExact(BankReconciliation $reconciliation, int $dateToleranceDays): int
    {
        return $this->matchByAmountAndDate(
            $reconciliation,
            $dateToleranceDays,
            ReconciliationMatchGroup::TYPE_AUTO_EXACT,
            false,
        );
    }

    private function matchFuzzy(BankReconciliation $reconciliation, int $dateToleranceDays): int
    {
        return $this->matchByAmountAndDate(
            $reconciliation,
            $dateToleranceDays,
            ReconciliationMatchGroup::TYPE_AUTO_FUZZY,
            true,
        );
    }

    private function matchByAmountAndDate(
        BankReconciliation $reconciliation,
        int $dateToleranceDays,
        string $matchType,
        bool $useDescriptionSimilarity,
    ): int {
        $matched = 0;
        $bankLines = $this->availableBankLines($reconciliation);
        $bookByKey = $this->bucketBookLinesByAmount($this->availableBookLines($reconciliation));

        foreach ($bankLines as $bankLine) {
            $candidates = $this->candidatesForBankLine($bankLine, $bookByKey);
            $best = null;
            $bestScore = -1.0;

            foreach ($candidates as $bookLine) {
                if (! $this->amountsAreOpposite($bankLine, $bookLine)) {
                    continue;
                }

                $days = abs($bankLine->posting_date->diffInDays($bookLine->posting_date ?? $bookLine->doc_date));
                if ($days > $dateToleranceDays) {
                    continue;
                }

                $score = 100.0 - $days;

                if ($useDescriptionSimilarity) {
                    similar_text(
                        strtolower((string) $bankLine->description),
                        strtolower((string) $bookLine->description),
                        $percent,
                    );
                    if ($percent < 40) {
                        continue;
                    }
                    $score += $percent;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $bookLine;
                }
            }

            if (! $best) {
                continue;
            }

            $this->createGroup(
                $reconciliation,
                collect([$bankLine]),
                collect([$best]),
                $matchType,
                null,
                BankStatementLine::MATCH_MATCHED,
                BankBookLine::MATCH_MATCHED,
            );

            $this->removeFromBuckets($bookByKey, $best);
            $matched++;
        }

        return $matched;
    }

    private function matchSplitManyBookToOneBank(BankReconciliation $reconciliation, int $maxLines, int $dateToleranceDays): int
    {
        $matched = 0;
        $bookLines = $this->availableBookLines($reconciliation);

        foreach ($this->availableBankLines($reconciliation) as $bankLine) {
            $window = $bookLines
                ->filter(fn (BankBookLine $line) => abs($bankLine->posting_date->diffInDays($line->posting_date ?? $line->doc_date)) <= $dateToleranceDays)
                ->take(20)
                ->values();

            $subset = $this->findSubsetSummingToTarget($window, -1 * $bankLine->netAmount(), $maxLines);
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

            $usedIds = collect($subset)->pluck('id')->all();
            $bookLines = $bookLines->reject(fn (BankBookLine $line) => in_array($line->id, $usedIds, true))->values();
            $matched++;
        }

        return $matched;
    }

    private function matchSplitManyBankToOneBook(BankReconciliation $reconciliation, int $maxLines, int $dateToleranceDays): int
    {
        $matched = 0;
        $bankLines = $this->availableBankLines($reconciliation);

        foreach ($this->availableBookLines($reconciliation) as $bookLine) {
            $window = $bankLines
                ->filter(fn (BankStatementLine $line) => abs($line->posting_date->diffInDays($bookLine->posting_date ?? $bookLine->doc_date)) <= $dateToleranceDays)
                ->take(20)
                ->values();

            $subset = $this->findSubsetSummingToTarget($window, -1 * $bookLine->netAmount(), $maxLines);
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

            $usedIds = collect($subset)->pluck('id')->all();
            $bankLines = $bankLines->reject(fn (BankStatementLine $line) => in_array($line->id, $usedIds, true))->values();
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
            $result = $this->subsetSearch($items, $target, $size, 0, [], 0.0);
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

        $remaining = $size - count($current);
        for ($i = $start; $i <= count($items) - $remaining; $i++) {
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
        return abs(round((float) $bankLine->debit, 2) - round((float) $bookLine->credit, 2)) < ReconciliationBalanceService::TOLERANCE
            && abs(round((float) $bankLine->credit, 2) - round((float) $bookLine->debit, 2)) < ReconciliationBalanceService::TOLERANCE;
    }

    private function referencesMatch(BankStatementLine $bankLine, BankBookLine $bookLine): bool
    {
        $bankRef = $this->normalizeReference($bankLine->reference_no);
        if ($bankRef === '') {
            return false;
        }

        $haystack = strtolower(trim(
            (string) $bookLine->description.' '
            .(string) $bookLine->doc_num.' '
            .(string) $bookLine->ref_doc_num.' '
            .(string) $bookLine->transaction_id
        ));

        return $haystack !== '' && str_contains($haystack, $bankRef);
    }

    private function normalizeReference(?string $reference): string
    {
        return strtolower(trim((string) $reference));
    }

    /**
     * @param  Collection<int, BankBookLine>  $bookLines
     * @return array<string, Collection<int, BankBookLine>>
     */
    private function bucketBookLinesByAmount(Collection $bookLines): array
    {
        $buckets = [];
        foreach ($bookLines as $line) {
            $key = $this->amountBucketKey((float) $line->debit, (float) $line->credit);
            $buckets[$key] ??= collect();
            $buckets[$key]->push($line);
        }

        return $buckets;
    }

    /**
     * @param  array<string, Collection<int, BankBookLine>>  $buckets
     * @return Collection<int, BankBookLine>
     */
    private function candidatesForBankLine(BankStatementLine $bankLine, array $buckets): Collection
    {
        // Opposite sides: bank debit↔book credit, bank credit↔book debit
        $key = $this->amountBucketKey((float) $bankLine->credit, (float) $bankLine->debit);

        return $buckets[$key] ?? collect();
    }

    /**
     * @param  array<string, Collection<int, BankBookLine>>  $buckets
     */
    private function removeFromBuckets(array &$buckets, BankBookLine $bookLine): void
    {
        $key = $this->amountBucketKey((float) $bookLine->debit, (float) $bookLine->credit);
        if (! isset($buckets[$key])) {
            return;
        }

        $buckets[$key] = $buckets[$key]->reject(fn (BankBookLine $line) => $line->id === $bookLine->id)->values();
    }

    private function amountBucketKey(float $debit, float $credit): string
    {
        return number_format(round($debit, 2), 2, '.', '').'|'.number_format(round($credit, 2), 2, '.', '');
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

            $bankIds = [];
            foreach ($bankLines as $bankLine) {
                MatchGroupBankLine::create([
                    'reconciliation_match_group_id' => $group->id,
                    'bank_statement_line_id' => $bankLine->id,
                ]);
                $bankLine->update(['match_status' => $bankMatchStatus]);
                $bankIds[] = $bankLine->id;
            }

            $bookIds = [];
            foreach ($bookLines as $bookLine) {
                MatchGroupBookLine::create([
                    'reconciliation_match_group_id' => $group->id,
                    'bank_book_line_id' => $bookLine->id,
                ]);
                $bookLine->update(['match_status' => $bookMatchStatus]);
                $bookIds[] = $bookLine->id;
            }

            ReconciliationMatchAudit::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'reconciliation_match_group_id' => $group->id,
                'action' => ReconciliationMatchAudit::ACTION_MATCH,
                'match_type' => $matchType,
                'bank_total' => $bankTotal,
                'book_total' => $bookTotal,
                'bank_line_ids' => $bankIds,
                'book_line_ids' => $bookIds,
                'performed_by' => Auth::id(),
            ]);

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
