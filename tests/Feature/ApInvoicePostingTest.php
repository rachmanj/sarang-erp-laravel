<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApInvoicePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post']);
        $this->actingAs($user);
    }

    public function test_posting_purchase_invoice_creates_balanced_journal(): void
    {
        $expenseId = (int) DB::table('accounts')->where('code', '5.2.7')->value('id');
        $vendorId = (int) DB::table('vendors')->value('id');

        $resp = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'vendor_id' => $vendorId,
            'description' => 'Test AP',
            'lines' => [
                ['account_id' => $expenseId, 'description' => 'Supplies', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $resp->assertRedirect();

        $location = $resp->headers->get('Location');
        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) substr($location, strrpos($location, '/')));
        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'total_amount' => 100.00]);

        $postResp = $this->post('/purchase-invoices/' . $invoiceId . '/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'purchase_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float)$sum->d, (float)$sum->c, 0.01);
    }
}
