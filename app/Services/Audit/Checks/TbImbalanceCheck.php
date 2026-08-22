<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class TbImbalanceCheck extends AuditCheck
{
    public string $key = 'tb_imbalance';

    public string $name = 'Trial Balance Imbalance';

    public function run(): AuditCheckResult
    {
        $totals = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->whereNotNull('journals.posted_at')
            ->selectRaw('COALESCE(SUM(journal_lines.debit), 0) as total_debit, COALESCE(SUM(journal_lines.credit), 0) as total_credit')
            ->first();

        $imbalance = (float) $totals->total_debit - (float) $totals->total_credit;

        if (abs($imbalance) > 0.02) {
            return AuditCheckResult::fail([
                'Trial balance imbalance: debit minus credit = '.$imbalance,
            ]);
        }

        return AuditCheckResult::pass();
    }
}
