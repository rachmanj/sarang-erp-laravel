<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostingErrorMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function actingAsAccountingUser(array $permissions): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);
    }

    private function closePeriodForDate(string $date): void
    {
        $parsed = \Illuminate\Support\Carbon::parse($date);
        DB::table('periods')->updateOrInsert(
            ['year' => (int) $parsed->format('Y'), 'month' => (int) $parsed->format('n')],
            ['is_closed' => true, 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_sales_invoice_post_in_closed_period_redirects_with_indonesian_error(): void
    {
        $this->actingAsAccountingUser(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post']);

        $docDate = now()->startOfMonth()->toDateString();
        $this->closePeriodForDate($docDate);

        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => $docDate,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Closed period SI',
            'lines' => [
                ['account_id' => $revenueId, 'description' => 'Service', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));

        $postResp = $this->post('/sales-invoices/'.$invoiceId.'/post');

        $postResp->assertRedirect();
        $postResp->assertStatus(302);
        $postResp->assertSessionHas('error');
        $error = session('error');
        $this->assertIsString($error);
        $this->assertStringContainsStringIgnoringCase('sudah ditutup', $error);
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'draft']);
    }

    public function test_purchase_invoice_post_in_closed_period_redirects_with_indonesian_error(): void
    {
        $this->actingAsAccountingUser(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post', 'accounts.view']);

        $docDate = now()->startOfMonth()->toDateString();
        $this->closePeriodForDate($docDate);

        $expenseId = (int) DB::table('accounts')->where('code', '6.2.1')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');

        $resp = $this->post('/purchase-invoices', [
            'date' => $docDate,
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'payment_method' => 'credit',
            'description' => 'Closed period PI',
            'lines' => [
                ['account_id' => $expenseId, 'description' => 'Supplies', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));

        $postResp = $this->post('/purchase-invoices/'.$invoiceId.'/post');

        $postResp->assertRedirect();
        $postResp->assertStatus(302);
        $postResp->assertSessionHas('error');
        $error = session('error');
        $this->assertIsString($error);
        $this->assertStringContainsStringIgnoringCase('sudah ditutup', $error);
        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'status' => 'draft']);
    }

    public function test_manual_journal_post_in_closed_period_redirects_with_indonesian_error(): void
    {
        $this->actingAsAccountingUser(['journals.view', 'journals.create']);

        $docDate = now()->startOfMonth()->toDateString();
        $this->closePeriodForDate($docDate);

        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $expenseId = (int) DB::table('accounts')->where('code', '6.2.1')->value('id');

        $postResp = $this->from('/journals/manual/create')->post('/journals/manual', [
            'date' => $docDate,
            'description' => 'Closed period manual',
            'lines' => [
                ['account_id' => $expenseId, 'debit' => 100, 'credit' => 0],
                ['account_id' => $cashId, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $postResp->assertRedirect();
        $postResp->assertStatus(302);
        $postResp->assertSessionHas('error');
        $error = session('error');
        $this->assertIsString($error);
        $this->assertStringContainsStringIgnoringCase('sudah ditutup', $error);
    }

    public function test_manual_journal_unbalanced_lines_flash_validation_error_not_server_error(): void
    {
        $this->actingAsAccountingUser(['journals.view', 'journals.create']);

        $docDate = now()->toDateString();
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $expenseId = (int) DB::table('accounts')->where('code', '6.2.1')->value('id');

        $postResp = $this->from('/journals/manual/create')->post('/journals/manual', [
            'date' => $docDate,
            'description' => 'Unbalanced manual',
            'lines' => [
                ['account_id' => $expenseId, 'debit' => 100, 'credit' => 0],
                ['account_id' => $cashId, 'debit' => 0, 'credit' => 50],
            ],
        ]);

        $postResp->assertRedirect();
        $postResp->assertStatus(302);
        $postResp->assertSessionHas('error');
        $error = session('error');
        $this->assertIsString($error);
        $this->assertStringContainsString('belum lengkap atau tidak seimbang', $error);
    }
}
