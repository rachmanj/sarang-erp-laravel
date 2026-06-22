<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;

class CashJournalLineBuilder
{
    /**
     * @param  iterable<int, object|array<string, mixed>>  $paymentLines
     * @return list<array<string, mixed>>
     */
    public static function buildLines(iterable $paymentLines, string $side, string $memo): array
    {
        if (! in_array($side, ['debit', 'credit'], true)) {
            throw new \InvalidArgumentException('Side must be debit or credit.');
        }

        $fallbackAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $grouped = [];

        foreach ($paymentLines as $line) {
            $accountId = (int) (is_array($line) ? ($line['account_id'] ?? 0) : ($line->account_id ?? 0));
            $amount = (float) (is_array($line) ? ($line['amount'] ?? 0) : ($line->amount ?? 0));

            if ($amount <= 0) {
                continue;
            }

            if ($accountId <= 0) {
                $accountId = $fallbackAccountId;
            }

            $grouped[$accountId] = ($grouped[$accountId] ?? 0.0) + $amount;
        }

        if ($grouped === []) {
            throw new \RuntimeException('No cash/bank lines found for posting.');
        }

        $journalLines = [];
        foreach ($grouped as $accountId => $amount) {
            $journalLines[] = [
                'account_id' => $accountId,
                'debit' => $side === 'debit' ? $amount : 0,
                'credit' => $side === 'credit' ? $amount : 0,
                'project_id' => null,
                'fund_id' => null,
                'dept_id' => null,
                'memo' => $memo,
            ];
        }

        return $journalLines;
    }
}
