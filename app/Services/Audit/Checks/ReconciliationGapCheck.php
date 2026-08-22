<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class ReconciliationGapCheck extends AuditCheck
{
    public string $key = 'reconciliation_gap';

    public string $name = 'Reconciliation Gap';

    public function run(): AuditCheckResult
    {
        $rows = DB::table('bank_reconciliations')
            ->whereNotNull('closing_balance_bank')
            ->whereNotNull('closing_balance_book')
            ->whereRaw('ABS(closing_balance_bank - closing_balance_book) > 0.02')
            ->orderBy('id')
            ->get(['id', 'bank_account_id', 'closing_balance_bank', 'closing_balance_book']);

        $issues = [];

        foreach ($rows as $row) {
            $gap = (float) $row->closing_balance_bank - (float) $row->closing_balance_book;
            $issues[] = "id={$row->id}, bank_account_id={$row->bank_account_id}, gap={$gap}";
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
