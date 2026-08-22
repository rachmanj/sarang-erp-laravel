<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class NonPostableParentCheck extends AuditCheck
{
    public string $key = 'non_postable_parent';

    public string $name = 'Non-Postable Parent Account';

    public function run(): AuditCheckResult
    {
        $rows = DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->whereNotNull('journals.posted_at')
            ->where('accounts.is_postable', 0)
            ->select('accounts.code', 'journal_lines.journal_id')
            ->distinct()
            ->orderBy('accounts.code')
            ->orderBy('journal_lines.journal_id')
            ->get();

        $issues = [];

        foreach ($rows as $row) {
            $issues[] = "account_code={$row->code}, journal_id={$row->journal_id}";
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
