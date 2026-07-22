<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationOutstandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_outstanding_book_line_allows_identity_balance(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-OUT',
            'name' => 'Outstanding Test',
            'bank_name' => 'Mandiri',
            'account_number' => '111222333',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $service = app(BankReconciliationService::class);
        $reconciliation = $service->createManualSession($bankAccount, '2026-05-01');

        $service->updateStatementBalances($reconciliation, 100000, 100000);
        $reconciliation->update([
            'opening_balance_book' => 100000,
            'closing_balance_book' => 110000,
            'book_balance' => 110000,
        ]);

        $bookLine = BankBookLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-28',
            'description' => 'Deposit in transit',
            'debit' => 10000,
            'credit' => 0,
            'match_status' => BankBookLine::MATCH_UNMATCHED,
        ]);

        $balance = app(ReconciliationBalanceService::class);
        $this->assertFalse($balance->isBalanced($reconciliation));

        $service->setBookLineOutstanding($reconciliation, $bookLine, true, 'Deposit in transit');

        $reconciliation->refresh();
        $this->assertSame(10000.0, $balance->depositsInTransit($reconciliation));
        $this->assertSame(0.0, $balance->reconciliationDifference($reconciliation));
        $this->assertTrue($balance->isBalanced($reconciliation));

        $service->finalize($reconciliation);
        $this->assertSame('completed', $reconciliation->fresh()->status);
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
