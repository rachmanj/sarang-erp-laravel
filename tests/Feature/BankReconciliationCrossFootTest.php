<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankStatementLine;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationCrossFootTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_cross_foot_detects_missing_statement_line(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-CFOT',
            'name' => 'Cross Foot Test',
            'bank_name' => 'Mandiri',
            'account_number' => '777888999',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $service = app(BankReconciliationService::class);
        $reconciliation = $service->createManualSession($bankAccount, '2026-03-01');
        $service->updateStatementBalances($reconciliation, 1000, 1500);

        BankStatementLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-03-05',
            'amount' => 200,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 200,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => hash('sha256', 'cf1'),
        ]);

        $crossFoot = app(ReconciliationBalanceService::class)->statementCrossFoot($reconciliation->fresh());

        $this->assertNotNull($crossFoot);
        $this->assertFalse($crossFoot['valid']);
        $this->assertSame(500.0, $crossFoot['expected_movement']);
        $this->assertSame(200.0, $crossFoot['actual_movement']);
    }

    public function test_finalize_rejects_cross_foot_failure(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-CFOT2',
            'name' => 'Cross Foot Finalize',
            'bank_name' => 'Mandiri',
            'account_number' => '777888000',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $service = app(BankReconciliationService::class);
        $reconciliation = $service->createManualSession($bankAccount, '2026-03-01');
        $service->updateStatementBalances($reconciliation, 1000, 1500);
        $reconciliation->update([
            'opening_balance_book' => 1000,
            'closing_balance_book' => 1000,
            'book_balance' => 1000,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cross-foot');
        $service->finalize($reconciliation);
    }

    public function test_closing_balance_book_preserved_on_finalize(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-CLB',
            'name' => 'Closing Book Test',
            'bank_name' => 'BCA',
            'account_number' => '101010',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $service = app(BankReconciliationService::class);
        $reconciliation = $service->createManualSession($bankAccount, '2026-06-01');
        $reconciliation->update([
            'opening_balance_book' => 20000,
            'closing_balance_book' => 25000,
            'book_balance' => 25000,
            'opening_balance_bank' => 0,
            'closing_balance_bank' => 0,
        ]);

        $finalized = $service->finalize($reconciliation->fresh());

        $this->assertSame(25000.0, (float) $finalized->closing_balance_book);
        $this->assertSame(25000.0, (float) $finalized->book_balance);
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
