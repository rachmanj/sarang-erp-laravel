<?php

namespace Tests\Feature;

use App\Jobs\Bank\FetchBookGlLinesJob;
use App\Models\Bank\BankAccount;
use App\Models\User;
use App\Services\Bank\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationManualModeTest extends TestCase
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

    public function test_manual_session_can_be_created_without_pdf(): void
    {
        Bus::fake([FetchBookGlLinesJob::class]);

        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-MANUAL',
            'name' => 'Manual Bank',
            'bank_name' => 'BCA',
            'account_number' => '111222333',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $response = $this->post(route('bank-reconciliation.store'), [
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-02',
            'source_mode' => 'manual',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_reconciliations', [
            'bank_account_id' => $bankAccount->id,
            'source_mode' => 'manual',
            'status' => 'in_review',
            'bank_statement_id' => null,
        ]);

        Bus::assertDispatched(FetchBookGlLinesJob::class);
    }

    public function test_manual_bank_line_can_be_added(): void
    {
        $bankCoaId = $this->ensureBankCoa();
        $bankAccount = BankAccount::create([
            'code' => 'BNK-MANUAL2',
            'name' => 'Manual Bank 2',
            'bank_name' => 'BCA',
            'account_number' => '444555666',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);

        $reconciliation = app(BankReconciliationService::class)->createManualSession($bankAccount, '2026-03-01');

        $response = $this->post(route('bank-reconciliation.lines.store', $reconciliation), [
            'posting_date' => '2026-03-05',
            'description' => 'Manual entry',
            'debit' => 0,
            'credit' => 250000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_statement_lines', [
            'bank_reconciliation_id' => $reconciliation->id,
            'credit' => 250000,
            'is_ai_extracted' => false,
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
