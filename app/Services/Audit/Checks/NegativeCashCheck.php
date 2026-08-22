<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class NegativeCashCheck extends AuditCheck
{
    public string $key = 'negative_cash';

    public string $name = 'Negative Cash Balance';

    public function run(): AuditCheckResult
    {
        $rows = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereNotNull('journals.posted_at')
            ->where('accounts.code', 'like', '1.1.1%')
            ->groupBy('accounts.id', 'accounts.code')
            ->selectRaw('accounts.code, COALESCE(SUM(journal_lines.credit), 0) - COALESCE(SUM(journal_lines.debit), 0) as balance')
            ->havingRaw('COALESCE(SUM(journal_lines.credit), 0) - COALESCE(SUM(journal_lines.debit), 0) < 0')
            ->orderBy('accounts.code')
            ->get();

        $issues = [];

        foreach ($rows as $row) {
            $issues[] = "code={$row->code}, balance={$row->balance}";
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
