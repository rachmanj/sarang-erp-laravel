<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationBalanceService;
use App\Services\Bank\ReconciliationMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationSignConventionTest extends TestCase
{
    use RefreshDatabase;

    private BankReconciliation $reconciliation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-TEST',
            'name' => 'Test Bank',
            'bank_name' => 'Mandiri',
            'account_number' => '1234567890',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $this->reconciliation = app(BankReconciliationService::class)->createManualSession($bankAccount, '2026-01-01');
    }

    public function test_opposite_polarity_lines_sum_to_zero(): void
    {
        BankStatementLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'description' => 'Transfer in',
            'amount' => 48010000,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 48010000,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => hash('sha256', 'bank-1'),
        ]);

        BankBookLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'description' => 'Receipt',
            'debit' => 48010000,
            'credit' => 0,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        $balance = app(ReconciliationBalanceService::class);

        $this->assertSame(-48010000.0, $balance->bankNet($this->reconciliation));
        $this->assertSame(48010000.0, $balance->bookNet($this->reconciliation));
        $this->assertTrue($balance->isBalanced($this->reconciliation));
    }

    public function test_manual_match_requires_bank_plus_book_net_zero(): void
    {
        $bankLine = BankStatementLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'amount' => 1000,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 1000,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => hash('sha256', 'bank-2'),
        ]);

        $bookLine = BankBookLine::create([
            'bank_reconciliation_id' => $this->reconciliation->id,
            'posting_date' => '2026-01-06',
            'debit' => 1000,
            'credit' => 0,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        app(ReconciliationMatchingService::class)->manualMatch(
            $this->reconciliation,
            [$bankLine->id],
            [$bookLine->id],
        );

        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $this->reconciliation->id,
            'match_type' => 'manual',
            'difference' => 0,
        ]);
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
