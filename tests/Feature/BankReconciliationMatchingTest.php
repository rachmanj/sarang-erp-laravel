<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use App\Services\Accounting\PostingService;
use App\Services\Bank\BankBookLineFetcher;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationMatchingTest extends TestCase
{
    use RefreshDatabase;

    private int $bankCoaId;

    private BankAccount $bankAccount;

    private BankReconciliation $reconciliation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $this->bankCoaId = $this->ensureBankCoa();
        $this->bankAccount = BankAccount::create([
            'code' => 'BNK-MANDIRI',
            'name' => 'Mandiri Operasional',
            'bank_name' => 'Mandiri',
            'account_number' => '1490055550059',
            'currency' => 'IDR',
            'account_id' => $this->bankCoaId,
            'is_active' => true,
        ]);

        $this->reconciliation = app(BankReconciliationService::class)->createManualSession($this->bankAccount, '2026-01-01');
    }

    public function test_auto_match_links_statement_credit_to_book_debit(): void
    {
        BankStatementLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'description' => 'Incoming transfer',
            'amount' => 482850.00,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 482850,
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'line-1'),
        ]);

        app(PostingService::class)->postJournal([
            'date' => '2026-01-06',
            'description' => 'Receipt in bank',
            'source_type' => 'manual_journal',
            'source_id' => 1,
            'lines' => [
                ['account_id' => $this->bankCoaId, 'debit' => 482850, 'credit' => 0, 'memo' => 'Bank in'],
                ['account_id' => (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id'), 'debit' => 0, 'credit' => 482850, 'memo' => 'AR'],
            ],
        ]);

        app(BankBookLineFetcher::class)->fetchAndReplace($this->reconciliation);
        $matched = app(ReconciliationMatchingService::class)->autoMatch($this->reconciliation->fresh());

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $this->reconciliation->id,
            'match_type' => 'auto_exact',
        ]);
        $this->assertDatabaseHas('bank_statement_lines', [
            'bank_reconciliation_id' => $this->reconciliation->id,
            'match_status' => 'matched',
        ]);
    }

    public function test_nm_manual_match_creates_group_with_multiple_lines(): void
    {
        $bank1 = BankStatementLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'amount' => 500,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 500,
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'b1'),
        ]);
        $bank2 = BankStatementLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'amount' => 500,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 500,
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'b2'),
        ]);

        $book = BankBookLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'debit' => 1000,
            'credit' => 0,
            'match_status' => 'unmatched',
        ]);

        app(ReconciliationMatchingService::class)->manualMatch(
            $this->reconciliation,
            [$bank1->id, $bank2->id],
            [$book->id],
        );

        $this->assertSame(1, $this->reconciliation->matchGroups()->count());
        $this->assertSame(2, DB::table('match_group_bank_lines')->count());
    }

    public function test_finalize_requires_balanced_nets(): void
    {
        BankStatementLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'amount' => 1000,
            'direction' => 'debit',
            'debit' => 1000,
            'credit' => 0,
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'unbalanced'),
        ]);

        $this->expectException(\RuntimeException::class);
        app(BankReconciliationService::class)->finalize($this->reconciliation);
    }

    private function ensureBankCoa(): int
    {
        $id = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if ($id) {
            return $id;
        }

        return DB::table('accounts')->insertGetId([
            'code' => '1.1.1.02',
            'name' => 'Kas di Bank - Operasional',
            'type' => 'asset',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
