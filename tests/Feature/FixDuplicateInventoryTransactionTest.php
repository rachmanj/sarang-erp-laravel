<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FixDuplicateInventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_invoice_option_removes_duplicate_purchase_rows_and_recalculates_stock(): void
    {
        $warehouseId = (int) DB::table('warehouses')->orderBy('id')->value('id');
        $categoryId = (int) DB::table('product_categories')->where('is_active', true)->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'DUP-PI-ITEM',
            'name' => 'Duplicate PI Item',
            'category_id' => $categoryId,
            'default_warehouse_id' => $warehouseId,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $invoiceId = DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => '71260300999',
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 15000,
            'status' => 'posted',
            'payment_method' => 'cash',
            'is_direct_purchase' => 1,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = (int) DB::table('accounts')->where('is_postable', true)->value('id');

        $lineId = DB::table('purchase_invoice_lines')->insertGetId([
            'invoice_id' => $invoiceId,
            'inventory_item_id' => $item->id,
            'account_id' => $accountId,
            'qty' => 5,
            'unit_price' => 3000,
            'net_amount' => 15000,
            'warehouse_id' => $warehouseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $keepId = DB::table('inventory_transactions')->insertGetId([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseId,
            'transaction_type' => 'purchase',
            'quantity' => 5,
            'unit_cost' => 3000,
            'total_cost' => 15000,
            'reference_type' => 'purchase_invoice',
            'reference_id' => $invoiceId,
            'purchase_invoice_line_id' => $lineId,
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $duplicateId = DB::table('inventory_transactions')->insertGetId([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseId,
            'transaction_type' => 'purchase',
            'quantity' => 5,
            'unit_cost' => 3000,
            'total_cost' => 15000,
            'reference_type' => 'purchase_invoice',
            'reference_id' => $invoiceId,
            'purchase_invoice_line_id' => null,
            'transaction_date' => now()->toDateString(),
            'created_at' => now()->addMinute(),
            'updated_at' => now()->addMinute(),
        ]);

        InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'available_quantity' => 10,
        ]);

        $result = $this->artisan('inventory:fix-duplicate-transaction', [
            '--invoice' => (string) $invoiceId,
            '--dry-run' => true,
        ]);

        $result->expectsOutputToContain('Delete duplicate txn #'.$duplicateId);
        $result->assertSuccessful();

        $this->artisan('inventory:fix-duplicate-transaction', [
            '--invoice' => (string) $invoiceId,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('inventory_transactions', ['id' => $keepId]);
        $this->assertDatabaseMissing('inventory_transactions', ['id' => $duplicateId]);

        $stock = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        $this->assertSame(5, (int) $stock->quantity_on_hand);
    }

    public function test_repair_valuation_uses_tolerant_fifo_when_historical_sales_exceed_layers(): void
    {
        $warehouseId = (int) DB::table('warehouses')->orderBy('id')->value('id');
        $categoryId = (int) DB::table('product_categories')->where('is_active', true)->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'DUP-FIFO-ITEM',
            'name' => 'Duplicate FIFO Item',
            'category_id' => $categoryId,
            'default_warehouse_id' => $warehouseId,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        DB::table('inventory_transactions')->insert([
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'purchase',
                'quantity' => 12,
                'unit_cost' => 100,
                'total_cost' => 1200,
                'transaction_date' => '2026-03-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'sale',
                'quantity' => -12,
                'unit_cost' => 100,
                'total_cost' => -1200,
                'transaction_date' => '2026-03-02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
        ]);

        $valuation = app(\App\Services\InventoryService::class)
            ->updateItemValuationAfterDataRepair($item->fresh());

        $this->assertSame(0, (int) $valuation->quantity_on_hand);
        $this->assertEqualsWithDelta(100.0, (float) $valuation->unit_cost, 0.01);
    }
}
