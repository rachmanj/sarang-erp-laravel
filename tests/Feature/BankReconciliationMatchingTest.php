<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankStatement;
use App\Models\Bank\BankStatementLine;
use App\Services\Accounting\PostingService;
use App\Services\Bank\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationMatchingTest extends TestCase
{
    use RefreshDatabase;

    private int $bankCoaId;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $this->bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if (! $this->bankCoaId) {
            $this->bankCoaId = DB::table('accounts')->insertGetId([
                'code' => '1.1.1.02',
                'name' => 'Kas di Bank - Operasional',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->bankAccount = BankAccount::create([
            'code' => 'BNK-MANDIRI',
            'name' => 'Mandiri Operasional',
            'bank_name' => 'Mandiri',
            'account_number' => '1490055550059',
            'currency' => 'IDR',
            'account_id' => $this->bankCoaId,
            'is_active' => true,
        ]);
    }

    public function test_auto_match_links_statement_credit_to_book_debit(): void
    {
        $statement = $this->createStatement();
        $bankLine = BankStatementLine::create([
            'bank_statement_id' => $statement->id,
            'posting_date' => '2026-01-06',
            'description' => 'Incoming transfer',
            'amount' => 482850.00,
            'direction' => 'credit',
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'line-1'),
        ]);

        $journalId = app(PostingService::class)->postJournal([
            'date' => '2026-01-06',
            'description' => 'Receipt in bank',
            'source_type' => 'manual_journal',
            'source_id' => 1,
            'lines' => [
                ['account_id' => $this->bankCoaId, 'debit' => 482850, 'credit' => 0, 'memo' => 'Bank in'],
                ['account_id' => (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id'), 'debit' => 0, 'credit' => 482850, 'memo' => 'AR'],
            ],
        ]);

        $journalLineId = (int) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $this->bankCoaId)
            ->value('id');

        $reconciliation = app(BankReconciliationService::class)->createSessionFromStatement($statement);
        $matched = app(BankReconciliationService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('bank_reconciliation_matches', [
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_statement_line_id' => $bankLine->id,
            'journal_line_id' => $journalLineId,
            'match_type' => 'auto',
        ]);
        $this->assertDatabaseHas('bank_statement_lines', [
            'id' => $bankLine->id,
            'match_status' => 'matched',
        ]);
    }

    public function test_finalize_requires_zero_unmatched_lines_and_balanced_closing(): void
    {
        $statement = $this->createStatement(closing: 100000);
        BankStatementLine::create([
            'bank_statement_id' => $statement->id,
            'posting_date' => '2026-01-06',
            'description' => 'Ignored fee',
            'amount' => 12500,
            'direction' => 'debit',
            'match_status' => 'ignored',
            'line_hash' => hash('sha256', 'line-ignored'),
        ]);

        $reconciliation = app(BankReconciliationService::class)->createSessionFromStatement($statement);

        $this->expectException(\RuntimeException::class);
        app(BankReconciliationService::class)->finalize($reconciliation);
    }

    private function createStatement(float $opening = 0, float $closing = 0): BankStatement
    {
        return BankStatement::create([
            'bank_account_id' => $this->bankAccount->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'currency' => 'IDR',
            'status' => 'imported',
        ]);
    }
}
