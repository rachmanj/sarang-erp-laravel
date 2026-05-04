<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Warehouse;
use App\Services\GrpoLinePurchaseOrderPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GrpoLinePurchaseOrderPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @return array{item: InventoryItem, accountId: int, vendorId: int, warehouseId: int, entityId: int}
     */
    private function itemContext(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $item = InventoryItem::query()->create([
            'code' => 'TEST-GRPO-PR-'.uniqid(),
            'name' => 'Test GRPO pricing item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 123.45,
            'selling_price' => 200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        return [
            'item' => $item,
            'accountId' => (int) DB::table('accounts')->where('is_postable', 1)->orderBy('id')->value('id'),
            'vendorId' => (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id'),
            'warehouseId' => (int) $warehouse->id,
            'entityId' => (int) DB::table('company_entities')->where('code', '71')->value('id'),
        ];
    }

    public function test_uses_po_effective_unit_price_when_po_linked(): void
    {
        $ctx = $this->itemContext();
        $currencyId = (int) DB::table('currencies')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-GRPO-P-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $ctx['vendorId'],
            'company_entity_id' => $ctx['entityId'],
            'warehouse_id' => $ctx['warehouseId'],
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'approved',
            'approval_status' => 'approved',
            'total_amount' => 54000,
        ]);

        PurchaseOrderLine::query()->create([
            'order_id' => $po->id,
            'account_id' => $ctx['accountId'],
            'inventory_item_id' => $ctx['item']->id,
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

        $service = app(GrpoLinePurchaseOrderPricingService::class);

        $econ = $service->economicsForIncomingGrpoLine(
            $po->id,
            $ctx['item']->id,
            $ctx['item'],
            0
        );

        $this->assertSame(5400.0, $econ['unit_price']);
        $this->assertSame($ctx['accountId'], $econ['account_id']);
    }

    public function test_uses_inventory_purchase_price_when_no_po_linked(): void
    {
        $ctx = $this->itemContext();
        $service = app(GrpoLinePurchaseOrderPricingService::class);

        $econ = $service->economicsForIncomingGrpoLine(
            null,
            $ctx['item']->id,
            $ctx['item'],
            0
        );

        $this->assertSame(123.45, $econ['unit_price']);
        $this->assertSame(0, $econ['account_id']);
        $this->assertNull($econ['tax_code_id']);
    }

    public function test_validation_when_linked_po_has_no_matching_item(): void
    {
        $ctx = $this->itemContext();
        $currencyId = (int) DB::table('currencies')->value('id');

        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $itemOther = InventoryItem::query()->create([
            'code' => 'TEST-GRPO-P2-'.uniqid(),
            'name' => 'Other item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 50,
            'selling_price' => 75,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-GRPO-P3-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $ctx['vendorId'],
            'company_entity_id' => $ctx['entityId'],
            'warehouse_id' => $ctx['warehouseId'],
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'approved',
            'approval_status' => 'approved',
            'total_amount' => 54000,
        ]);

        PurchaseOrderLine::query()->create([
            'order_id' => $po->id,
            'account_id' => $ctx['accountId'],
            'inventory_item_id' => $ctx['item']->id,
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

        $service = app(GrpoLinePurchaseOrderPricingService::class);

        $this->expectException(ValidationException::class);
        $service->economicsForIncomingGrpoLine(
            $po->id,
            $itemOther->id,
            $itemOther,
            0
        );
    }
}
