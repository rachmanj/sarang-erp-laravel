<?php

namespace App\Console\Commands;

use App\Models\Accounting\Account;
use App\Services\Accounting\PostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedCapitalAndFixedAssetsCommand extends Command
{
    protected $signature = 'accounts:seed-capital-fixed-assets
                            {--dry-run : Report planned changes without writing}';

    protected $description = 'Seed opening-balance capital and fixed-asset journals for PT (entity 1) and CV (entity 2)';

    private const JOURNAL_DATE = '2026-01-01';

    private const CV_EQUITY_CODE = '3.1.3';

    private const CV_EQUITY_NAME = 'Modal/Penyertaan CV';

    /** @var list<array{entity_id: int, description: string, lines: list<array{code: string, debit: float, credit: float}>}> */
    private const JOURNALS = [
        [
            'entity_id' => 1,
            'description' => 'Opening Balance - Modal Disetor PT',
            'lines' => [
                ['code' => '3.3.1', 'debit' => 125_000_000, 'credit' => 0],
                ['code' => '3.1.1', 'debit' => 0, 'credit' => 125_000_000],
            ],
        ],
        [
            'entity_id' => 2,
            'description' => 'Opening Balance - Modal Penyertaan CV',
            'lines' => [
                ['code' => '3.3.1', 'debit' => 100_000_000, 'credit' => 0],
                ['code' => '3.1.3', 'debit' => 0, 'credit' => 100_000_000],
            ],
        ],
        [
            'entity_id' => 1,
            'description' => 'Opening Balance - Aset Tetap PT',
            'lines' => [
                ['code' => '1.2.1.06', 'debit' => 35_011_000, 'credit' => 0],
                ['code' => '3.3.1', 'debit' => 0, 'credit' => 35_011_000],
            ],
        ],
        [
            'entity_id' => 2,
            'description' => 'Opening Balance - Aset Tetap CV',
            'lines' => [
                ['code' => '1.2.1.06', 'debit' => 18_690_000, 'credit' => 0],
                ['code' => '3.3.1', 'debit' => 0, 'credit' => 18_690_000],
            ],
        ],
    ];

    public function handle(PostingService $posting): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no data will be modified.');
            $this->newLine();
        }

        $this->ensureCvEquityAccount($dryRun);
        $this->newLine();

        foreach (self::JOURNALS as $journal) {
            $this->postOpeningJournal($posting, $journal, $dryRun);
        }

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }

    private function ensureCvEquityAccount(bool $dryRun): void
    {
        $existing = Account::query()->where('code', self::CV_EQUITY_CODE)->first();
        if ($existing) {
            $this->line(sprintf(
                'Account %s (%s): skipped — already exists (id %d)',
                self::CV_EQUITY_CODE,
                self::CV_EQUITY_NAME,
                $existing->id
            ));

            return;
        }

        if ($dryRun) {
            $this->line(sprintf(
                'Account %s (%s): would create under parent 3.1',
                self::CV_EQUITY_CODE,
                self::CV_EQUITY_NAME
            ));

            return;
        }

        $parent = Account::query()->where('code', '3.1')->first();
        if (! $parent) {
            $parent = Account::query()->create([
                'code' => '3.1',
                'name' => 'Modal Saham',
                'type' => 'net_assets',
                'is_postable' => false,
                'parent_id' => Account::query()->where('code', '3')->value('id'),
            ]);
            $this->line(sprintf('Account 3.1 (Modal Saham): created (id %d)', $parent->id));
        }

        $account = Account::query()->create([
            'code' => self::CV_EQUITY_CODE,
            'name' => self::CV_EQUITY_NAME,
            'type' => 'net_assets',
            'is_postable' => true,
            'parent_id' => $parent->id,
        ]);

        $this->line(sprintf(
            'Account %s (%s): created (id %d)',
            self::CV_EQUITY_CODE,
            self::CV_EQUITY_NAME,
            $account->id
        ));
    }

    /**
     * @param  array{entity_id: int, description: string, lines: list<array{code: string, debit: float, credit: float}>}  $journal
     */
    private function postOpeningJournal(PostingService $posting, array $journal, bool $dryRun): void
    {
        $existingId = DB::table('journals')
            ->where('source_type', 'manual_journal')
            ->where('description', $journal['description'])
            ->where('company_entity_id', $journal['entity_id'])
            ->value('id');

        if ($existingId) {
            $this->line(sprintf(
                'Journal "%s" (entity %d): skipped — already exists (id %d)',
                $journal['description'],
                $journal['entity_id'],
                $existingId
            ));

            return;
        }

        $lines = [];
        foreach ($journal['lines'] as $line) {
            $accountId = Account::query()->where('code', $line['code'])->value('id');
            if (! $accountId) {
                $this->error(sprintf(
                    'Journal "%s": account %s not found — aborting this journal',
                    $journal['description'],
                    $line['code']
                ));

                return;
            }

            $lines[] = [
                'account_id' => (int) $accountId,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ];
        }

        if ($dryRun) {
            $debitTotal = array_sum(array_column($lines, 'debit'));
            $creditTotal = array_sum(array_column($lines, 'credit'));
            $this->line(sprintf(
                'Journal "%s" (entity %d): would post on %s (Dr %s = Cr %s)',
                $journal['description'],
                $journal['entity_id'],
                self::JOURNAL_DATE,
                number_format($debitTotal, 2, '.', ','),
                number_format($creditTotal, 2, '.', ',')
            ));

            return;
        }

        $journalId = $posting->postJournal([
            'date' => self::JOURNAL_DATE,
            'description' => $journal['description'],
            'source_type' => 'manual_journal',
            'source_id' => 0,
            'posted_by' => null,
            'company_entity_id' => $journal['entity_id'],
            'lines' => $lines,
        ]);

        $this->line(sprintf(
            'Journal "%s" (entity %d): posted (id %d)',
            $journal['description'],
            $journal['entity_id'],
            $journalId
        ));
    }
}
