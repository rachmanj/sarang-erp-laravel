<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApInvoicePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post', 'accounts.view']);
        $this->actingAs($user);
    }

    public function test_posting_purchase_invoice_creates_balanced_journal(): void
    {
        $expenseId = (int) DB::table('accounts')->where('code', '6.2.1')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');

        $resp = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'payment_method' => 'credit',
            'description' => 'Test AP',
            'lines' => [
                ['account_id' => $expenseId, 'description' => 'Supplies', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHasNoErrors();

        $invoiceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));
        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'total_amount' => 100.00]);

        $postResp = $this->post('/purchase-invoices/'.$invoiceId.'/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'purchase_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }
}
