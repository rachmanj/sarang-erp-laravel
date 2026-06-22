<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\SalesReceipt;
use App\Services\Accounting\CashJournalLineBuilder;
use Illuminate\Support\Facades\DB;

class SalesReceiptJournalBuilder
{
    public function build(SalesReceipt $receipt): JournalDraft
    {
        $receipt->loadMissing('lines');

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $total = (float) $receipt->total_amount;

        $lines = CashJournalLineBuilder::buildLines($receipt->lines, 'debit', 'Receipt cash/bank');
        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => 0,
            'credit' => $total,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Settle Accounts Receivable',
        ];

        return new JournalDraft(
            description: 'Post Sales Receipt #'.$receipt->id,
            lines: $lines,
            date: $receipt->date->toDateString(),
        );
    }
}
