<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReceiptUpdateTest extends TestCase
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

    private function createDraftReceiptForCustomer(int $customerId, int $entityId, int $cashId): int
    {
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-TEST-UPD-'.str_replace('.', '', uniqid('', true)),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 500,
            'total_amount_foreign' => 500,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->post('/sales-receipts', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Original',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 100],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 100],
            ],
        ]);
        $resp->assertRedirect();
        $location = (string) $resp->headers->get('Location');

        return (int) preg_replace('/[^0-9]/', '', substr($location, strrpos($location, '/')));
    }

    public function test_draft_sales_receipt_can_be_updated(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $receiptId = $this->createDraftReceiptForCustomer($customerId, $entityId, $cashId);

        $invoiceId = (int) DB::table('sales_receipt_allocations')
            ->where('receipt_id', $receiptId)
            ->value('invoice_id');

        $upd = $this->put('/sales-receipts/'.$receiptId, [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Updated note',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 150],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 150],
            ],
        ]);

        $upd->assertRedirect(route('sales-receipts.show', $receiptId, false));

        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'description' => 'Updated note',
            'total_amount' => 150.00,
            'status' => 'draft',
        ]);

        $lineSum = (float) DB::table('sales_receipt_lines')->where('receipt_id', $receiptId)->sum('amount');
        $this->assertEqualsWithDelta(150.0, $lineSum, 0.01);

        $allocSum = (float) DB::table('sales_receipt_allocations')->where('receipt_id', $receiptId)->sum('amount');
        $this->assertEqualsWithDelta(150.0, $allocSum, 0.01);
    }

    public function test_posted_sales_receipt_cannot_be_updated_via_edit_or_put(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $receiptId = $this->createDraftReceiptForCustomer($customerId, $entityId, $cashId);

        $this->post('/sales-receipts/'.$receiptId.'/post')->assertRedirect();

        $invoiceId = (int) DB::table('sales_receipt_allocations')
            ->where('receipt_id', $receiptId)
            ->value('invoice_id');

        $this->get('/sales-receipts/'.$receiptId.'/edit')
            ->assertRedirect(route('sales-receipts.show', $receiptId, false));

        $this->put('/sales-receipts/'.$receiptId, [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Hack',
            'lines' => [
                ['account_id' => $cashId, 'amount' => 1],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 1],
            ],
        ])->assertRedirect(route('sales-receipts.show', $receiptId, false));

        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'description' => 'Original',
            'status' => 'posted',
        ]);
    }

    public function test_update_rejects_mismatched_totals(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $receiptId = $this->createDraftReceiptForCustomer($customerId, $entityId, $cashId);

        $invoiceId = (int) DB::table('sales_receipt_allocations')
            ->where('receipt_id', $receiptId)
            ->value('invoice_id');

        $this->from(route('sales-receipts.edit', $receiptId, false))
            ->put('/sales-receipts/'.$receiptId, [
                'date' => now()->toDateString(),
                'business_partner_id' => $customerId,
                'company_entity_id' => $entityId,
                'lines' => [
                    ['account_id' => $cashId, 'amount' => 50],
                ],
                'allocations' => [
                    ['invoice_id' => $invoiceId, 'amount' => 100],
                ],
            ])
            ->assertRedirect(route('sales-receipts.edit', $receiptId, false))
            ->assertSessionHasErrors('lines');

        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'description' => 'Original',
            'total_amount' => 100,
        ]);
    }
}
