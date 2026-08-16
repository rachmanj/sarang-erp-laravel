<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchasePaymentPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.payments.view', 'ap.payments.create', 'ap.payments.post']);
        $this->actingAs($user);
    }

    public function test_posting_payment_creates_balanced_journal(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => 'PI-POST-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 120.00,
            'total_amount_foreign' => 120.00,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->post('/purchase-payments', [
            'date' => now()->toDateString(),
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'description' => 'Payment',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 120],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 120],
            ],
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHasNoErrors();

        $paymentId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));
        $this->assertDatabaseHas('purchase_payments', ['id' => $paymentId, 'total_amount' => 120.00]);

        $postResp = $this->post('/purchase-payments/'.$paymentId.'/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('purchase_payments', ['id' => $paymentId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'purchase_payment', 'source_id' => $paymentId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }
}
