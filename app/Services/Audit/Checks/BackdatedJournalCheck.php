<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class BackdatedJournalCheck extends AuditCheck
{
    public string $key = 'backdated_journal';

    public string $name = 'Backdated Journal';

    public function run(): AuditCheckResult
    {
        $rows = DB::table('journals')
            ->whereNotNull('posted_at')
            ->where('date', '<', '2026-01-01')
            ->orderBy('id')
            ->get(['id', 'journal_no', 'date', 'source_type']);

        $issues = [];

        foreach ($rows as $row) {
            $issues[] = "id={$row->id}, journal_no={$row->journal_no}, date={$row->date}, source_type={$row->source_type}";
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
