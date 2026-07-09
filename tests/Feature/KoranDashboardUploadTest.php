<?php

namespace Tests\Feature;

use App\Jobs\Bank\ParseBankStatementJob;
use App\Models\Bank\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KoranDashboardUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $user = User::factory()->create();
        $user->givePermissionTo(['bank_reconciliation.view', 'bank_reconciliation.import']);
        $this->actingAs($user);
    }

    public function test_cell_upload_creates_session_and_redirects_to_koran_grid(): void
    {
        Bus::fake([ParseBankStatementJob::class]);

        $bankAccount = $this->createBankAccount();

        $response = $this->post(route('bank-reconciliation.store'), [
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-06',
            'source_mode' => 'ai',
            'redirect_to' => 'koran',
            'file' => UploadedFile::fake()->create('koran.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('bank-reconciliation.index', ['year' => 2026]));
        $this->assertDatabaseHas('bank_reconciliations', [
            'bank_account_id' => $bankAccount->id,
            'source_mode' => 'ai',
            'status' => 'processing',
        ]);

        Bus::assertDispatched(ParseBankStatementJob::class);
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
            'code' => 'BNK-UP',
            'name' => 'Upload Test Bank',
            'bank_name' => 'BCA',
            'account_number' => '555444333',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);
    }
}
