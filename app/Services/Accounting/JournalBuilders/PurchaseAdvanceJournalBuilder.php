<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\PurchaseAdvance;
use App\Services\Accounting\CashJournalLineBuilder;
use Illuminate\Support\Facades\DB;

class PurchaseAdvanceJournalBuilder
{
    public function build(PurchaseAdvance $advance): JournalDraft
    {
        $advance->loadMissing('lines');

        $advanceAccountId = (int) DB::table('accounts')->where('code', '1.1.6.01')->value('id');
        if ($advanceAccountId <= 0) {
            throw new \RuntimeException('Akun uang muka pembelian (1.1.6.01) tidak ditemukan.');
        }

        $lineTotal = 0.0;
        foreach ($advance->lines as $line) {
            $lineTotal += (float) $line->amount;
        }

        $totalAmount = (float) $advance->total_amount;
        if (abs($lineTotal - $totalAmount) > 0.005) {
            throw new \RuntimeException(
                sprintf(
                    'Total baris kas/bank (%.2f) tidak sama dengan total dokumen (%.2f).',
                    $lineTotal,
                    $totalAmount
                )
            );
        }

        $cashLines = CashJournalLineBuilder::buildLines($advance->lines, 'credit', 'Uang muka pembelian');

        $lines = [
            [
                'account_id' => $advanceAccountId,
                'debit' => $totalAmount,
                'credit' => 0,
                'project_id' => null,
                'fund_id' => null,
                'dept_id' => null,
                'memo' => 'Uang muka pembelian',
            ],
            ...$cashLines,
        ];

        return new JournalDraft(
            description: 'Post Purchase Advance #'.$advance->id,
            lines: $lines,
            date: $advance->date->toDateString(),
        );
    }
}
