<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Documents\DocumentDeletionService;
use App\Services\Documents\DocumentType;
use App\Services\Documents\Exceptions\DocumentDeletionBlockedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentDeletionTest extends TestCase
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
            'ar.invoices.create',
            'ar.invoices.post',
            'ar.invoices.delete',
            'ar.receipts.view',
            'ar.receipts.create',
            'ar.receipts.post',
            'ar.receipts.delete',
            'sales-orders.view',
            'sales-orders.delete',
            'delivery-orders.delete',
            'purchase-orders.view',
            'purchase-orders.delete',
            'ap.invoices.delete',
            'ap.payments.delete',
            'goods-receipt-pos.delete',
        ]);
        $this->actingAs($this->user);
    }

    public function test_delete_preview_lists_cascade_documents_for_sales_order(): void
    {
        $soId = $this->createSalesOrder();
        $doId = $this->createDeliveryOrder($soId);
        $siId = $this->createSalesInvoiceLinkedToDeliveryOrder($doId, $soId);

        $response = $this->getJson(route('sales-orders.delete-preview', $soId));

        $response->assertOk();
        $types = collect($response->json('documents'))->pluck('type')->all();

        $this->assertContains(DocumentType::SALES_ORDER, $types);
        $this->assertContains(DocumentType::DELIVERY_ORDER, $types);
        $this->assertContains(DocumentType::SALES_INVOICE, $types);
        $this->assertSame($siId, collect($response->json('documents'))->firstWhere('type', DocumentType::SALES_INVOICE)['id']);
    }

    public function test_deleting_posted_sales_invoice_reverses_journal_and_removes_tax(): void
    {
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Delete test SI',
            'lines' => [
                [
                    'account_id' => $revenueId,
                    'description' => 'Taxable line',
                    'qty' => 1,
                    'unit_price' => 100000,
                    'tax_code_id' => $taxCodeId,
                ],
            ],
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));
        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $this->assertGreaterThan(0, DB::table('tax_transactions')->where('reference_type', 'sales_invoice')->where('reference_id', $invoiceId)->count());

        $deleteResponse = $this->delete(route('sales-invoices.destroy', $invoiceId));
        $deleteResponse->assertRedirect(route('sales-invoices.index'));

        $this->assertDatabaseMissing('sales_invoices', ['id' => $invoiceId]);
        $this->assertSame(0, DB::table('tax_transactions')->where('reference_type', 'sales_invoice')->where('reference_id', $invoiceId)->count());

        $originalJournalId = (int) DB::table('journals')
            ->where('source_type', 'sales_invoice')
            ->where('source_id', $invoiceId)
            ->orderBy('id')
            ->value('id');

        $this->assertGreaterThan(0, $originalJournalId);

        $reversalExists = DB::table('journals')
            ->where('source_type', 'sales_invoice')
            ->where('source_id', $invoiceId)
            ->where('description', 'like', 'Reversal of #'.$originalJournalId.'%')
            ->exists();

        $this->assertTrue($reversalExists);

        $allJournalIds = DB::table('journals')
            ->where('source_type', 'sales_invoice')
            ->where('source_id', $invoiceId)
            ->pluck('id');

        foreach ($allJournalIds as $journalId) {
            $sum = DB::table('journal_lines')->where('journal_id', $journalId)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
            $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
        }
    }

    public function test_deleting_sales_order_cascades_delivery_order_and_invoice(): void
    {
        $soId = $this->createSalesOrder();
        $doId = $this->createDeliveryOrder($soId);
        $siId = $this->createSalesInvoiceLinkedToDeliveryOrder($doId, $soId);

        $response = $this->delete(route('sales-orders.destroy', $soId));
        $response->assertRedirect(route('sales-orders.index'));

        $this->assertDatabaseMissing('sales_orders', ['id' => $soId]);
        $this->assertDatabaseMissing('delivery_orders', ['id' => $doId]);
        $this->assertDatabaseMissing('sales_invoices', ['id' => $siId]);
    }

    public function test_deleting_purchase_order_cascades_grpo_invoice_and_payment(): void
    {
        $poId = $this->createPurchaseOrder();
        $grpoId = $this->createGrpoForPurchaseOrder($poId);
        $piId = $this->createPurchaseInvoiceForGrpo($grpoId, $poId);
        $ppId = $this->createPurchasePaymentForInvoice($piId);

        $response = $this->delete(route('purchase-orders.destroy', $poId));
        $response->assertRedirect(route('purchase-orders.index'));

        $this->assertDatabaseMissing('purchase_orders', ['id' => $poId]);
        $this->assertDatabaseMissing('goods_receipt_po', ['id' => $grpoId]);
        $this->assertDatabaseMissing('purchase_invoices', ['id' => $piId]);
        $this->assertDatabaseMissing('purchase_payments', ['id' => $ppId]);
    }

    public function test_deleting_posted_document_in_closed_period_is_blocked(): void
    {
        $invoiceId = $this->createPostedSalesInvoiceRecord();

        DB::table('periods')->updateOrInsert(
            ['year' => (int) date('Y'), 'month' => (int) date('n')],
            ['is_closed' => true, 'closed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        $service = app(DocumentDeletionService::class);

        $this->expectException(DocumentDeletionBlockedException::class);
        $service->delete(DocumentType::SALES_INVOICE, $invoiceId);
    }

    public function test_single_delete_removes_only_document_and_keeps_base(): void
    {
        $soId = $this->createSalesOrder();
        $doId = $this->createDeliveryOrder($soId);
        $siId = $this->createSalesInvoiceLinkedToDeliveryOrder($doId, $soId);

        DB::table('delivery_orders')->where('id', $doId)->update([
            'closure_status' => 'closed',
            'closed_by_document_type' => 'sales_invoice',
            'closed_by_document_id' => $siId,
            'closed_at' => now(),
            'closed_by_user_id' => $this->user->id,
        ]);

        $service = app(DocumentDeletionService::class);
        $service->deleteSingle(DocumentType::SALES_INVOICE, $siId);

        $this->assertDatabaseMissing('sales_invoices', ['id' => $siId]);
        $this->assertDatabaseHas('delivery_orders', [
            'id' => $doId,
            'closure_status' => 'open',
            'closed_by_document_type' => null,
            'closed_by_document_id' => null,
        ]);
        $this->assertDatabaseHas('sales_orders', ['id' => $soId]);
        $this->assertDatabaseMissing('delivery_order_sales_invoice', [
            'delivery_order_id' => $doId,
            'sales_invoice_id' => $siId,
        ]);
    }

    public function test_single_delete_blocked_when_targets_exist(): void
    {
        $soId = $this->createSalesOrder();
        $doId = $this->createDeliveryOrder($soId);
        $this->createSalesInvoiceLinkedToDeliveryOrder($doId, $soId);

        $service = app(DocumentDeletionService::class);

        $this->expectException(DocumentDeletionBlockedException::class);
        $this->expectExceptionMessage('downstream documents');
        $service->deleteSingle(DocumentType::DELIVERY_ORDER, $doId);

        $this->assertDatabaseHas('delivery_orders', ['id' => $doId]);
    }

    public function test_single_delete_preview_shows_blocked_targets(): void
    {
        $soId = $this->createSalesOrder();
        $doId = $this->createDeliveryOrder($soId);
        $siId = $this->createSalesInvoiceLinkedToDeliveryOrder($doId, $soId);

        $response = $this->getJson(route('delivery-orders.delete-preview', $doId).'?mode=single');

        $response->assertOk();
        $response->assertJsonPath('mode', 'single');
        $response->assertJsonPath('blocked', true);
        $this->assertSame($siId, collect($response->json('targets'))->firstWhere('type', DocumentType::SALES_INVOICE)['id']);
    }

    public function test_single_delete_via_http_deletes_only_document(): void
    {
        $soId = $this->createSalesOrder();
        $doId = $this->createDeliveryOrder($soId);
        $siId = $this->createSalesInvoiceLinkedToDeliveryOrder($doId, $soId);

        $response = $this->delete(route('sales-invoices.destroy', $siId), ['mode' => 'single']);
        $response->assertRedirect(route('sales-invoices.index'));

        $this->assertDatabaseMissing('sales_invoices', ['id' => $siId]);
        $this->assertDatabaseHas('delivery_orders', ['id' => $doId]);
        $this->assertDatabaseHas('sales_orders', ['id' => $soId]);
    }

    private function createSalesOrder(): int
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        return (int) DB::table('sales_orders')->insertGetId([
            'order_no' => 'SO-DEL-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'confirmed',
            'approval_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDeliveryOrder(int $salesOrderId): int
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $warehouseId = (int) DB::table('warehouses')->orderBy('id')->value('id');

        return (int) DB::table('delivery_orders')->insertGetId([
            'do_number' => 'DO-DEL-'.uniqid(),
            'sales_order_id' => $salesOrderId,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouseId,
            'delivery_address' => 'Test delivery address',
            'planned_delivery_date' => now()->toDateString(),
            'status' => 'draft',
            'closure_status' => 'open',
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSalesInvoiceLinkedToDeliveryOrder(int $deliveryOrderId, int $salesOrderId): int
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoiceId = (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-DEL-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'sales_order_id' => $salesOrderId,
            'total_amount' => 100000,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'item_code' => 'DEL',
            'item_name' => 'Delete cascade item',
            'description' => 'Line',
            'qty' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('delivery_order_sales_invoice')->insert([
            'delivery_order_id' => $deliveryOrderId,
            'sales_invoice_id' => $invoiceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $invoiceId;
    }

    private function createPurchaseOrder(): int
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        return (int) DB::table('purchase_orders')->insertGetId([
            'order_no' => 'PO-DEL-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 200000,
            'status' => 'ordered',
            'approval_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGrpoForPurchaseOrder(int $purchaseOrderId): int
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');

        return (int) DB::table('goods_receipt_po')->insertGetId([
            'grn_no' => 'GRPO-DEL-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'purchase_order_id' => $purchaseOrderId,
            'total_amount' => 200000,
            'status' => 'received',
            'closure_status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPurchaseInvoiceForGrpo(int $grpoId, int $purchaseOrderId): int
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => 'PI-DEL-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'purchase_order_id' => $purchaseOrderId,
            'goods_receipt_id' => $grpoId,
            'total_amount' => 200000,
            'status' => 'draft',
            'payment_method' => 'credit',
            'is_direct_purchase' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('goods_receipt_po_purchase_invoice')->insert([
            'grpo_id' => $grpoId,
            'purchase_invoice_id' => $invoiceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $invoiceId;
    }

    private function createPurchasePaymentForInvoice(int $invoiceId): int
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $paymentId = (int) DB::table('purchase_payments')->insertGetId([
            'payment_no' => 'PP-DEL-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 200000,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_payment_allocations')->insert([
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'amount' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $paymentId;
    }

    private function createPostedSalesInvoiceRecord(): int
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoiceId = (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-CLOSED-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 50000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'item_code' => 'X',
            'item_name' => 'Closed period test',
            'description' => 'Line',
            'qty' => 1,
            'unit_price' => 50000,
            'amount' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalId = DB::table('journals')->insertGetId([
            'journal_no' => 'J-CLOSED-'.uniqid(),
            'date' => now()->toDateString(),
            'description' => 'Posted SI for closed period test',
            'source_type' => 'sales_invoice',
            'source_id' => $invoiceId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'debit' => 50000,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'debit' => 0,
                'credit' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $invoiceId;
    }
}
