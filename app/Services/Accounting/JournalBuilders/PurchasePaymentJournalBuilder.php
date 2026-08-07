<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\PurchasePayment;
use App\Services\Accounting\CashJournalLineBuilder;
use App\Services\Accounting\PaymentRoundingService;
use Illuminate\Support\Facades\DB;

class PurchasePaymentJournalBuilder
{
    public function __construct(private PaymentRoundingService $paymentRoundingService) {}

    public function build(PurchasePayment $payment): JournalDraft
    {
        $payment->loadMissing('lines');

        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id');
        $total = (float) $payment->total_amount;
        $roundingAmount = (float) ($payment->rounding_amount ?? 0);
        $totalCents = (int) round($total * 100);
        $roundingCents = (int) round($roundingAmount * 100);
        $settleAmount = ($totalCents - $roundingCents) / 100;

        $lines = CashJournalLineBuilder::buildLines($payment->lines, 'credit', 'Payment cash/bank');
        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => $settleAmount,
            'credit' => 0,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Settle Accounts Payable',
        ];

        if ($roundingCents !== 0) {
            $roundingAccountId = (int) ($payment->rounding_account_id ?: $this->paymentRoundingService->defaultRoundingAccountId());
            $roundingAbs = abs($roundingCents) / 100;
            $lines[] = [
                'account_id' => $roundingAccountId,
                'debit' => $roundingCents > 0 ? $roundingAbs : 0,
                'credit' => $roundingCents < 0 ? $roundingAbs : 0,
                'project_id' => null,
                'fund_id' => null,
                'dept_id' => null,
                'memo' => $roundingAmount > 0 ? 'Rounding Loss' : 'Rounding Gain',
            ];
        }

        return new JournalDraft(
            description: 'Post Purchase Payment #'.$payment->id,
            lines: $lines,
            date: $payment->date->toDateString(),
        );
    }
}
