<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankStatementLine;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\ReconciliationAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_post_adjustment_from_bank_charge_creates_journal_and_matches(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $expenseId = $this->ensureExpenseAccount();

        $bankAccount = BankAccount::create([
            'code' => 'BNK-ADJ',
            'name' => 'Adjustment Test',
            'bank_name' => 'CIMB',
            'account_number' => '555666777',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $service = app(BankReconciliationService::class);
        $reconciliation = $service->createManualSession($bankAccount, '2026-07-01');

        $bankLine = BankStatementLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-07-10',
            'description' => 'Admin fee',
            'amount' => 7500,
            'direction' => 'debit',
            'debit' => 7500,
            'credit' => 0,
            'match_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_hash' => hash('sha256', 'adj-fee'),
        ]);

        $journalId = app(ReconciliationAdjustmentService::class)->postFromBankLine(
            $reconciliation->fresh(['bankAccount']),
            $bankLine->fresh(),
            $expenseId,
            'Bank admin fee',
        );

        $this->assertGreaterThan(0, $journalId);
        $this->assertDatabaseHas('journals', [
            'id' => $journalId,
            'source_type' => 'bank_reconciliation_adjustment',
            'source_id' => $reconciliation->id,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $bankCoaId,
            'credit' => 7500,
        ]);
        $this->assertDatabaseHas('bank_statement_lines', [
            'id' => $bankLine->id,
            'adjusting_journal_id' => $journalId,
            'match_status' => 'matched',
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

    private function ensureExpenseAccount(): int
    {
        $id = (int) DB::table('accounts')->where('code', '6.2.17')->value('id');
        if ($id) {
            return $id;
        }

        return DB::table('accounts')->insertGetId([
            'code' => '6.2.17',
            'name' => 'Biaya Lain-lain',
            'type' => 'expense',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
