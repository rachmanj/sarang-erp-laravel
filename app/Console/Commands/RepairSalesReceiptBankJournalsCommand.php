<?php

namespace App\Console\Commands;

use App\Models\Accounting\SalesReceipt;
use App\Services\Accounting\JournalBuilders\SalesReceiptJournalBuilder;
use App\Services\Accounting\PostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairSalesReceiptBankJournalsCommand extends Command
{
    protected $signature = 'sales-receipts:repair-bank-journals
                            {--dry-run : List affected receipts without posting corrections}
                            {--force : Apply corrections without confirmation}
                            {--id= : Process only this sales_receipts.id}
                            {--receipt-no= : Process only this receipt number}';

    protected $description = 'Reverse mis-posted sales receipt journals that debited Kas di Tangan instead of the selected bank account, then repost correctly';

    private const TOLERANCE = 0.02;

    public function handle(SalesReceiptJournalBuilder $builder, PostingService $posting): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $receipts = $this->findReceipts();

        if ($receipts->isEmpty()) {
            $this->warn('No posted sales receipts matched the filter.');

            return self::SUCCESS;
        }

        $rows = [];
        $toRepair = [];

        foreach ($receipts as $receipt) {
            $issue = $this->analyzeReceipt($receipt, $builder);
            if ($issue === null) {
                continue;
            }

            $toRepair[] = ['receipt' => $receipt, 'issue' => $issue];
            $rows[] = [
                'id' => $receipt->id,
                'receipt_no' => $receipt->receipt_no,
                'entity' => $receipt->company_entity_id,
                'total' => number_format($issue['total'], 2, '.', ','),
                'intended' => $issue['expected_code'],
                'wrong_cash_net' => number_format($issue['wrong_cash_net'], 2, '.', ','),
            ];
        }

        if ($rows === []) {
            $this->info("Checked {$receipts->count()} posted sales receipt(s); all bank journals match receipt lines.");

            return self::SUCCESS;
        }

        $this->table(['id', 'receipt_no', 'entity', 'total', 'intended', 'wrong_cash_net'], $rows);
        $this->warn(sprintf('Found %d sales receipt(s) with bank account mis-posted to Kas di Tangan (1.1.1.01).', count($rows)));

        if ($dryRun) {
            $this->info('Dry run only — no journals were changed.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Reverse incorrect journals and repost with the selected bank/cash accounts?', true)) {
            return self::SUCCESS;
        }

        $repaired = 0;
        foreach ($toRepair as $item) {
            /** @var SalesReceipt $receipt */
            $receipt = $item['receipt'];
            try {
                DB::transaction(function () use ($receipt, $builder, $posting): void {
                    $this->repairReceipt($receipt, $builder, $posting);
                });
                $repaired++;
                $this->line("Repaired SR {$receipt->receipt_no} (#{$receipt->id}).");
            } catch (\Throwable $e) {
                $this->error("Failed SR {$receipt->receipt_no} (#{$receipt->id}): {$e->getMessage()}");
            }
        }

        $this->info("Repaired {$repaired} sales receipt journal(s).");

        return $repaired === count($toRepair) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return \Illuminate\Support\Collection<int, SalesReceipt>
     */
    private function findReceipts()
    {
        $query = SalesReceipt::query()
            ->where('status', 'posted')
            ->with('lines')
            ->orderBy('id');

        if ($this->option('id') !== null && $this->option('id') !== '') {
            $query->where('id', (int) $this->option('id'));
        }

        if ($this->option('receipt-no') !== null && $this->option('receipt-no') !== '') {
            $query->where('receipt_no', (string) $this->option('receipt-no'));
        }

        return $query->get();
    }

    /**
     * @return array{expected_account_id: int, expected_code: string, wrong_cash_net: float, total: float}|null
     */
    private function analyzeReceipt(SalesReceipt $receipt, SalesReceiptJournalBuilder $builder): ?array
    {
        $draft = $builder->build($receipt);
        $expectedDebitAccountId = 0;

        foreach ($draft->lines as $line) {
            if ((float) ($line['debit'] ?? 0) > 0) {
                $expectedDebitAccountId = (int) $line['account_id'];
                break;
            }
        }

        if ($expectedDebitAccountId <= 0) {
            return null;
        }

        $expectedCode = (string) DB::table('accounts')->where('id', $expectedDebitAccountId)->value('code');
        $total = (float) $receipt->total_amount;
        $net = $this->netEffectByAccountCode((int) $receipt->id);

        $expectedNet = $net[$expectedCode] ?? 0.0;
        $arNet = $net['1.1.2.01'] ?? 0.0;

        if ($this->approxEq($expectedNet, $total) && $this->approxEq($arNet, -$total)) {
            return null;
        }

        return [
            'expected_account_id' => $expectedDebitAccountId,
            'expected_code' => $expectedCode,
            'wrong_cash_net' => $net['1.1.1.01'] ?? 0.0,
            'total' => $total,
        ];
    }

    private function repairReceipt(
        SalesReceipt $receipt,
        SalesReceiptJournalBuilder $builder,
        PostingService $posting
    ): void {
        $original = DB::table('journals')
            ->where('source_type', 'sales_receipt')
            ->where('source_id', $receipt->id)
            ->where('description', 'Post Sales Receipt #'.$receipt->id)
            ->orderBy('id')
            ->first();

        if ($original && ! $this->isReversed((int) $original->id)) {
            $debitAccountId = (int) DB::table('journal_lines')
                ->where('journal_id', $original->id)
                ->where('debit', '>', 0)
                ->value('account_id');

            $draft = $builder->build($receipt);
            $expectedDebitAccountId = 0;
            foreach ($draft->lines as $line) {
                if ((float) ($line['debit'] ?? 0) > 0) {
                    $expectedDebitAccountId = (int) $line['account_id'];
                    break;
                }
            }

            if ($debitAccountId !== $expectedDebitAccountId) {
                $posting->reverseJournal((int) $original->id, $receipt->date->toDateString());
            }
        }

        $issue = $this->analyzeReceipt($receipt, $builder);
        if ($issue === null) {
            return;
        }

        $draft = $builder->build($receipt);
        $posting->postJournal([
            'date' => $draft->date ?? $receipt->date->toDateString(),
            'description' => $draft->description,
            'source_type' => 'sales_receipt',
            'source_id' => $receipt->id,
            'lines' => $draft->lines,
        ]);
    }

    /**
     * @return array<string, float>
     */
    private function netEffectByAccountCode(int $receiptId): array
    {
        $rows = DB::table('journals as j')
            ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('j.source_type', 'sales_receipt')
            ->where('j.source_id', $receiptId)
            ->groupBy('a.code')
            ->selectRaw('a.code, SUM(jl.debit) - SUM(jl.credit) as net')
            ->get();

        $net = [];
        foreach ($rows as $row) {
            $net[$row->code] = (float) $row->net;
        }

        return $net;
    }

    private function isReversed(int $journalId): bool
    {
        return DB::table('journals')
            ->where('description', 'like', 'Reversal of #'.$journalId.'%')
            ->exists();
    }

    private function approxEq(float $a, float $b): bool
    {
        return abs($a - $b) <= self::TOLERANCE;
    }
}
