<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DeliveryJournalService;
use App\Services\GRPOJournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalPreviewMatchesPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $user = User::factory()->create();
        $user->givePermissionTo([
            'ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post',
            'ar.receipts.view', 'ar.receipts.create', 'ar.receipts.post',
            'ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post',
            'ap.payments.view', 'ap.payments.create', 'ap.payments.post',
        ]);
        $this->actingAs($user);
    }

    /**
     * @return array<int, string>
     */
    private function lineFingerprints(array $lines): array
    {
        return collect($lines)
            ->map(fn ($line) => sprintf(
                '%d|%.2f|%.2f',
                (int) ($line['account_id'] ?? $line->account_id),
                round((float) ($line['debit'] ?? $line->debit), 2),
                round((float) ($line['credit'] ?? $line->credit), 2),
            ))
            ->sort()
            ->values()
            ->all();
    }

    private function assertPreviewMatchesPostedJournal(string $documentType, int $documentId, int $journalId): void
    {
        $previewResponse = $this->postJson("/api/documents/{$documentType}/{$documentId}/journal-preview", [
            'action_type' => 'post',
        ]);

        $previewResponse->assertOk();
        $previewResponse->assertJsonPath('success', true);
        $previewResponse->assertJsonPath('data.is_balanced', true);

        $previewLines = $previewResponse->json('data.lines');

        $postedLines = DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->get(['account_id', 'debit', 'credit'])
            ->map(fn ($row) => [
                'account_id' => (int) $row->account_id,
                'debit' => round((float) $row->debit, 2),
                'credit' => round((float) $row->credit, 2),
            ])
            ->all();

        $this->assertSame(
            $this->lineFingerprints($previewLines),
            $this->lineFingerprints($postedLines),
            "Preview lines for {$documentType} #{$documentId} do not match posted journal #{$journalId}"
        );
    }

    public function test_sales_invoice_preview_matches_posted_journal(): void
    {
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Preview match SI',
            'lines' => [
                ['account_id' => $revenueId, 'description' => 'Service', 'qty' => 1, 'unit_price' => 100000],
            ],
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));

        $previewBeforePost = $this->postJson("/api/documents/sales-invoice/{$invoiceId}/journal-preview");
        $previewBeforePost->assertOk();

        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'sales_invoice',
            'source_id' => $invoiceId,
        ])->value('id');

        $this->assertGreaterThan(0, $journalId);
        $this->assertPreviewMatchesPostedJournal('sales-invoice', $invoiceId, $journalId);
    }

    public function test_sales_receipt_preview_matches_posted_journal(): void
    {
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if (! $bankCoaId) {
            $bankCoaId = DB::table('accounts')->insertGetId([
                'code' => '1.1.1.02',
                'name' => 'Kas di Bank - Operasional',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-PREV-001',
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 500000,
            'total_amount_foreign' => 500000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->post('/sales-receipts', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Preview match SR',
            'lines' => [
                ['account_id' => $bankCoaId, 'description' => 'Bank', 'amount' => 500000],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 500000],
            ],
        ]);
        $resp->assertRedirect();

        $location = $resp->headers->get('Location');
        $receiptId = (int) preg_replace('/[^0-9]/', '', (string) substr($location, strrpos($location, '/')));

        $this->post('/sales-receipts/'.$receiptId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'sales_receipt',
            'source_id' => $receiptId,
        ])->value('id');

        $this->assertGreaterThan(0, $journalId);
        $this->assertPreviewMatchesPostedJournal('sales-receipt', $receiptId, $journalId);
    }

    public function test_purchase_payment_preview_matches_posted_journal(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $paymentId = DB::table('purchase_payments')->insertGetId([
            'payment_no' => 'PP-PREV-001',
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'created_by' => $userId,
            'description' => 'Preview match PP',
            'status' => 'draft',
            'total_amount' => 250000,
            'total_amount_foreign' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_payment_lines')->insert([
            'payment_id' => $paymentId,
            'account_id' => $cashId,
            'description' => 'Cash',
            'amount' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/purchase-payments/'.$paymentId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'purchase_payment',
            'source_id' => $paymentId,
        ])->value('id');

        $this->assertGreaterThan(0, $journalId);
        $this->assertPreviewMatchesPostedJournal('purchase-payment', $paymentId, $journalId);
    }

    public function test_purchase_invoice_with_ppn_preview_matches_posted_journal(): void
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->orderBy('id')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_IN')->value('id');
        $this->assertGreaterThan(0, $taxCodeId);

        $item = InventoryItem::query()->create([
            'code' => 'TEST-PREV-PI-'.uniqid(),
            'name' => 'Preview PI item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $grpo = GoodsReceiptPO::query()->create([
            'grn_no' => 'T-PREV-GRPO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'description' => 'GRPO for preview PI',
            'total_amount' => 100000,
            'status' => 'received',
            'source_type' => 'manual',
        ]);

        GoodsReceiptPOLine::query()->create([
            'grpo_id' => $grpo->id,
            'item_id' => $item->id,
            'account_id' => $accountId,
            'description' => 'GRPO line',
            'qty' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
            'tax_code_id' => null,
        ]);

        $prefill = $this->postJson(route('purchase-invoices.api.prefill-from-grpos'), [
            'business_partner_id' => $vendorId,
            'grpo_ids' => [$grpo->id],
        ]);
        $prefill->assertOk();
        $lines = $prefill->json('prefill.lines');

        $resp = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'payment_method' => 'credit',
            'description' => 'PI with PPN preview',
            'goods_receipt_id' => $grpo->id,
            'lines' => array_map(static function ($l) use ($item, $accountId, $warehouse, $taxCodeId) {
                return [
                    'inventory_item_id' => $item->id,
                    'account_id' => $accountId,
                    'warehouse_id' => $warehouse->id,
                    'description' => $l['description'] ?? 'line',
                    'qty' => $l['qty'],
                    'unit_price' => $l['unit_price'],
                    'tax_code_id' => $taxCodeId,
                ];
            }, $lines),
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) DB::table('purchase_invoices')->orderByDesc('id')->value('id');
        $this->assertGreaterThan(0, $invoiceId);

        $previewBeforePost = $this->postJson("/api/documents/purchase-invoice/{$invoiceId}/journal-preview");
        $previewBeforePost->assertOk();
        $previewBeforePost->assertJsonPath('data.is_balanced', true);

        $ppnLine = collect($previewBeforePost->json('data.lines'))
            ->first(fn ($line) => ($line['account_code'] ?? '') === '1.1.4.01');
        $this->assertNotNull($ppnLine, 'Preview should include PPN Masukan line');
        $this->assertGreaterThan(0, (float) ($ppnLine['debit'] ?? 0));

        $this->post('/purchase-invoices/'.$invoiceId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'purchase_invoice',
            'source_id' => $invoiceId,
        ])->value('id');

        $this->assertGreaterThan(0, $journalId);
        $this->assertPreviewMatchesPostedJournal('purchase-invoice', $invoiceId, $journalId);
    }

    public function test_grpo_preview_matches_posted_journal(): void
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'TEST-PREV-GRPO-'.uniqid(),
            'name' => 'Preview GRPO item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 5000,
            'selling_price' => 6000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $grpo = GoodsReceiptPO::query()->create([
            'grn_no' => 'T-PREV-JRN-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'description' => 'GRPO preview match',
            'total_amount' => 75000,
            'status' => 'received',
            'source_type' => 'manual',
        ]);

        GoodsReceiptPOLine::query()->create([
            'grpo_id' => $grpo->id,
            'item_id' => $item->id,
            'account_id' => $accountId,
            'description' => 'GRPO line',
            'qty' => 3,
            'unit_price' => 25000,
            'amount' => 75000,
            'tax_code_id' => null,
        ]);

        $previewBeforePost = $this->postJson("/api/documents/goods-receipt-po/{$grpo->id}/journal-preview");
        $previewBeforePost->assertOk();
        $previewBeforePost->assertJsonPath('data.is_balanced', true);

        $journal = app(GRPOJournalService::class)->createJournalEntries($grpo);
        $this->assertGreaterThan(0, $journal->id);

        $this->assertPreviewMatchesPostedJournal('goods-receipt-po', $grpo->id, $journal->id);
    }

    public function test_delivery_order_preview_matches_revenue_recognition_journal(): void
    {
        foreach (['1.1.3.01', '4.1.1.01', '5.1.01', '1.1.2.04'] as $accountCode) {
            DB::table('accounts')->where('code', $accountCode)->update(['is_postable' => 1]);
        }

        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'TEST-PREV-DO-'.uniqid(),
            'name' => 'Preview DO item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 8000,
            'selling_price' => 12000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $so = SalesOrder::query()->create([
            'order_no' => 'T-DO-PREV-SO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
            'total_amount' => 100000,
            'created_by' => $userId,
        ]);

        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $revenueAccountId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'qty' => 2,
            'delivered_qty' => 0,
            'pending_qty' => 2,
            'unit_price' => 50000,
            'amount' => 100000,
        ]);

        $do = DeliveryOrder::query()->create([
            'do_number' => 'T-DO-PREV-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Test delivery address',
            'planned_delivery_date' => now()->toDateString(),
            'actual_delivery_date' => now()->toDateString(),
            'status' => 'delivered',
            'approval_status' => 'approved',
            'created_by' => $userId,
        ]);

        DeliveryOrderLine::query()->create([
            'delivery_order_id' => $do->id,
            'sales_order_line_id' => $soLine->id,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => 2,
            'delivered_qty' => 2,
            'unit_price' => 50000,
            'amount' => 100000,
            'status' => 'delivered',
        ]);

        $previewBeforePost = $this->postJson("/api/documents/delivery-order/{$do->id}/journal-preview");
        $previewBeforePost->assertOk();
        $previewBeforePost->assertJsonPath('data.is_balanced', true);

        $journalId = app(DeliveryJournalService::class)->createRevenueRecognition($do->fresh(['lines.inventoryItem']));
        $this->assertGreaterThan(0, $journalId);

        $this->assertPreviewMatchesPostedJournal('delivery-order', $do->id, $journalId);
    }
}
