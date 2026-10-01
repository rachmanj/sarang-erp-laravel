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

class RepairWarehouseStockDriftCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function warehouseStockSum(int $itemId): int
    {
        return (int) DB::table('inventory_warehouse_stock')
            ->where('item_id', $itemId)
            ->sum('quantity_on_hand');
    }

    private function ledgerStockSum(int $itemId): int
    {
        return (int) DB::table('inventory_transactions')
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    /**
     * @return array{item: InventoryItem, warehouse: Warehouse}
     */
    private function createStockItem(int $ledgerQty, int $wrongWarehouseQty): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-DRIFT-'.uniqid(),
            'name' => 'Warehouse drift test item',
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

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => $ledgerQty,
            'unit_cost' => 1000,
            'total_cost' => $ledgerQty * 1000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Test purchase',
            'created_by' => (int) DB::table('users')->orderBy('id')->value('id'),
        ]);

        InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'quantity_on_hand' => $wrongWarehouseQty,
            'reserved_quantity' => 0,
            'available_quantity' => $wrongWarehouseQty,
            'min_stock_level' => 0,
            'max_stock_level' => 0,
            'reorder_point' => 0,
        ]);

        return ['item' => $item, 'warehouse' => $warehouse];
    }

    public function test_repairs_drifted_item_so_warehouse_matches_ledger(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 50, wrongWarehouseQty: 80);

        $this->assertNotEquals($this->ledgerStockSum($item->id), $this->warehouseStockSum($item->id));

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertEquals($this->ledgerStockSum($item->id), $this->warehouseStockSum($item->id));
        $this->assertSame(50, $this->warehouseStockSum($item->id));
    }

    public function test_dry_run_does_not_change_database(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 30, wrongWarehouseQty: 45);

        $before = DB::table('inventory_warehouse_stock')->get()->map(fn ($row) => (array) $row)->all();

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
        ]);

        $this->assertSame(0, $exitCode);
        $after = DB::table('inventory_warehouse_stock')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertSame($before, $after);
        $this->assertStringContainsString('DRY-RUN', Artisan::output());
    }

    public function test_execute_without_force_is_rejected_and_leaves_data_unchanged(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 20, wrongWarehouseQty: 35);

        $beforeQty = $this->warehouseStockSum($item->id);

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--execute' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertSame($beforeQty, $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('--force', Artisan::output());
    }

    public function test_consistent_item_is_unchanged_after_execute(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createStockItem(ledgerQty: 40, wrongWarehouseQty: 40);

        $rowBefore = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail()
            ->toArray();

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $rowAfter = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail()
            ->toArray();

        $this->assertSame($rowBefore['quantity_on_hand'], $rowAfter['quantity_on_hand']);
        $this->assertSame($rowBefore['available_quantity'], $rowAfter['available_quantity']);
    }

    public function test_execute_creates_backup_table(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 10, wrongWarehouseQty: 25);

        Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $output = Artisan::output();
        $this->assertMatchesRegularExpression('/inventory_warehouse_stock_bak_\d{8}_\d{6}/', $output);

        preg_match('/inventory_warehouse_stock_bak_\d{8}_\d{6}/', $output, $matches);
        $this->assertNotEmpty($matches);
        $backupTable = $matches[0];

        $this->assertTrue(Schema::hasTable($backupTable));

        $liveCount = (int) DB::table('inventory_warehouse_stock')->count();
        $backupCount = (int) DB::table($backupTable)->count();

        $this->assertGreaterThan(0, $backupCount);
        $this->assertSame($liveCount, $backupCount);
    }

    /**
     * @return array{item: InventoryItem, warehouse: Warehouse}
     */
    private function createZeroLedgerItemWithWarehouseStock(int $warehouseQty): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-ZERO-LED-'.uniqid(),
            'name' => 'Zero ledger warehouse drift item',
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

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => $warehouseQty,
            'unit_cost' => 1000,
            'total_cost' => $warehouseQty * 1000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => $date,
            'notes' => 'Test purchase',
            'created_by' => $userId,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'sale',
            'quantity' => -$warehouseQty,
            'unit_cost' => 1000,
            'total_cost' => $warehouseQty * 1000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => $date,
            'notes' => 'Test sale',
            'created_by' => $userId,
        ]);

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

        $this->assertSame(0, $this->ledgerStockSum($item->id));
        $this->assertSame($warehouseQty, $this->warehouseStockSum($item->id));

        return ['item' => $item, 'warehouse' => $warehouse];
    }

    public function test_zero_empty_ledger_mode_zeros_warehouse_stock_when_ledger_total_is_zero(): void
    {
        ['item' => $item] = $this->createZeroLedgerItemWithWarehouseStock(warehouseQty: 120);

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'zero-empty-ledger',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, $this->ledgerStockSum($item->id));
        $this->assertSame(0, $this->warehouseStockSum($item->id));
        $this->assertEquals($this->ledgerStockSum($item->id), $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('Items processed: 1', Artisan::output());
    }

    public function test_zero_empty_ledger_mode_skips_item_when_ledger_total_is_not_zero(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 50, wrongWarehouseQty: 80);

        $beforeQty = $this->warehouseStockSum($item->id);

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'zero-empty-ledger',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame($beforeQty, $this->warehouseStockSum($item->id));
        $output = Artisan::output();
        $this->assertStringContainsString('Items skipped (ledger total not zero): 1', $output);
        $this->assertStringContainsString('Items processed: 0', $output);
    }

    public function test_zero_empty_ledger_mode_does_not_create_new_warehouse_stock_rows(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createZeroLedgerItemWithWarehouseStock(warehouseQty: 75);

        $secondWarehouse = Warehouse::query()->where('id', '!=', $warehouse->id)->first();

        if ($secondWarehouse !== null) {
            InventoryTransaction::query()->create([
                'item_id' => $item->id,
                'warehouse_id' => $secondWarehouse->id,
                'transaction_type' => 'purchase',
                'quantity' => 10,
                'unit_cost' => 1000,
                'total_cost' => 10000,
                'reference_type' => null,
                'reference_id' => null,
                'transaction_date' => now()->toDateString(),
                'notes' => 'Extra purchase on other wh',
                'created_by' => (int) DB::table('users')->orderBy('id')->value('id'),
            ]);

            InventoryTransaction::query()->create([
                'item_id' => $item->id,
                'warehouse_id' => $secondWarehouse->id,
                'transaction_type' => 'sale',
                'quantity' => -10,
                'unit_cost' => 1000,
                'total_cost' => 10000,
                'reference_type' => null,
                'reference_id' => null,
                'transaction_date' => now()->toDateString(),
                'notes' => 'Offset sale on other wh',
                'created_by' => (int) DB::table('users')->orderBy('id')->value('id'),
            ]);
        }

        $rowCountBefore = InventoryWarehouseStock::query()->where('item_id', $item->id)->count();

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'zero-empty-ledger',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $rowCountAfter = InventoryWarehouseStock::query()->where('item_id', $item->id)->count();
        $this->assertSame($rowCountBefore, $rowCountAfter);
        $this->assertStringNotContainsString('create wh', Artisan::output());
    }

    public function test_zero_empty_ledger_dry_run_does_not_change_database(): void
    {
        ['item' => $item] = $this->createZeroLedgerItemWithWarehouseStock(warehouseQty: 60);

        $before = DB::table('inventory_warehouse_stock')->get()->map(fn ($row) => (array) $row)->all();

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'zero-empty-ledger',
        ]);

        $this->assertSame(0, $exitCode);
        $after = DB::table('inventory_warehouse_stock')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertSame($before, $after);
        $output = Artisan::output();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('mode=zero-empty-ledger', $output);
    }

    public function test_default_per_warehouse_mode_still_repairs_warehouse_to_match_ledger(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 55, wrongWarehouseQty: 90);

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertEquals($this->ledgerStockSum($item->id), $this->warehouseStockSum($item->id));
        $this->assertSame(55, $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('mode=per-warehouse', Artisan::output());
    }

    /**
     * @return array{item: InventoryItem, warehouse: Warehouse}
     */
    private function createUnderStockItem(int $ledgerQty, int $wrongWarehouseQty): array
    {
        return $this->createStockItem(ledgerQty: $ledgerQty, wrongWarehouseQty: $wrongWarehouseQty);
    }

    public function test_fill_dominant_warehouse_adjusts_single_warehouse_row_to_match_ledger(): void
    {
        ['item' => $item] = $this->createUnderStockItem(ledgerQty: 80, wrongWarehouseQty: 50);

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'fill-dominant-warehouse',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(80, $this->warehouseStockSum($item->id));
        $this->assertEquals($this->ledgerStockSum($item->id), $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('Total units added: 30', Artisan::output());
    }

    public function test_fill_dominant_warehouse_only_updates_dominant_warehouse_row(): void
    {
        $warehouseA = Warehouse::query()->firstOrFail();
        $warehouseB = Warehouse::query()->where('id', '!=', $warehouseA->id)->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-FILL-DOM-'.uniqid(),
            'name' => 'Fill dominant multi-wh item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouseA->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseA->id,
            'transaction_type' => 'purchase',
            'quantity' => 100,
            'unit_cost' => 1000,
            'total_cost' => 100000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Test purchase',
            'created_by' => $userId,
        ]);

        $stockA = InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseA->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'available_quantity' => 10,
            'min_stock_level' => 0,
            'max_stock_level' => 0,
            'reorder_point' => 0,
        ]);

        $stockB = InventoryWarehouseStock::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseB->id,
            'quantity_on_hand' => 40,
            'reserved_quantity' => 0,
            'available_quantity' => 40,
            'min_stock_level' => 0,
            'max_stock_level' => 0,
            'reorder_point' => 0,
        ]);

        $stockABefore = $stockA->quantity_on_hand;
        $stockBBefore = $stockB->quantity_on_hand;

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'fill-dominant-warehouse',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $stockA->refresh();
        $stockB->refresh();

        $this->assertSame($stockABefore, $stockA->quantity_on_hand);
        $this->assertSame(90, $stockB->quantity_on_hand);
        $this->assertNotSame($stockBBefore, $stockB->quantity_on_hand);
        $this->assertSame(100, $this->warehouseStockSum($item->id));
    }

    public function test_fill_dominant_warehouse_skips_item_when_gap_exceeds_delta_max(): void
    {
        ['item' => $item] = $this->createUnderStockItem(ledgerQty: 200, wrongWarehouseQty: 50);

        $beforeQty = $this->warehouseStockSum($item->id);

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'fill-dominant-warehouse',
            '--delta-max' => 100,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame($beforeQty, $this->warehouseStockSum($item->id));
        $output = Artisan::output();
        $this->assertStringContainsString('Items skipped (gap exceeds delta-max): 1', $output);
        $this->assertStringContainsString($item->code, $output);
    }

    public function test_fill_dominant_warehouse_creates_row_when_item_has_no_warehouse_stock(): void
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-FILL-NO-WH-'.uniqid(),
            'name' => 'No warehouse row item',
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

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => 42,
            'unit_cost' => 1000,
            'total_cost' => 42000,
            'reference_type' => null,
            'reference_id' => null,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Test purchase',
            'created_by' => $userId,
        ]);

        $this->assertSame(0, $this->warehouseStockSum($item->id));

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'fill-dominant-warehouse',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, InventoryWarehouseStock::query()->where('item_id', $item->id)->count());
        $this->assertSame(42, $this->warehouseStockSum($item->id));
        $this->assertSame($warehouse->id, (int) InventoryWarehouseStock::query()->where('item_id', $item->id)->value('warehouse_id'));
    }

    public function test_fill_dominant_warehouse_dry_run_does_not_change_database(): void
    {
        ['item' => $item] = $this->createUnderStockItem(ledgerQty: 60, wrongWarehouseQty: 40);

        $before = DB::table('inventory_warehouse_stock')->get()->map(fn ($row) => (array) $row)->all();

        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $item->code,
            '--mode' => 'fill-dominant-warehouse',
        ]);

        $this->assertSame(0, $exitCode);
        $after = DB::table('inventory_warehouse_stock')->get()->map(fn ($row) => (array) $row)->all();
        $this->assertSame($before, $after);
        $this->assertStringContainsString('DRY-RUN', Artisan::output());
        $this->assertStringContainsString('mode=fill-dominant-warehouse', Artisan::output());
    }

    public function test_fill_dominant_warehouse_rejects_unknown_mode(): void
    {
        $exitCode = Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--mode' => 'invalid-mode',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('fill-dominant-warehouse', Artisan::output());
    }

    public function test_fill_dominant_warehouse_does_not_change_per_warehouse_and_zero_empty_ledger_modes(): void
    {
        ['item' => $perWhItem] = $this->createStockItem(ledgerQty: 44, wrongWarehouseQty: 70);

        Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $perWhItem->code,
            '--mode' => 'per-warehouse',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(44, $this->warehouseStockSum($perWhItem->id));

        ['item' => $zeroItem] = $this->createZeroLedgerItemWithWarehouseStock(warehouseQty: 33);

        Artisan::call('inventory:repair-warehouse-stock-drift', [
            '--item' => $zeroItem->code,
            '--mode' => 'zero-empty-ledger',
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $this->warehouseStockSum($zeroItem->id));
    }
}
