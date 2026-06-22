<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\PurchasePayment;
use App\Services\Accounting\CashJournalLineBuilder;
use Illuminate\Support\Facades\DB;

class PurchasePaymentJournalBuilder
{
    public function build(PurchasePayment $payment): JournalDraft
    {
        $payment->loadMissing('lines');

        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id');
        $total = (float) $payment->total_amount;

        $lines = CashJournalLineBuilder::buildLines($payment->lines, 'credit', 'Payment cash/bank');
        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => $total,
            'credit' => 0,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Settle Accounts Payable',
        ];

        return new JournalDraft(
            description: 'Post Purchase Payment #'.$payment->id,
            lines: $lines,
            date: $payment->date->toDateString(),
        );
    }
}
