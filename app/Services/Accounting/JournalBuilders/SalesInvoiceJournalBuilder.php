<?php

namespace App\Services\Accounting\JournalBuilders;

use App\Models\Accounting\SalesInvoice;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Support\Facades\DB;

class SalesInvoiceJournalBuilder
{
    public function build(SalesInvoice $invoice): JournalDraft
    {
        $invoice->loadMissing('lines');

        $arUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '1.1.2.04')->value('id');
        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $ppnOutputId = (int) DB::table('accounts')->where('code', '2.1.2.01')->value('id');

        $totals = SalesInvoicePostingMath::summarizeLinesForPosting($invoice->lines);
        $grossTotal = $totals['gross_total'];
        $headerDiscount = round((float) ($invoice->discount_amount ?? 0), 2);
        $arAmount = round($grossTotal - $headerDiscount, 2);
        if ($arAmount < 0) {
            $arAmount = 0;
        }
        $ppnTotal = $totals['ppn_total'];
        $wtaxTotal = $totals['wtax_total'] ?? 0.0;
        $ppnByRevenueAccount = $totals['ppn_by_revenue_account'];
        $wtaxPrepaidId = (int) DB::table('accounts')->where('code', '1.1.4.02')->value('id');
        $arUnInvoiceCredit = round($arAmount + $wtaxTotal, 2);
        $lines = [];

        if ($invoice->is_opening_balance) {
            $retainedEarningsAccountId = (int) DB::table('accounts')->where('code', '3.3.1')->value('id');

            if (! $retainedEarningsAccountId) {
                throw new \Exception('Retained Earnings Opening Balance account (3.3.1) not found. Please ensure this account exists in the chart of accounts.');
            }

            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $arAmount,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Accounts Receivable - Opening Balance',
            ];

            $lines[] = [
                'account_id' => $retainedEarningsAccountId,
                'debit' => 0,
                'credit' => round($arAmount - $ppnTotal, 2),
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Saldo Awal Laba Ditahan - Opening Balance',
            ];

            if ($ppnTotal > 0) {
                $lines[] = [
                    'account_id' => $ppnOutputId,
                    'debit' => 0,
                    'credit' => $ppnTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'PPN Keluaran',
                ];
            }

            $description = 'Post AR Invoice (Opening Balance) #'.$invoice->invoice_no;
        } else {
            $lines[] = [
                'account_id' => $arUnInvoiceAccountId,
                'debit' => 0,
                'credit' => $arUnInvoiceCredit,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Reduce AR UnInvoice - convert to invoiced AR',
            ];

            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $arAmount,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Accounts Receivable',
            ];

            if ($ppnTotal > 0) {
                foreach ($ppnByRevenueAccount as $revenueAccountId => $ppnPart) {
                    if ($ppnPart <= 0) {
                        continue;
                    }
                    $lines[] = [
                        'account_id' => (int) $revenueAccountId,
                        'debit' => $ppnPart,
                        'credit' => 0,
                        'project_id' => null,
                        'dept_id' => null,
                        'memo' => 'Reclass VAT from revenue (DO gross → PPN)',
                    ];
                }

                $lines[] = [
                    'account_id' => $ppnOutputId,
                    'debit' => 0,
                    'credit' => $ppnTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'PPN Keluaran',
                ];
            }

            if ($wtaxTotal > 0 && $wtaxPrepaidId) {
                $lines[] = [
                    'account_id' => $wtaxPrepaidId,
                    'debit' => $wtaxTotal,
                    'credit' => 0,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'PPh 23 Dibayar Dimuka (customer withholding)',
                ];
            }

            $description = 'Post AR Invoice #'.$invoice->invoice_no;
        }

        return new JournalDraft(
            description: $description,
            lines: $lines,
            date: $invoice->date->toDateString(),
        );
    }
}
