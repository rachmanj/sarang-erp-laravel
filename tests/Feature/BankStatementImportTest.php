<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankStatement;
use App\Models\User;
use App\Services\Bank\BankStatementParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankStatementImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $user = User::factory()->create();
        $user->givePermissionTo([
            'bank_reconciliation.view',
            'bank_reconciliation.import',
            'bank_reconciliation.reconcile',
        ]);
        $this->actingAs($user);
    }

    public function test_import_route_creates_reconciliation_session_from_parser_result(): void
    {
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if (! $bankCoaId) {
            $bankCoaId = DB::table('accounts')->insertGetId([
                'code' => '1.1.1.02',
                'name' => 'Kas di Bank - Operasional',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bankAccount = BankAccount::create([
            'code' => 'BNK-CIMB',
            'name' => 'CIMB Operasional',
            'bank_name' => 'CIMB Niaga',
            'account_number' => '800201845200',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $statement = BankStatement::create([
            'bank_account_id' => $bankAccount->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'opening_balance' => 1000,
            'closing_balance' => 900,
            'currency' => 'IDR',
            'status' => 'imported',
        ]);

        $this->mock(BankStatementParser::class, function ($mock) use ($statement) {
            $mock->shouldReceive('importFromUpload')->once()->andReturn($statement);
        });

        $response = $this->post(route('bank-reconciliation.import.store'), [
            'bank_account_id' => $bankAccount->id,
            'file' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_reconciliations', [
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('bank_statements', [
            'id' => $statement->id,
            'status' => 'reconciling',
        ]);
    }
}
