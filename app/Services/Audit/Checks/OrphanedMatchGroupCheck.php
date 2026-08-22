<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class OrphanedMatchGroupCheck extends AuditCheck
{
    public string $key = 'orphaned_match_group';

    public string $name = 'Orphaned Match Group';

    public function run(): AuditCheckResult
    {
        $bankOnlyIds = DB::table('reconciliation_match_groups as g')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('match_group_bank_lines as b')
                    ->whereColumn('b.reconciliation_match_group_id', 'g.id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('match_group_book_lines as bk')
                    ->whereColumn('bk.reconciliation_match_group_id', 'g.id');
            })
            ->pluck('g.id');

        $bookOnlyIds = DB::table('reconciliation_match_groups as g')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('match_group_book_lines as bk')
                    ->whereColumn('bk.reconciliation_match_group_id', 'g.id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('match_group_bank_lines as b')
                    ->whereColumn('b.reconciliation_match_group_id', 'g.id');
            })
            ->pluck('g.id');

        $issues = [];

        foreach ($bankOnlyIds as $groupId) {
            $issues[] = "group_id={$groupId}, missing_side=book";
        }

        foreach ($bookOnlyIds as $groupId) {
            $issues[] = "group_id={$groupId}, missing_side=bank";
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }
}
