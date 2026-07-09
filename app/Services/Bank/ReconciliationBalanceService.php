<?php

namespace App\Services\Bank;

use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;

class ReconciliationBalanceService
{
    public const TOLERANCE = 0.005;

    public function bankNet(BankReconciliation $reconciliation): float
    {
        return (float) BankStatementLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', '!=', BankStatementLine::MATCH_EXCLUDED)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net');
    }

    public function bookNet(BankReconciliation $reconciliation): float
    {
        return (float) BankBookLine::query()
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->where('match_status', '!=', BankBookLine::MATCH_EXCLUDED)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net');
    }

    public function difference(BankReconciliation $reconciliation): float
    {
        return round($this->bankNet($reconciliation) + $this->bookNet($reconciliation), 2);
    }

    public function isBalanced(BankReconciliation $reconciliation): bool
    {
        return abs($this->difference($reconciliation)) < self::TOLERANCE;
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
        $difference = round($bankNet + $bookNet, 2);

        return [
            'status' => $reconciliation->status,
            'bank_lines_count' => $reconciliation->bankLines()->count(),
            'book_lines_count' => $reconciliation->bookLines()->count(),
            'match_groups_count' => $reconciliation->matchGroups()->count(),
            'bank_net' => round($bankNet, 2),
            'book_net' => round($bookNet, 2),
            'difference' => $difference,
            'is_balanced' => abs($difference) < self::TOLERANCE,
        ];
    }
}
