<?php

namespace Tests\Feature;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseInvoiceMultiGrpoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.invoices.view', 'ap.invoices.create', 'accounts.view']);
        $this->actingAs($user);
    }

    /**
     * @return array{itemId: int, accountId: int, vendorId: int, warehouseId: int, entityId: int}
     */
    private function baseIds(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $item = InventoryItem::query()->create([
            'code' => 'TEST-MGRPO-'.uniqid(),
            'name' => 'Test multi-GRPO item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1500,
            'selling_price' => 2000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        return [
            'itemId' => (int) $item->id,
            'accountId' => (int) DB::table('accounts')->where('is_postable', 1)->orderBy('id')->value('id'),
            'vendorId' => (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id'),
            'warehouseId' => (int) $warehouse->id,
            'entityId' => (int) DB::table('company_entities')->where('code', '71')->value('id'),
        ];
    }

    private function createGrpoWithLine(string $grnNo, array $base, float $qty): GoodsReceiptPO
    {
        $grpo = GoodsReceiptPO::query()->create([
            'grn_no' => $grnNo,
            'date' => now()->toDateString(),
            'business_partner_id' => $base['vendorId'],
            'company_entity_id' => $base['entityId'],
            'warehouse_id' => $base['warehouseId'],
            'description' => 'Test '.$grnNo,
            'total_amount' => 0,
            'status' => 'received',
            'source_type' => 'manual',
        ]);

        $lineAmount = $qty * 100000;
        GoodsReceiptPOLine::query()->create([
            'grpo_id' => $grpo->id,
            'item_id' => $base['itemId'],
            'account_id' => $base['accountId'],
            'description' => 'Line '.$grnNo,
            'qty' => $qty,
            'unit_price' => 100000,
            'amount' => $lineAmount,
            'tax_code_id' => null,
        ]);

        $grpo->update(['total_amount' => $lineAmount]);

        return $grpo->fresh();
    }

    public function test_available_grpos_lists_all_company_entities_for_vendor(): void
    {
        $base = $this->baseIds();
        $otherEntityId = (int) DB::table('company_entities')
            ->where('id', '!=', $base['entityId'])
            ->orderBy('id')
            ->value('id');
        $this->assertNotSame(0, $otherEntityId);

        $a = $this->createGrpoWithLine('T-AV-A', $base, 1);
        $baseOther = $base;
        $baseOther['entityId'] = $otherEntityId;
        $b = $this->createGrpoWithLine('T-AV-B', $baseOther, 1);

        $response = $this->getJson(route('purchase-invoices.api.grpos-available', [
            'business_partner_id' => $base['vendorId'],
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_available_grpos_can_filter_by_company_entity(): void
    {
        $base = $this->baseIds();
        $otherEntityId = (int) DB::table('company_entities')
            ->where('id', '!=', $base['entityId'])
            ->orderBy('id')
            ->value('id');
        $this->assertNotSame(0, $otherEntityId);

        $a = $this->createGrpoWithLine('T-AV-F1', $base, 1);
        $baseOther = $base;
        $baseOther['entityId'] = $otherEntityId;
        $b = $this->createGrpoWithLine('T-AV-F2', $baseOther, 1);

        $response = $this->getJson(route('purchase-invoices.api.grpos-available', [
            'business_partner_id' => $base['vendorId'],
            'company_entity_id' => $base['entityId'],
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($a->id, $ids);
        $this->assertNotContains($b->id, $ids);
    }

    public function test_prefill_from_multiple_grpos_merges_lines(): void
    {
        $base = $this->baseIds();
        $a = $this->createGrpoWithLine('T-MGRPO-A', $base, 1);
        $b = $this->createGrpoWithLine('T-MGRPO-B', $base, 2);

        $res = $this->postJson(route('purchase-invoices.api.prefill-from-grpos'), [
            'business_partner_id' => $base['vendorId'],
            'grpo_ids' => [$a->id, $b->id],
        ]);

        $res->assertOk();
        $lines = $res->json('prefill.lines');
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('T-MGRPO-A', $res->json('prefill.description'));
        $this->assertStringContainsString('T-MGRPO-B', $res->json('prefill.description'));
    }

    public function test_store_links_multiple_grpos_via_pivot(): void
    {
        $base = $this->baseIds();
        $a = $this->createGrpoWithLine('T-MGRPO-S1', $base, 1);
        $b = $this->createGrpoWithLine('T-MGRPO-S2', $base, 1);

        $prefill = $this->postJson(route('purchase-invoices.api.prefill-from-grpos'), [
            'business_partner_id' => $base['vendorId'],
            'grpo_ids' => [$a->id, $b->id],
        ]);
        $prefill->assertOk();
        $lines = $prefill->json('prefill.lines');

        $payload = [
            'date' => now()->toDateString(),
            'business_partner_id' => $base['vendorId'],
            'company_entity_id' => $base['entityId'],
            'payment_method' => 'credit',
            'description' => 'Combined GRPO PI',
            'goods_receipt_ids' => [$a->id, $b->id],
            'lines' => array_map(static function ($l) use ($base) {
                return [
                    'inventory_item_id' => $base['itemId'],
                    'account_id' => $base['accountId'],
                    'warehouse_id' => $base['warehouseId'],
                    'description' => $l['description'] ?? 'x',
                    'qty' => $l['qty'],
                    'unit_price' => $l['unit_price'],
                    'tax_code_id' => null,
                ];
            }, $lines),
        ];

        $resp = $this->post('/purchase-invoices', $payload);
        $resp->assertRedirect();

        $piId = (int) DB::table('purchase_invoices')->orderByDesc('id')->value('id');
        $this->assertGreaterThan(0, $piId);

        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $piId,
            'goods_receipt_id' => null,
        ]);

        $this->assertDatabaseHas('goods_receipt_po_purchase_invoice', [
            'purchase_invoice_id' => $piId,
            'grpo_id' => $a->id,
        ]);
        $this->assertDatabaseHas('goods_receipt_po_purchase_invoice', [
            'purchase_invoice_id' => $piId,
            'grpo_id' => $b->id,
        ]);
    }

    public function test_store_single_grpo_closes_grpo_when_invoice_qty_covers_receipt(): void
    {
        $base = $this->baseIds();
        $grpo = $this->createGrpoWithLine('T-SINGLE-CLOSE', $base, 1);

        $prefill = $this->postJson(route('purchase-invoices.api.prefill-from-grpos'), [
            'business_partner_id' => $base['vendorId'],
            'grpo_ids' => [$grpo->id],
        ]);
        $prefill->assertOk();
        $lines = $prefill->json('prefill.lines');

        $payload = [
            'date' => now()->toDateString(),
            'business_partner_id' => $base['vendorId'],
            'company_entity_id' => $base['entityId'],
            'payment_method' => 'credit',
            'description' => 'From GRPO',
            'goods_receipt_id' => $grpo->id,
            'lines' => array_map(static function ($l) use ($base) {
                return [
                    'inventory_item_id' => $base['itemId'],
                    'account_id' => $base['accountId'],
                    'warehouse_id' => $base['warehouseId'],
                    'description' => $l['description'] ?? 'x',
                    'qty' => $l['qty'],
                    'unit_price' => $l['unit_price'],
                    'tax_code_id' => null,
                ];
            }, $lines),
        ];

        $resp = $this->post('/purchase-invoices', $payload);
        $resp->assertRedirect();

        $grpo->refresh();
        $this->assertSame('closed', $grpo->closure_status);
        $this->assertSame('purchase_invoice', $grpo->closed_by_document_type);
    }

    public function test_prefill_from_grpo_uses_linked_purchase_order_net_unit_price(): void
    {
        $base = $this->baseIds();
        $currencyId = (int) DB::table('currencies')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-PO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $base['vendorId'],
            'company_entity_id' => $base['entityId'],
            'warehouse_id' => $base['warehouseId'],
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'approved',
            'approval_status' => 'approved',
            'total_amount' => 60000,
        ]);

        PurchaseOrderLine::query()->create([
            'order_id' => $po->id,
            'account_id' => $base['accountId'],
            'inventory_item_id' => $base['itemId'],
            'qty' => 10,
            'base_quantity' => 10,
            'unit_conversion_factor' => 1,
            'received_qty' => 0,
            'pending_qty' => 10,
            'unit_price' => 6000,
            'unit_price_foreign' => 0,
            'amount' => 60000,
            'discount_amount' => 0,
            'discount_percentage' => 0,
            'net_amount' => 54000,
            'amount_foreign' => 0,
            'freight_cost' => 0,
            'handling_cost' => 0,
            'total_cost' => 0,
            'vat_rate' => 0,
            'wtax_rate' => 0,
        ]);

        $grpo = GoodsReceiptPO::query()->create([
            'grn_no' => 'T-GRPO-PO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $base['vendorId'],
            'company_entity_id' => $base['entityId'],
            'warehouse_id' => $base['warehouseId'],
            'purchase_order_id' => $po->id,
            'description' => 'Linked GRPO',
            'total_amount' => 10,
            'status' => 'received',
            'source_type' => 'manual',
        ]);

        GoodsReceiptPOLine::query()->create([
            'grpo_id' => $grpo->id,
            'item_id' => $base['itemId'],
            'account_id' => $base['accountId'],
            'description' => 'Wrong stored price',
            'qty' => 10,
            'unit_price' => 1,
            'amount' => 10,
            'tax_code_id' => null,
        ]);

        $res = $this->postJson(route('purchase-invoices.api.prefill-from-grpos'), [
            'business_partner_id' => $base['vendorId'],
            'grpo_ids' => [$grpo->id],
        ]);

        $res->assertOk();
        $this->assertSame(5400.0, (float) $res->json('prefill.lines.0.unit_price'));
    }
}
