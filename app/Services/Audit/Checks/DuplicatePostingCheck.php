<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class DuplicatePostingCheck extends AuditCheck
{
    public string $key = 'duplicate_posting';

    public string $name = 'Duplicate Posting';

    public function run(): AuditCheckResult
    {
        $rows = DB::table('journals')
            ->whereNotNull('posted_at')
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', 'not like', '%Reversal%');
            })
            ->groupBy('source_type', 'source_id', 'description')
            ->selectRaw('source_type, source_id, description, COUNT(*) as count')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $issues = [];

        foreach ($rows as $row) {
            $issues[] = "source_type={$row->source_type}, source_id={$row->source_id}, description={$row->description}, count={$row->count}";
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
