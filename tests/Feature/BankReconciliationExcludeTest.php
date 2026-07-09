<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankStatementLine;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationExcludeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_excluded_lines_are_omitted_from_balance(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-EX',
            'name' => 'Exclude Test',
            'bank_name' => 'Mandiri',
            'account_number' => '999888777',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $reconciliation = app(BankReconciliationService::class)->createManualSession($bankAccount, '2026-04-01');

        $bankLine = BankStatementLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-04-05',
            'amount' => 5000,
            'direction' => 'debit',
            'debit' => 5000,
            'credit' => 0,
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'ex-bank'),
        ]);

        $balance = app(ReconciliationBalanceService::class);
        $this->assertFalse($balance->isBalanced($reconciliation));

        app(BankReconciliationService::class)->setBankLineExcluded($reconciliation, $bankLine, true, 'Timing difference');

        $reconciliation->refresh();
        $this->assertTrue($balance->isBalanced($reconciliation));
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
