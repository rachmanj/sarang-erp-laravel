<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateTargetDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'ap.invoices.view',
            'ap.payments.create',
            'ap.payments.view',
            'ar.invoices.view',
            'ar.receipts.create',
            'ar.receipts.view',
        ]);
        $this->actingAs($this->user);
    }

    public function test_posted_purchase_invoice_show_displays_create_payment_button(): void
    {
        $invoiceId = $this->createPostedPurchaseInvoice([
            'payment_method' => 'credit',
            'is_direct_purchase' => 0,
            'total_amount' => 500000,
        ]);

        $response = $this->get(route('purchase-invoices.show', $invoiceId));

        $response->assertOk();
        $response->assertSee('Create Payment', false);
        $response->assertSee(route('purchase-payments.create', ['purchase_invoice_id' => $invoiceId]), false);
    }

    public function test_cash_purchase_invoice_show_hides_create_payment_button(): void
    {
        $invoiceId = $this->createPostedPurchaseInvoice([
            'payment_method' => 'cash',
            'is_direct_purchase' => 0,
            'total_amount' => 500000,
        ]);

        $response = $this->get(route('purchase-invoices.show', $invoiceId));

        $response->assertOk();
        $response->assertDontSee('Create Payment', false);
    }

    public function test_purchase_payment_create_prefills_from_purchase_invoice_query_param(): void
    {
        $invoiceId = $this->createPostedPurchaseInvoice([
            'payment_method' => 'credit',
            'is_direct_purchase' => 0,
            'total_amount' => 750000,
        ]);

        $response = $this->get(route('purchase-payments.create', ['purchase_invoice_id' => $invoiceId]));

        $response->assertOk();
        $response->assertViewHas('prefill', fn ($prefill) => is_array($prefill)
            && isset($prefill['allocations'][(string) $invoiceId])
            && (float) $prefill['allocations'][(string) $invoiceId] === 750000.0);
    }

    public function test_posted_sales_invoice_show_displays_create_receipt_button(): void
    {
        $invoiceId = $this->createPostedSalesInvoice(300000);

        $response = $this->get(route('sales-invoices.show', $invoiceId));

        $response->assertOk();
        $response->assertSee('Create Receipt', false);
        $response->assertSee(route('sales-receipts.create', ['sales_invoice_id' => $invoiceId]), false);
    }

    public function test_sales_receipt_create_prefills_from_sales_invoice_query_param(): void
    {
        $invoiceId = $this->createPostedSalesInvoice(420000);

        $response = $this->get(route('sales-receipts.create', ['sales_invoice_id' => $invoiceId]));

        $response->assertOk();
        $response->assertViewHas('prefill', fn ($prefill) => is_array($prefill)
            && isset($prefill['allocations'][(string) $invoiceId])
            && (float) $prefill['allocations'][(string) $invoiceId] === 420000.0);
    }

    public function test_fully_allocated_sales_invoice_hides_create_receipt_button(): void
    {
        $invoiceId = $this->createPostedSalesInvoice(100000);
        $customerId = (int) DB::table('sales_invoices')->where('id', $invoiceId)->value('business_partner_id');
        $entityId = (int) DB::table('sales_invoices')->where('id', $invoiceId)->value('company_entity_id');
        $currencyId = (int) DB::table('sales_invoices')->where('id', $invoiceId)->value('currency_id');
        $receiptId = DB::table('sales_receipts')->insertGetId([
            'receipt_no' => 'SR-TEST-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sales_receipt_allocations')->insert([
            'receipt_id' => $receiptId,
            'invoice_id' => $invoiceId,
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('sales-invoices.show', $invoiceId));

        $response->assertOk();
        $response->assertDontSee('Create Receipt', false);
    }

    private function createPostedPurchaseInvoice(array $overrides = []): int
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');

        return (int) DB::table('purchase_invoices')->insertGetId(array_merge([
            'invoice_no' => 'PI-TGT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'currency_id' => $currencyId,
            'company_entity_id' => $entityId,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
            'payment_method' => 'credit',
            'is_direct_purchase' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createPostedSalesInvoice(float $totalAmount): int
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoiceId = (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-TGT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => $totalAmount,
            'total_amount_foreign' => $totalAmount,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'item_code' => 'TGT-ITEM',
            'item_name' => 'Target Document Test Item',
            'description' => 'Test line',
            'qty' => 1,
            'unit_price' => $totalAmount,
            'amount' => $totalAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $invoiceId;
    }
}
