<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;

class ReconciliationBalanceService
{
    public const TOLERANCE = 0.005;

    /**
     * Bank net of lines that participate in the clear-to-zero check
     * (matched / unmatched — excludes excluded + outstanding).
     */
    public function bankNet(BankReconciliation $reconciliation): float
    {
        return (float) BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereNotIn('match_status', [
                BankStatementLine::MATCH_EXCLUDED,
                BankStatementLine::MATCH_OUTSTANDING,
            ])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net');
    }

    /**
     * Book net of lines that participate in the clear-to-zero check.
     */
    public function bookNet(BankReconciliation $reconciliation): float
    {
        return (float) BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->whereNotIn('match_status', [
                BankBookLine::MATCH_EXCLUDED,
                BankBookLine::MATCH_OUTSTANDING,
            ])
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net');
    }

    public function difference(BankReconciliation $reconciliation): float
    {
        return round($this->bankNet($reconciliation) + $this->bookNet($reconciliation), 2);
    }

    /**
     * Deposits in transit: outstanding book debits (recorded in books, not yet on statement).
     */
    public function depositsInTransit(BankReconciliation $reconciliation): float
    {
        return round((float) BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankBookLine::MATCH_OUTSTANDING)
            ->selectRaw('COALESCE(SUM(debit), 0) as total')
            ->value('total'), 2);
    }

    /**
     * Outstanding checks / payments: outstanding book credits.
     */
    public function outstandingChecks(BankReconciliation $reconciliation): float
    {
        return round((float) BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankBookLine::MATCH_OUTSTANDING)
            ->selectRaw('COALESCE(SUM(credit), 0) as total')
            ->value('total'), 2);
    }

    /**
     * Outstanding bank lines net (unusual; typically should be adjusted via journal).
     */
    public function outstandingBankNet(BankReconciliation $reconciliation): float
    {
        return round((float) BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankStatementLine::MATCH_OUTSTANDING)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net'), 2);
    }

    public function statementOpening(BankReconciliation $reconciliation): float
    {
        return round((float) ($reconciliation->opening_balance_bank
            ?? $reconciliation->statement_opening
            ?? 0), 2);
    }

    public function statementClosing(BankReconciliation $reconciliation): float
    {
        return round((float) ($reconciliation->closing_balance_bank
            ?? $reconciliation->statement_closing
            ?? 0), 2);
    }

    public function bookClosing(BankReconciliation $reconciliation): float
    {
        return round((float) ($reconciliation->closing_balance_book
            ?? $reconciliation->book_balance
            ?? 0), 2);
    }

    /**
     * Standard identity:
     * statement_closing + deposits_in_transit - outstanding_checks ≈ book_closing
     * (outstanding bank net adjusts statement side when present).
     */
    public function adjustedStatementBalance(BankReconciliation $reconciliation): float
    {
        return round(
            $this->statementClosing($reconciliation)
            + $this->depositsInTransit($reconciliation)
            - $this->outstandingChecks($reconciliation)
            - $this->outstandingBankNet($reconciliation),
            2
        );
    }

    public function reconciliationDifference(BankReconciliation $reconciliation): float
    {
        return round(
            $this->adjustedStatementBalance($reconciliation) - $this->bookClosing($reconciliation),
            2
        );
    }

    public function hasStatementBalances(BankReconciliation $reconciliation): bool
    {
        $opening = $reconciliation->opening_balance_bank ?? $reconciliation->statement_opening;
        $closing = $reconciliation->closing_balance_bank ?? $reconciliation->statement_closing;

        return $opening !== null && $closing !== null
            && (abs((float) $opening) > self::TOLERANCE || abs((float) $closing) > self::TOLERANCE
                || $reconciliation->bankLines()->exists());
    }

    /**
     * Cross-foot: sum(bank line nets) should equal statement_closing - statement_opening.
     *
     * @return array{valid: bool, expected_movement: float, actual_movement: float, difference: float}|null
     */
    public function statementCrossFoot(BankReconciliation $reconciliation): ?array
    {
        if (! $this->hasStatementBalances($reconciliation)) {
            return null;
        }

        $opening = $this->statementOpening($reconciliation);
        $closing = $this->statementClosing($reconciliation);
        $expected = round($closing - $opening, 2);

        // Bank statement perspective: credit increases balance, debit decreases.
        // Our stored debit/credit follow bank statement convention, so movement =
        // sum(credit - debit) = -sum(debit - credit).
        $actual = round(-1 * (float) BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', '!=', BankStatementLine::MATCH_EXCLUDED)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net'), 2);

        $difference = round($actual - $expected, 2);

        return [
            'valid' => abs($difference) < self::TOLERANCE,
            'expected_movement' => $expected,
            'actual_movement' => $actual,
            'difference' => $difference,
        ];
    }

    public function unmatchedBankCount(BankReconciliation $reconciliation): int
    {
        return BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankStatementLine::MATCH_UNMATCHED)
            ->count();
    }

    public function unmatchedBookCount(BankReconciliation $reconciliation): int
    {
        return BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', BankBookLine::MATCH_UNMATCHED)
            ->count();
    }

    /**
     * Balanced when:
     * 1. No unmatched lines remain (all matched / excluded / outstanding)
     * 2. Cleared nets cancel (bankNet + bookNet ≈ 0)
     * 3. When statement balances exist: adjusted statement ≈ book closing
     * 4. When statement balances exist: cross-foot is valid (or only soft-warn when zero balances)
     */
    public function isBalanced(BankReconciliation $reconciliation): bool
    {
        if ($this->unmatchedBankCount($reconciliation) > 0 || $this->unmatchedBookCount($reconciliation) > 0) {
            return false;
        }

        if (abs($this->difference($reconciliation)) >= self::TOLERANCE) {
            return false;
        }

        if ($this->hasStatementBalances($reconciliation)
            && abs((float) ($reconciliation->closing_balance_book ?? 0)) + abs((float) ($reconciliation->opening_balance_book ?? 0)) > self::TOLERANCE
        ) {
            if (abs($this->reconciliationDifference($reconciliation)) >= self::TOLERANCE) {
                return false;
            }

            $crossFoot = $this->statementCrossFoot($reconciliation);
            if ($crossFoot !== null && ! $crossFoot['valid']) {
                return false;
            }
        }

        return true;
    }

    public function totalsAreBalanced(float $bankTotal, float $bookTotal): bool
    {
        return abs($bankTotal + $bookTotal) < self::TOLERANCE;
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(BankReconciliation $reconciliation): array
    {
        $bankNet = $this->bankNet($reconciliation);
        $bookNet = $this->bookNet($reconciliation);
        $clearedDifference = round($bankNet + $bookNet, 2);
        $depositsInTransit = $this->depositsInTransit($reconciliation);
        $outstandingChecks = $this->outstandingChecks($reconciliation);
        $statementClosing = $this->statementClosing($reconciliation);
        $bookClosing = $this->bookClosing($reconciliation);
        $adjustedStatement = $this->adjustedStatementBalance($reconciliation);
        $reconDifference = round($adjustedStatement - $bookClosing, 2);
        $crossFoot = $this->statementCrossFoot($reconciliation);
        $isBalanced = $this->isBalanced($reconciliation);

        return [
            'status' => $reconciliation->status,
            'bank_lines_count' => $reconciliation->bankLines()->count(),
            'book_lines_count' => $reconciliation->bookLines()->count(),
            'match_groups_count' => $reconciliation->matchGroups()->count(),
            'unmatched_bank_count' => $this->unmatchedBankCount($reconciliation),
            'unmatched_book_count' => $this->unmatchedBookCount($reconciliation),
            'bank_net' => round($bankNet, 2),
            'book_net' => round($bookNet, 2),
            'difference' => $clearedDifference,
            'statement_opening' => $this->statementOpening($reconciliation),
            'statement_closing' => $statementClosing,
            'book_opening' => round((float) ($reconciliation->opening_balance_book ?? 0), 2),
            'book_closing' => $bookClosing,
            'deposits_in_transit' => $depositsInTransit,
            'outstanding_checks' => $outstandingChecks,
            'outstanding_bank_net' => $this->outstandingBankNet($reconciliation),
            'adjusted_statement_balance' => $adjustedStatement,
            'reconciliation_difference' => $reconDifference,
            'cross_foot' => $crossFoot,
            'has_statement_balances' => $this->hasStatementBalances($reconciliation),
            'is_balanced' => $isBalanced,
        ];
    }
}
