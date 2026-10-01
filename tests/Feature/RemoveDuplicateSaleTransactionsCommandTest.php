<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveDuplicateSaleTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ledgerStockSum(int $itemId): int
    {
        return (int) DB::table('inventory_transactions')
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    private function warehouseStockSum(int $itemId): int
    {
        return (int) DB::table('inventory_warehouse_stock')
            ->where('item_id', $itemId)
            ->sum('quantity_on_hand');
    }

    /**
     * @return array{item: InventoryItem, warehouse: Warehouse, referenceId: int, keepId: int, duplicateId: int}
     */
    private function createDuplicateSaleScenario(int $purchaseQty = 50, int $saleQty = 5): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-DUP-SALE-'.uniqid(),
            'name' => 'Duplicate sale test item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $date = now()->toDateString();
        $referenceId = 900001 + random_int(1, 99999);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => $purchaseQty,
            'unit_cost' => 1000,
            'total_cost' => $purchaseQty * 1000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => $date,
            'created_by' => $userId,
        ]);

        $keepId = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'sale',
            'quantity' => -$saleQty,
            'unit_cost' => 1000,
            'total_cost' => $saleQty * 1000,
            'reference_type' => 'delivery_order_line',
            'reference_id' => $referenceId,
            'transaction_date' => $date,
            'created_by' => $userId,
        ])->id;

        $duplicateId = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'sale',
            'quantity' => -$saleQty,
            'unit_cost' => 1000,
            'total_cost' => $saleQty * 1000,
            'reference_type' => 'delivery_order_line',
            'reference_id' => $referenceId,
            'transaction_date' => $date,
            'created_by' => $userId,
        ])->id;

        $warehouseQty = $purchaseQty - ($saleQty * 2);
        InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity_on_hand' => $warehouseQty,
            'reserved_quantity' => 0,
            'available_quantity' => $warehouseQty,
            'min_stock_level' => 0,
            'max_stock_level' => 0,
            'reorder_point' => 0,
        ]);

        return [
            'item' => $item,
            'warehouse' => $warehouse,
            'referenceId' => $referenceId,
            'keepId' => $keepId,
            'duplicateId' => $duplicateId,
        ];
    }

    public function test_removes_duplicate_sale_rows_and_keeps_smallest_id(): void
    {
        $scenario = $this->createDuplicateSaleScenario();
        $item = $scenario['item'];

        $exitCode = Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $saleRows = InventoryTransaction::query()
            ->where('reference_type', 'delivery_order_line')
            ->where('reference_id', $scenario['referenceId'])
            ->where('transaction_type', 'sale')
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $saleRows);
        $this->assertSame($scenario['keepId'], $saleRows->first()->id);
        $this->assertNull(InventoryTransaction::query()->find($scenario['duplicateId']));
    }

    public function test_warehouse_stock_restored_while_ledger_warehouse_gap_unchanged(): void
    {
        $scenario = $this->createDuplicateSaleScenario(purchaseQty: 50, saleQty: 5);
        $item = $scenario['item'];

        $gapBefore = $this->ledgerStockSum($item->id) - $this->warehouseStockSum($item->id);
        $warehouseBefore = $this->warehouseStockSum($item->id);

        Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $gapAfter = $this->ledgerStockSum($item->id) - $this->warehouseStockSum($item->id);

        $this->assertSame($gapBefore, $gapAfter);
        $this->assertSame($warehouseBefore + 5, $this->warehouseStockSum($item->id));
    }

    public function test_dry_run_does_not_change_database(): void
    {
        $scenario = $this->createDuplicateSaleScenario();
        $item = $scenario['item'];

        $txnBefore = DB::table('inventory_transactions')->count();
        $whBefore = DB::table('inventory_warehouse_stock')->count();

        $exitCode = Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame($txnBefore, DB::table('inventory_transactions')->count());
        $this->assertSame($whBefore, DB::table('inventory_warehouse_stock')->count());
        $this->assertStringContainsString('DRY-RUN', Artisan::output());
        $this->assertNotNull(InventoryTransaction::query()->find($scenario['duplicateId']));
    }

    public function test_execute_without_force_is_rejected(): void
    {
        $scenario = $this->createDuplicateSaleScenario();
        $item = $scenario['item'];

        $txnCountBefore = DB::table('inventory_transactions')->count();

        $exitCode = Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
            '--execute' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame($txnCountBefore, DB::table('inventory_transactions')->count());
        $this->assertStringContainsString('--force', Artisan::output());
    }

    public function test_execute_creates_both_backup_tables(): void
    {
        $scenario = $this->createDuplicateSaleScenario();
        $item = $scenario['item'];

        $txnLiveCount = (int) DB::table('inventory_transactions')->count();
        $whLiveCount = (int) DB::table('inventory_warehouse_stock')->count();

        Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/inventory_transactions_bak_\d{8}_\d{6}/', $output);
        $this->assertMatchesRegularExpression('/inventory_warehouse_stock_bak_\d{8}_\d{6}/', $output);

        preg_match('/inventory_transactions_bak_\d{8}_\d{6}/', $output, $txnMatches);
        preg_match('/inventory_warehouse_stock_bak_\d{8}_\d{6}/', $output, $whMatches);

        $this->assertNotEmpty($txnMatches);
        $this->assertNotEmpty($whMatches);

        $txnBackup = $txnMatches[0];
        $whBackup = $whMatches[0];

        $this->assertTrue(Schema::hasTable($txnBackup));
        $this->assertTrue(Schema::hasTable($whBackup));
        $this->assertSame($txnLiveCount, (int) DB::table($txnBackup)->count());
        $this->assertSame($whLiveCount, (int) DB::table($whBackup)->count());
    }

    public function test_non_duplicate_sale_transaction_is_untouched(): void
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-SINGLE-SALE-'.uniqid(),
            'name' => 'Single sale item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $referenceId = 800001;

        $saleId = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'sale',
            'quantity' => -3,
            'unit_cost' => 1000,
            'total_cost' => 3000,
            'reference_type' => 'delivery_order_line',
            'reference_id' => $referenceId,
            'transaction_date' => now()->toDateString(),
            'created_by' => $userId,
        ])->id;

        $exitCode = Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertNotNull(InventoryTransaction::query()->find($saleId));
        $this->assertStringContainsString('No duplicate sale transactions', Artisan::output());
    }

    public function test_duplicate_sale_with_null_warehouse_id_deletes_without_restoring_stock(): void
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-DUP-NULL-WH-'.uniqid(),
            'name' => 'Duplicate sale null warehouse item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        $date = now()->toDateString();
        $referenceId = 700001 + random_int(1, 99999);
        $saleQty = 4;

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => 40,
            'unit_cost' => 1000,
            'total_cost' => 40000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => $date,
            'created_by' => $userId,
        ]);

        $keepId = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => null,
            'transaction_type' => 'sale',
            'quantity' => -$saleQty,
            'unit_cost' => 1000,
            'total_cost' => $saleQty * 1000,
            'reference_type' => 'delivery_order_line',
            'reference_id' => $referenceId,
            'transaction_date' => $date,
            'created_by' => $userId,
        ])->id;

        $duplicateId = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => null,
            'transaction_type' => 'sale',
            'quantity' => -$saleQty,
            'unit_cost' => 1000,
            'total_cost' => $saleQty * 1000,
            'reference_type' => 'delivery_order_line',
            'reference_id' => $referenceId,
            'transaction_date' => $date,
            'created_by' => $userId,
        ])->id;

        $warehouseQty = 40;
        InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity_on_hand' => $warehouseQty,
            'reserved_quantity' => 0,
            'available_quantity' => $warehouseQty,
            'min_stock_level' => 0,
            'max_stock_level' => 0,
            'reorder_point' => 0,
        ]);

        $exitCode = Artisan::call('inventory:remove-duplicate-sale-transactions', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertNull(InventoryTransaction::query()->find($duplicateId));
        $this->assertNotNull(InventoryTransaction::query()->find($keepId));
        $this->assertSame($warehouseQty, $this->warehouseStockSum($item->id));

        $output = Artisan::output();
        $this->assertStringContainsString('no stock restore', $output);
        $this->assertStringContainsString('Total units restored to warehouse stock: 0', $output);
    }
}
