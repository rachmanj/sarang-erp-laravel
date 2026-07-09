<?php

namespace Tests\Feature;

use App\Jobs\Bank\ParseBankStatementJob;
use App\Models\Bank\BankAccount;
use App\Models\Bank\BankReconciliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankReconciliationJobsTest extends TestCase
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
        ]);
        $this->actingAs($user);
    }

    public function test_ai_create_dispatches_parse_job(): void
    {
        Bus::fake([ParseBankStatementJob::class]);

        $bankAccount = $this->createBankAccount();

        $response = $this->post(route('bank-reconciliation.store'), [
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-05',
            'source_mode' => 'ai',
            'file' => UploadedFile::fake()->create('koran.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bank_reconciliations', [
            'bank_account_id' => $bankAccount->id,
            'source_mode' => 'ai',
            'status' => 'processing',
        ]);

        Bus::assertDispatched(ParseBankStatementJob::class);
    }

    public function test_status_endpoint_returns_balance_payload(): void
    {
        $bankAccount = $this->createBankAccount();
        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-06-01',
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'source_mode' => 'manual',
            'status' => 'in_review',
        ]);

        $response = $this->getJson(route('bank-reconciliation.status', $reconciliation));

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'bank_lines_count',
                'book_lines_count',
                'bank_net',
                'book_net',
                'difference',
                'is_balanced',
            ]);
    }

    private function createBankAccount(): BankAccount
    {
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if (! $bankCoaId) {
            $bankCoaId = DB::table('accounts')->insertGetId([
                'code' => '1.1.1.02',
                'name' => 'Kas di Bank',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return BankAccount::create([
            'code' => 'BNK-JOB',
            'name' => 'Job Test Bank',
            'bank_name' => 'Mandiri',
            'account_number' => '555666777',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);
    }
}
