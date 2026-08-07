<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\SalesReceipt;
use App\Services\Accounting\CashJournalLineBuilder;
use App\Services\Accounting\PaymentRoundingService;
use Illuminate\Support\Facades\DB;

class SalesReceiptJournalBuilder
{
    public function __construct(private PaymentRoundingService $paymentRoundingService) {}

    public function build(SalesReceipt $receipt): JournalDraft
    {
        $receipt->loadMissing('lines');

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $total = (float) $receipt->total_amount;
        $roundingAmount = (float) ($receipt->rounding_amount ?? 0);
        $totalCents = (int) round($total * 100);
        $roundingCents = (int) round($roundingAmount * 100);
        $settleAmount = ($totalCents - $roundingCents) / 100;

        $lines = CashJournalLineBuilder::buildLines($receipt->lines, 'debit', 'Receipt cash/bank');
        $lines[] = [
            'account_id' => $arAccountId,
            'debit' => 0,
            'credit' => $settleAmount,
            'project_id' => null,
            'fund_id' => null,
            'dept_id' => null,
            'memo' => 'Settle Accounts Receivable',
        ];

        if ($roundingCents !== 0) {
            $roundingAccountId = (int) ($receipt->rounding_account_id ?: $this->paymentRoundingService->defaultRoundingAccountId());
            $roundingAbs = abs($roundingCents) / 100;
            $lines[] = [
                'account_id' => $roundingAccountId,
                'debit' => $roundingCents < 0 ? $roundingAbs : 0,
                'credit' => $roundingCents > 0 ? $roundingAbs : 0,
                'project_id' => null,
                'fund_id' => null,
                'dept_id' => null,
                'memo' => $roundingAmount > 0 ? 'Rounding Gain' : 'Rounding Loss',
            ];
        }

        return new JournalDraft(
            description: 'Post Sales Receipt #'.$receipt->id,
            lines: $lines,
            date: $receipt->date->toDateString(),
        );
    }
}
