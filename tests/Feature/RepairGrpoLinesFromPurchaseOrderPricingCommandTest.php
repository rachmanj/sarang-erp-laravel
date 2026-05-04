<?php

namespace Tests\Feature;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairGrpoLinesFromPurchaseOrderPricingCommandTest extends TestCase
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
    private function contexts(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $item = InventoryItem::query()->create([
            'code' => 'TEST-REPAIR-G-'.uniqid(),
            'name' => 'Test repair GRPO',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 777,
            'selling_price' => 888,
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

    private function linkedPoAndGrpoWithWrongPricing(): GoodsReceiptPO
    {
        $ctx = $this->contexts();
        $currencyId = (int) DB::table('currencies')->value('id');

        $po = PurchaseOrder::query()->create([
            'order_no' => 'T-REPAIR-P-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $ctx['vendorId'],
            'company_entity_id' => $ctx['entityId'],
            'warehouse_id' => $ctx['warehouseId'],
            'currency_id' => $currencyId,
            'order_type' => 'item',
            'status' => 'approved',
            'approval_status' => 'approved',
            'total_amount' => 60000,
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

        $grpo = GoodsReceiptPO::query()->create([
            'grn_no' => 'T-REPAIR-G-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $ctx['vendorId'],
            'company_entity_id' => $ctx['entityId'],
            'warehouse_id' => $ctx['warehouseId'],
            'purchase_order_id' => $po->id,
            'description' => null,
            'total_amount' => 999,
            'status' => 'received',
            'source_type' => 'manual',
        ]);

        GoodsReceiptPOLine::query()->create([
            'grpo_id' => $grpo->id,
            'item_id' => $ctx['item']->id,
            'account_id' => 0,
            'description' => null,
            'qty' => 10,
            'unit_price' => 111,
            'amount' => 1110,
            'tax_code_id' => null,
        ]);

        return $grpo->fresh(['lines']);
    }

    public function test_command_rewrite_grpo_lines_from_po_without_dry_run(): void
    {
        $grpo = $this->linkedPoAndGrpoWithWrongPricing();

        $exitCode = Artisan::call('grpo:repair-lines-from-po-pricing', [
            '--grpo' => (string) $grpo->id,
        ]);

        $this->assertSame(0, $exitCode);

        $line = GoodsReceiptPOLine::query()->where('grpo_id', $grpo->id)->firstOrFail();

        $this->assertSame(5400.0, (float) $line->unit_price);
        $this->assertSame(54000.0, (float) $line->amount);
        $grpo->refresh();
        $this->assertSame(54000.0, (float) $grpo->total_amount);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $grpo = $this->linkedPoAndGrpoWithWrongPricing();

        $exitCode = Artisan::call('grpo:repair-lines-from-po-pricing', [
            '--grpo' => (string) $grpo->id,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $line = GoodsReceiptPOLine::query()->where('grpo_id', $grpo->id)->firstOrFail();

        $this->assertSame(111.0, (float) $line->unit_price);
        $this->assertSame(1110.0, (float) $line->amount);
        $grpo->refresh();
        $this->assertSame(999.0, (float) $grpo->total_amount);
    }
}
