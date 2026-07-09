<?php

namespace Tests\Feature;

use App\Models\Bank\BankAccount;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KoranDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $user = User::factory()->create();
        $user->givePermissionTo(['bank_reconciliation.view']);
        $this->actingAs($user);
    }

    public function test_koran_grid_renders_with_year_filter(): void
    {
        $bankAccount = $this->createBankAccount();

        BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-03-01',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'source_mode' => 'manual',
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ]);

        $this->get(route('bank-reconciliation.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Rekening Koran — 2026')
            ->assertSee($bankAccount->name)
            ->assertSee('koran-status-box--present', false)
            ->assertSee('fa-balance-scale', false);
    }

    public function test_koran_cell_json_returns_session_status(): void
    {
        $bankAccount = $this->createBankAccount();

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-04-01',
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'source_mode' => 'ai',
            'status' => BankReconciliation::STATUS_PROCESSING,
        ]);

        $this->getJson(route('bank-reconciliation.koran.cell', [
            'bank_account_id' => $bankAccount->id,
            'year' => 2026,
            'month' => 4,
        ]))
            ->assertOk()
            ->assertJson([
                'status' => 'processing',
                'reconciliation_id' => $reconciliation->id,
                'can_open' => true,
            ]);
    }

    public function test_sessions_list_page_is_accessible(): void
    {
        $this->get(route('bank-reconciliation.sessions'))
            ->assertOk()
            ->assertSee('Reconciliation Sessions');
    }

    public function test_koran_cell_shows_pdf_icon_when_statement_has_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bank-statements/test-koran.pdf', '%PDF-1.4 test');

        $bankAccount = $this->createBankAccount();
        $statement = BankStatement::create([
            'bank_account_id' => $bankAccount->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'opening_balance' => 0,
            'closing_balance' => 0,
            'currency' => 'IDR',
            'original_filename' => 'mandiri-march.pdf',
            'file_path' => 'bank-statements/test-koran.pdf',
            'status' => 'imported',
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'periode' => '2026-03-01',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'source_mode' => 'ai',
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ]);

        $pdfUrl = route('bank-reconciliation.statement-pdf', $reconciliation);

        $this->get(route('bank-reconciliation.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('koran-status-box--present', false)
            ->assertSee('fa-file-pdf', false)
            ->assertSee($pdfUrl, false);

        $this->getJson(route('bank-reconciliation.koran.cell', [
            'bank_account_id' => $bankAccount->id,
            'year' => 2026,
            'month' => 3,
        ]))
            ->assertOk()
            ->assertJson([
                'has_pdf' => true,
                'statement_pdf_url' => $pdfUrl,
            ]);
    }

    public function test_statement_pdf_route_returns_inline_pdf(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bank-statements/preview.pdf', '%PDF-1.4 preview');

        $bankAccount = $this->createBankAccount();
        $statement = BankStatement::create([
            'bank_account_id' => $bankAccount->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'opening_balance' => 0,
            'closing_balance' => 0,
            'currency' => 'IDR',
            'original_filename' => 'mandiri-march.pdf',
            'file_path' => 'bank-statements/preview.pdf',
            'status' => 'imported',
        ]);

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'bank_statement_id' => $statement->id,
            'periode' => '2026-03-01',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'source_mode' => 'ai',
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ]);

        $this->get(route('bank-reconciliation.statement-pdf', $reconciliation))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="mandiri-march.pdf"');
    }

    public function test_statement_pdf_route_returns_not_found_without_attachment(): void
    {
        $bankAccount = $this->createBankAccount();

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $bankAccount->id,
            'periode' => '2026-03-01',
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'source_mode' => 'manual',
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ]);

        $this->get(route('bank-reconciliation.statement-pdf', $reconciliation))
            ->assertNotFound();
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
            'code' => 'BNK-KORAN',
            'name' => 'Koran Grid Bank',
            'bank_name' => 'Mandiri',
            'account_number' => '777888999',
            'currency' => 'IDR',
            'account_id' => $bankCoaId,
            'is_active' => true,
        ]);
    }
}
