<?php

namespace App\Console\Commands;

use App\Models\Accounting\SalesInvoice;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidatePostedSalesInvoiceJournalsCommand extends Command
{
    protected $signature = 'sales-invoices:validate-posted-journals
                            {--id= : Only validate this sales_invoices.id}
                            {--limit= : Max invoices to scan when --id is omitted}';

    protected $description = 'Check posted sales invoice journals against line amounts and expected PPN / AR splits';

    private const TOLERANCE = 0.02;

    public function handle(): int
    {
        $accountIds = [
            'ar_uninvoice' => (int) DB::table('accounts')->where('code', '1.1.2.04')->value('id'),
            'ar' => (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id'),
            'ppn' => (int) DB::table('accounts')->where('code', '2.1.2')->value('id'),
            'retained_opening' => (int) DB::table('accounts')->where('code', '3.3.1')->value('id'),
        ];

        foreach (['ar_uninvoice', 'ar', 'ppn'] as $key) {
            if ($accountIds[$key] < 1) {
                $this->error("Missing chart account for key \"{$key}\" (required: 1.1.2.04, 1.1.2.01, 2.1.2).");

                return self::FAILURE;
            }
        }

        $query = SalesInvoice::query()
            ->where('status', 'posted')
            ->with('lines')
            ->orderBy('id');

        if ($this->option('id') !== null && $this->option('id') !== '') {
            $query->where('id', (int) $this->option('id'));
        }

        if ($this->option('limit') !== null && $this->option('limit') !== '') {
            $query->limit((int) $this->option('limit'));
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->warn('No posted sales invoices matched the query.');

            return self::SUCCESS;
        }

        $rows = [];
        $failureCount = 0;

        foreach ($invoices as $invoice) {
            foreach ($this->validateOne($invoice, $accountIds) as $issue) {
                $failureCount++;
                $rows[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no ?? '',
                    'issue' => $issue,
                ];
            }
        }

        if ($rows !== []) {
            $this->newLine();
            $this->error("Found {$failureCount} issue(s):");
            $this->table(['invoice_id', 'invoice_no', 'issue'], $rows);

            return self::FAILURE;
        }

        $this->info("Validated {$invoices->count()} posted sales invoice(s); journals match line totals and PPN splits.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $accountIds
     * @return list<string>
     */
    private function validateOne(SalesInvoice $invoice, array $accountIds): array
    {
        $issues = [];

        $lineSum = (float) $invoice->lines->sum(fn ($l) => (float) $l->amount);
        $headerTotal = (float) $invoice->total_amount;
        if (! $this->approxEq($lineSum, $headerTotal)) {
            $issues[] = sprintf(
                'Line amount sum (%.2f) ≠ invoice total_amount (%.2f)',
                $lineSum,
                $headerTotal
            );
        }

        $journal = DB::table('journals')
            ->where('source_type', 'sales_invoice')
            ->where('source_id', $invoice->id)
            ->orderBy('id')
            ->first();

        if (! $journal) {
            $issues[] = 'No journal with source_type=sales_invoice for this invoice';

            return $issues;
        }

        $jl = DB::table('journal_lines')->where('journal_id', $journal->id)->get();
        $debitByAcct = [];
        $creditByAcct = [];
        foreach ($jl as $row) {
            $aid = (int) $row->account_id;
            $debitByAcct[$aid] = ($debitByAcct[$aid] ?? 0) + (float) $row->debit;
            $creditByAcct[$aid] = ($creditByAcct[$aid] ?? 0) + (float) $row->credit;
        }

        $sumDebit = array_sum($debitByAcct);
        $sumCredit = array_sum($creditByAcct);
        if (! $this->approxEq($sumDebit, $sumCredit)) {
            $issues[] = sprintf(
                'Journal %d not balanced: debits %.2f vs credits %.2f',
                $journal->id,
                $sumDebit,
                $sumCredit
            );
        }

        $expected = SalesInvoicePostingMath::summarizeLinesForPosting($invoice->lines);
        $gross = (float) $expected['gross_total'];
        $ppn = (float) $expected['ppn_total'];
        /** @var array<int, float> $ppnByRev */
        $ppnByRev = $expected['ppn_by_revenue_account'];

        if ($invoice->is_opening_balance) {
            $arDebit = $debitByAcct[$accountIds['ar']] ?? 0;
            if (! $this->approxEq($arDebit, $gross)) {
                $issues[] = sprintf('Opening: AR (1.1.2.01) debit %.2f, expected gross %.2f', $arDebit, $gross);
            }

            $reAcct = $accountIds['retained_opening'];
            if ($reAcct > 0) {
                $reCredit = $creditByAcct[$reAcct] ?? 0;
                $expectedRe = round($gross - $ppn, 2);
                if (! $this->approxEq($reCredit, $expectedRe)) {
                    $issues[] = sprintf(
                        'Opening: Retained earnings (3.3.1) credit %.2f, expected %.2f (gross − PPN)',
                        $reCredit,
                        $expectedRe
                    );
                }
            } elseif ($ppn > 0) {
                $issues[] = 'Opening balance invoice has PPN but account 3.3.1 not found in chart';
            }

            if ($ppn > 0) {
                $ppnCredit = $creditByAcct[$accountIds['ppn']] ?? 0;
                if (! $this->approxEq($ppnCredit, $ppn)) {
                    $issues[] = sprintf('Opening: PPN (2.1.2) credit %.2f, expected %.2f', $ppnCredit, $ppn);
                }
            }
        } else {
            $arUnCr = $creditByAcct[$accountIds['ar_uninvoice']] ?? 0;
            if (! $this->approxEq($arUnCr, $gross)) {
                $issues[] = sprintf('AR UnInvoice (1.1.2.04) credit %.2f, expected gross %.2f', $arUnCr, $gross);
            }

            $arDebit = $debitByAcct[$accountIds['ar']] ?? 0;
            if (! $this->approxEq($arDebit, $gross)) {
                $issues[] = sprintf('AR (1.1.2.01) debit %.2f, expected gross %.2f', $arDebit, $gross);
            }

            if ($ppn > 0) {
                $ppnCredit = $creditByAcct[$accountIds['ppn']] ?? 0;
                if (! $this->approxEq($ppnCredit, $ppn)) {
                    $issues[] = sprintf('PPN (2.1.2) credit %.2f, expected %.2f', $ppnCredit, $ppn);
                }
            }

            foreach ($ppnByRev as $revenueAccountId => $expectedPpnDebit) {
                $got = $debitByAcct[$revenueAccountId] ?? 0;
                if (! $this->approxEq($got, $expectedPpnDebit)) {
                    $issues[] = sprintf(
                        'Revenue account_id %d debit %.2f, expected PPN reclass %.2f',
                        $revenueAccountId,
                        $got,
                        $expectedPpnDebit
                    );
                }
            }
        }

        return $issues;
    }

    private function approxEq(float $a, float $b): bool
    {
        return abs($a - $b) <= self::TOLERANCE;
    }
}
