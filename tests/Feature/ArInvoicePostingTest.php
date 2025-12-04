<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ArInvoicePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post']);
        $this->actingAs($user);
    }

    public function test_posting_invoice_creates_balanced_journal(): void
    {
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');

        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Test AR',
            'lines' => [
                ['account_id' => $revenueId, 'description' => 'Service', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'total_amount' => 100.00]);

        $postResp = $this->post('/sales-invoices/' . $invoiceId . '/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float)$sum->d, (float)$sum->c, 0.01);
    }
}
