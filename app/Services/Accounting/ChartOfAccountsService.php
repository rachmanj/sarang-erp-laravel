<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    public function postableAccountId(string $code): int
    {
        $account = DB::table('accounts')->where('code', $code)->first(['id', 'is_postable']);

        if (! $account) {
            throw new \RuntimeException("Account code {$code} not found");
        }

        if ((bool) $account->is_postable) {
            return (int) $account->id;
        }

        $childId = DB::table('accounts')
            ->where('parent_id', $account->id)
            ->where('is_postable', true)
            ->orderBy('code')
            ->value('id');

        if ($childId) {
            return (int) $childId;
        }

        throw new \RuntimeException("Account code {$code} is not postable and has no postable child");
    }

    public function accountIdByCode(string $code): int
    {
        $id = DB::table('accounts')->where('code', $code)->value('id');

        if (! $id) {
            throw new \RuntimeException("Account code {$code} not found");
        }

        return (int) $id;
    }
}
