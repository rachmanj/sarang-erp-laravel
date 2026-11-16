<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReceiptClosedPeriodFlashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.receipts.view', 'ar.receipts.create', 'ar.receipts.post']);
        $this->actingAs($user);
    }

    public function test_posting_receipt_in_closed_period_redirects_with_error_flash(): void
    {
        $receiptDate = now()->startOfMonth();
        DB::table('periods')->updateOrInsert(
            ['year' => (int) $receiptDate->format('Y'), 'month' => (int) $receiptDate->format('n')],
            ['is_closed' => true, 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-CLOSED-PERIOD-001',
            'date' => $receiptDate->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 150,
            'total_amount_foreign' => 150,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createResp = $this->post('/sales-receipts', [
            'date' => $receiptDate->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Receipt closed period test',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 150],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 150],
            ],
        ]);
        $createResp->assertRedirect();
        $location = $createResp->headers->get('Location');
        $receiptId = (int) preg_replace('/[^0-9]/', '', (string) substr($location, strrpos($location, '/')));

        $postResp = $this->post('/sales-receipts/'.$receiptId.'/post');

        $postResp->assertRedirect();
        $postResp->assertSessionHas('error');
        $error = session('error');
        $this->assertIsString($error);
        $this->assertStringContainsStringIgnoringCase('ditutup', $error);
        $this->assertDatabaseHas('sales_receipts', ['id' => $receiptId, 'status' => 'draft']);
    }
}
