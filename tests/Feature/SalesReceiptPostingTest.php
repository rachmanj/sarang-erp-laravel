<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SalesReceiptPostingTest extends TestCase
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

    public function test_posting_receipt_creates_balanced_journal(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $customerId = (int) DB::table('customers')->value('id');

        $resp = $this->post('/sales-receipts', [
            'date' => now()->toDateString(),
            'customer_id' => $customerId,
            'description' => 'Receipt',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 150],
            ],
        ]);
        $resp->assertRedirect();
        $location = $resp->headers->get('Location');
        $receiptId = (int) preg_replace('/[^0-9]/', '', (string) substr($location, strrpos($location, '/')));
        $this->assertDatabaseHas('sales_receipts', ['id' => $receiptId, 'total_amount' => 150.00]);

        $postResp = $this->post('/sales-receipts/' . $receiptId . '/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('sales_receipts', ['id' => $receiptId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_receipt', 'source_id' => $receiptId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float)$sum->d, (float)$sum->c, 0.01);
    }
}
