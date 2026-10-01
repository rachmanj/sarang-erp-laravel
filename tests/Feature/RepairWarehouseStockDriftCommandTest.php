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
}
