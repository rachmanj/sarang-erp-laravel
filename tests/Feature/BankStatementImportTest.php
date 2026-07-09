<?php

namespace Tests\Feature;

use App\Jobs\Bank\FetchBookGlLinesJob;
use App\Jobs\Bank\ParseBankStatementJob;
use App\Models\Bank\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
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

    public function test_store_route_creates_ai_session_and_dispatches_parse_job(): void
    {
        Bus::fake([ParseBankStatementJob::class, FetchBookGlLinesJob::class]);

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

        $response = $this->post(route('bank-reconciliation.store'), [
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-01',
            'source_mode' => 'ai',
            'file' => UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_reconciliations', [
            'bank_account_id' => $bankAccount->id,
            'source_mode' => 'ai',
            'status' => 'processing',
        ]);
        $this->assertDatabaseHas('bank_statements', [
            'bank_account_id' => $bankAccount->id,
            'status' => 'imported',
        ]);

        Bus::assertDispatched(ParseBankStatementJob::class);
    }
}
