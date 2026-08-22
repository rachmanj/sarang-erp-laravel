<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class JournalImbalanceCheck extends AuditCheck
{
    public string $key = 'journal_imbalance';

    public string $name = 'Journal Imbalance';

    public function run(): AuditCheckResult
    {
        $rows = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->whereNotNull('journals.posted_at')
            ->groupBy('journals.id', 'journals.journal_no')
            ->selectRaw('journals.id as journal_id, journals.journal_no, COALESCE(SUM(journal_lines.debit), 0) as total_debit, COALESCE(SUM(journal_lines.credit), 0) as total_credit')
            ->get();

        $issues = [];

        foreach ($rows as $row) {
            $diff = (float) $row->total_debit - (float) $row->total_credit;

            if (abs($diff) > 0.02) {
                $issues[] = "journal_id={$row->journal_id}, journal_no={$row->journal_no}, imbalance={$diff}";
            }
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
