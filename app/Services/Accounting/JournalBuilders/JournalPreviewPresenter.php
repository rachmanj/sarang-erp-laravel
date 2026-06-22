<?php

namespace App\Services\Accounting\JournalBuilders;

use Illuminate\Support\Facades\DB;

class JournalPreviewPresenter
{
    public function present(JournalDraft $draft): array
    {
        $accountIds = collect($draft->lines)->pluck('account_id')->unique()->filter()->values()->all();
        $accounts = DB::table('accounts')
            ->whereIn('id', $accountIds)
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $lines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($draft->lines as $line) {
            $account = $accounts->get($line['account_id']);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            $totalDebit += $debit;
            $totalCredit += $credit;

            $lines[] = [
                'account_id' => $line['account_id'],
                'account_code' => $account->code ?? 'N/A',
                'account_name' => $account->name ?? 'Unknown',
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        return [
            'journal_number' => 'Auto-generated',
            'date' => $draft->date ?? now()->format('Y-m-d'),
            'description' => $draft->description,
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}
