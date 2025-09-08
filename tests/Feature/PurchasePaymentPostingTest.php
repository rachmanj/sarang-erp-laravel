<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $cashId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $vendorId = (int) DB::table('vendors')->value('id');

        $resp = $this->post('/purchase-payments', [
            'date' => now()->toDateString(),
            'vendor_id' => $vendorId,
            'description' => 'Payment',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 120],
            ],
        ]);
        $resp->assertRedirect();
        $location = $resp->headers->get('Location');
        $paymentId = (int) preg_replace('/[^0-9]/', '', (string) substr($location, strrpos($location, '/')));
        $this->assertDatabaseHas('purchase_payments', ['id' => $paymentId, 'total_amount' => 120.00]);

        $postResp = $this->post('/purchase-payments/' . $paymentId . '/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('purchase_payments', ['id' => $paymentId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'purchase_payment', 'source_id' => $paymentId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float)$sum->d, (float)$sum->c, 0.01);
    }
}
