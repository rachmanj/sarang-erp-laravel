<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentOpenStateFilterTest extends TestCase
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
            'ar.invoices.view',
            'sales-orders.view',
            'purchase-orders.view',
        ]);
        $this->actingAs($this->user);
    }

    public function test_sales_invoice_open_filter_excludes_fully_receipted(): void
    {
        $openId = $this->createPostedSalesInvoice(500000, allocated: 0);
        $closedId = $this->createPostedSalesInvoice(300000, allocated: 300000);

        $openRows = $this->fetchSalesInvoiceDataIds('open');
        $closedRows = $this->fetchSalesInvoiceDataIds('closed');

        $this->assertContains($openId, $openRows);
        $this->assertNotContains($closedId, $openRows);
        $this->assertContains($closedId, $closedRows);
        $this->assertNotContains($openId, $closedRows);
    }

    public function test_grpo_open_filter_excludes_invoiced_grpo(): void
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');

        $openGrpoId = (int) DB::table('goods_receipt_po')->insertGetId([
            'grn_no' => 'GRPO-OPEN-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'status' => 'received',
            'total_amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $closedGrpoId = (int) DB::table('goods_receipt_po')->insertGetId([
            'grn_no' => 'GRPO-CLOSED-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'status' => 'received',
            'total_amount' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currencyId = (int) DB::table('currencies')->value('id');
        $piId = (int) DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => 'PI-GRPO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'currency_id' => $currencyId,
            'company_entity_id' => $entityId,
            'goods_receipt_id' => $closedGrpoId,
            'total_amount' => 200000,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('goods_receipt_po_purchase_invoice')->insert([
            'purchase_invoice_id' => $piId,
            'grpo_id' => $closedGrpoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $openRows = $this->fetchGrpoDataIds('open');
        $closedRows = $this->fetchGrpoDataIds('closed');

        $this->assertContains($openGrpoId, $openRows);
        $this->assertNotContains($closedGrpoId, $openRows);
        $this->assertContains($closedGrpoId, $closedRows);
        $this->assertNotContains($openGrpoId, $closedRows);
    }

    public function test_purchase_order_open_filter_excludes_fully_received_lines(): void
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $openPoId = $this->createPurchaseOrderWithLine($vendorId, $entityId, $accountId, qty: 10, receivedQty: 4);
        $closedPoId = $this->createPurchaseOrderWithLine($vendorId, $entityId, $accountId, qty: 10, receivedQty: 10);

        $openRows = $this->fetchPurchaseOrderDataIds('open');
        $closedRows = $this->fetchPurchaseOrderDataIds('closed');

        $this->assertContains($openPoId, $openRows);
        $this->assertNotContains($closedPoId, $openRows);
        $this->assertContains($closedPoId, $closedRows);
        $this->assertNotContains($openPoId, $closedRows);
    }

    public function test_sales_order_open_filter_excludes_fully_delivered_lines(): void
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $openSoId = $this->createSalesOrderWithLine($customerId, $entityId, $accountId, qty: 5, deliveredQty: 2);
        $closedSoId = $this->createSalesOrderWithLine($customerId, $entityId, $accountId, qty: 5, deliveredQty: 5);

        $openOrderNo = DB::table('sales_orders')->where('id', $openSoId)->value('order_no');
        $closedOrderNo = DB::table('sales_orders')->where('id', $closedSoId)->value('order_no');

        $openRows = $this->fetchSalesOrderDataOrderNos('open');
        $closedRows = $this->fetchSalesOrderDataOrderNos('closed');

        $this->assertContains($openOrderNo, $openRows);
        $this->assertNotContains($closedOrderNo, $openRows);
        $this->assertContains($closedOrderNo, $closedRows);
        $this->assertNotContains($openOrderNo, $closedRows);
    }

    /**
     * @return array<int, int>
     */
    private function fetchSalesInvoiceDataIds(string $openState): array
    {
        $response = $this->getJson('/sales-invoices/data?'.$this->dataTableParams(['open_state' => $openState]));
        $response->assertOk();

        return array_map('intval', array_column($response->json('data') ?? [], 'id'));
    }

    /**
     * @return array<int, int>
     */
    private function fetchGrpoDataIds(string $openState): array
    {
        $response = $this->getJson('/goods-receipt-pos/data?'.$this->dataTableParams(['open_state' => $openState]));
        $response->assertOk();

        return array_map('intval', array_column($response->json('data') ?? [], 'id'));
    }

    /**
     * @return array<int, int>
     */
    private function fetchPurchaseOrderDataIds(string $openState): array
    {
        $response = $this->getJson('/purchase-orders/data?'.$this->dataTableParams(['open_state' => $openState]));
        $response->assertOk();

        return array_map('intval', array_column($response->json('data') ?? [], 'id'));
    }

    /**
     * @return array<int, string>
     */
    private function fetchSalesOrderDataOrderNos(string $openState): array
    {
        $response = $this->getJson('/sales-orders/data?'.$this->dataTableParams(['open_state' => $openState]));
        $response->assertOk();

        return array_column($response->json('data') ?? [], 'order_no');
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function dataTableParams(array $extra = []): string
    {
        return http_build_query(array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ], $extra));
    }

    private function createPostedSalesInvoice(float $totalAmount, float $allocated): int
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoiceId = (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-OC-'.uniqid(),
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
            'item_code' => 'OC-ITEM',
            'item_name' => 'Open Closed Test',
            'qty' => 1,
            'unit_price' => $totalAmount,
            'amount' => $totalAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($allocated > 0) {
            $receiptId = (int) DB::table('sales_receipts')->insertGetId([
                'receipt_no' => 'SR-OC-'.uniqid(),
                'date' => now()->toDateString(),
                'business_partner_id' => $customerId,
                'company_entity_id' => $entityId,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'total_amount' => $allocated,
                'status' => 'posted',
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sales_receipt_allocations')->insert([
                'receipt_id' => $receiptId,
                'invoice_id' => $invoiceId,
                'amount' => $allocated,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $invoiceId;
    }

    private function createPurchaseOrderWithLine(int $vendorId, int $entityId, int $accountId, float $qty, float $receivedQty): int
    {
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $poId = (int) DB::table('purchase_orders')->insertGetId([
            'order_no' => 'PO-OC-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'total_amount' => $qty * 1000,
            'status' => 'ordered',
            'approval_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_order_lines')->insert([
            'order_id' => $poId,
            'account_id' => $accountId,
            'qty' => $qty,
            'received_qty' => $receivedQty,
            'pending_qty' => max(0, $qty - $receivedQty),
            'unit_price' => 1000,
            'amount' => $qty * 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $poId;
    }

    private function createSalesOrderWithLine(int $customerId, int $entityId, int $accountId, float $qty, float $deliveredQty): int
    {
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $soId = (int) DB::table('sales_orders')->insertGetId([
            'order_no' => 'SO-OC-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'total_amount' => $qty * 1000,
            'status' => 'confirmed',
            'approval_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_order_lines')->insert([
            'order_id' => $soId,
            'account_id' => $accountId,
            'qty' => $qty,
            'delivered_qty' => $deliveredQty,
            'pending_qty' => max(0, $qty - $deliveredQty),
            'unit_price' => 1000,
            'amount' => $qty * 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $soId;
    }
}
