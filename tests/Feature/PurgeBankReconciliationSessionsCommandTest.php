<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatement;
use App\Models\Bank\BankStatementLine;
use App\Models\Bank\ReconciliationMatchGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurgeBankReconciliationSessionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_purge_deletes_sessions_but_keeps_statements_and_accounts(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-PURGE',
            'name' => 'Purge Test Bank',
            'bank_name' => 'Mandiri',
            'account_number' => '111000222',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $statement = BankStatement::create([
            'bank_account_id' => $bankAccount->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'opening_balance' => 0,
            'closing_balance' => 1000,
            'currency' => 'IDR',
            'file_path' => 'bank-statements/test.pdf',
            'status' => 'imported',
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'periode' => '2026-01-01',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'source_mode' => 'ai',
            'status' => 'in_review',
        ]);

        BankStatementLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_statement_id' => $statement->id,
            'posting_date' => '2026-01-05',
            'amount' => 100,
            'direction' => 'credit',
            'debit' => 0,
            'credit' => 100,
            'match_status' => 'unmatched',
            'line_hash' => hash('sha256', 'purge-line'),
        ]);

        BankBookLine::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-05',
            'debit' => 100,
            'credit' => 0,
            'match_status' => 'unmatched',
        ]);

        ReconciliationMatchGroup::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => 'manual',
            'bank_total' => 0,
            'book_total' => 0,
            'difference' => 0,
        ]);

        $this->artisan('bank-reconciliation:purge-sessions --force')
            ->assertSuccessful();

        $this->assertDatabaseCount('bank_reconciliations', 0);
        $this->assertDatabaseCount('reconciliation_match_groups', 0);
        $this->assertDatabaseCount('bank_book_lines', 0);
        $this->assertDatabaseHas('bank_statements', ['id' => $statement->id]);
        $this->assertDatabaseHas('bank_accounts', ['id' => $bankAccount->id]);
    }

    private function ensureBankCoa(): int
    {
        $id = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if ($id) {
            return $id;
        }

        return DB::table('accounts')->insertGetId([
            'code' => '1.1.1.02',
            'name' => 'Kas di Bank',
            'type' => 'asset',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
