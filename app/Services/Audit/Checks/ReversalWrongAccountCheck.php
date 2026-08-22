<?php

namespace App\Services\Audit\Checks;

use App\Services\Audit\AuditCheck;
use App\Services\Audit\AuditCheckResult;
use Illuminate\Support\Facades\DB;

class ReversalWrongAccountCheck extends AuditCheck
{
    public string $key = 'reversal_wrong_account';

    public string $name = 'Reversal Wrong Account';

    public function run(): AuditCheckResult
    {
        $cashAccountIds = DB::table('accounts')
            ->where('code', 'like', '1.1.1%')
            ->pluck('id')
            ->all();

        if ($cashAccountIds === []) {
            return AuditCheckResult::pass();
        }

        $reversals = DB::table('journals')
            ->whereNotNull('posted_at')
            ->where('description', 'like', 'Reversal of #%')
            ->get(['id', 'description']);

        $issues = [];

        foreach ($reversals as $reversal) {
            if (! preg_match('/^Reversal of #(\d+)/', (string) $reversal->description, $matches)) {
                continue;
            }

            $originalJournalId = (int) $matches[1];

            $originalCashCode = $this->creditedCashAccountCode($originalJournalId, $cashAccountIds);
            $reversalCashCode = $this->debitedCashAccountCode((int) $reversal->id, $cashAccountIds);

            if ($originalCashCode === null || $reversalCashCode === null) {
                continue;
            }

            if ($originalCashCode !== $reversalCashCode) {
                $issues[] = "reversal_journal_id={$reversal->id}, original_journal_id={$originalJournalId}, original_cash={$originalCashCode}, reversal_cash={$reversalCashCode}";
            }
        }

        return $issues === [] ? AuditCheckResult::pass() : AuditCheckResult::fail($issues);
    }

    /**
     * @param  array<int>  $cashAccountIds
     */
    private function creditedCashAccountCode(int $journalId, array $cashAccountIds): ?string
    {
        $row = DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.journal_id', $journalId)
            ->whereIn('journal_lines.account_id', $cashAccountIds)
            ->where('journal_lines.credit', '>', 0)
            ->orderBy('journal_lines.credit', 'desc')
            ->select('accounts.code')
            ->first();

        return $row?->code;
    }

    /**
     * @param  array<int>  $cashAccountIds
     */
    private function debitedCashAccountCode(int $journalId, array $cashAccountIds): ?string
    {
        $row = DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.journal_id', $journalId)
            ->whereIn('journal_lines.account_id', $cashAccountIds)
            ->where('journal_lines.debit', '>', 0)
            ->orderBy('journal_lines.debit', 'desc')
            ->select('accounts.code')
            ->first();

        return $row?->code;
    }
}
