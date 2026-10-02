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

class WriteOffPhysicalCountCommandTest extends TestCase
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
    private function createStockItem(int $ledgerQty, int $warehouseQty): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-WO-'.uniqid(),
            'name' => 'Write-off test item',
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
            'quantity_on_hand' => $warehouseQty,
            'reserved_quantity' => 0,
            'available_quantity' => $warehouseQty,
            'min_stock_level' => 0,
            'max_stock_level' => 0,
            'reorder_point' => 0,
        ]);

        return ['item' => $item, 'warehouse' => $warehouse];
    }

    private function writeCsv(array $rows, ?string $path = null): string
    {
        $path ??= sys_get_temp_dir().'/wo_test_'.uniqid().'.csv';

        $lines = ['code,physical_qty'];
        foreach ($rows as $code => $qty) {
            $lines[] = $code.','.$qty;
        }

        file_put_contents($path, implode("\n", $lines)."\n");

        return $path;
    }

    public function test_write_off_reduces_ledger_and_warehouse_and_reports_value(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 10, warehouseQty: 25);

        $csv = $this->writeCsv([$item->code => 3]);

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv, '--execute' => true, '--force' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(3, $this->ledgerStockSum($item->id), 'buku besar harus turun ke angka fisik');
        $this->assertSame(3, $this->warehouseStockSum($item->id), 'total stok gudang harus sama dengan angka fisik');
        $this->assertStringContainsString('TOTAL NILAI WRITE-OFF', $output);
        $this->assertStringContainsString('7.000,00', $output, '7 unit x harga satuan 1000 harus dilaporkan sebagai nilai');

        $valuation = DB::table('inventory_valuations')
            ->where('item_id', $item->id)
            ->orderByDesc('valuation_date')
            ->first();

        $this->assertNotNull($valuation, 'valuasi harus dihitung ulang');
        $this->assertEquals(3, (int) $valuation->quantity_on_hand);
    }

    public function test_item_already_equal_to_physical_is_skipped(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 5, warehouseQty: 5);

        $csv = $this->writeCsv([$item->code => 5]);

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv, '--execute' => true, '--force' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(5, $this->ledgerStockSum($item->id));
        $this->assertSame(5, $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('Item dilewati (buku besar sudah sama dengan fisik): 1', $output);
    }

    public function test_physical_greater_than_ledger_is_rejected_without_changes(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 5, warehouseQty: 5);

        $csv = $this->writeCsv([$item->code => 8]);

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv, '--execute' => true, '--force' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(5, $this->ledgerStockSum($item->id), 'buku besar tidak boleh berubah');
        $this->assertSame(5, $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('Item ditolak (fisik lebih besar dari buku besar): 1', $output);
    }

    public function test_unknown_code_is_reported_but_other_rows_are_processed(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 6, warehouseQty: 6);

        $csv = $this->writeCsv(['TIDAK-ADA-9999' => 0, $item->code => 2]);

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv, '--execute' => true, '--force' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(2, $this->ledgerStockSum($item->id), 'baris lain tetap harus diproses');
        $this->assertStringContainsString('TIDAK-ADA-9999', $output);
        $this->assertStringContainsString('tidak ditemukan', $output);
    }

    public function test_dry_run_does_not_change_database(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 9, warehouseQty: 9);

        $csv = $this->writeCsv([$item->code => 1]);

        $before = DB::table('inventory_transactions')->count();

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(9, $this->ledgerStockSum($item->id));
        $this->assertSame(9, $this->warehouseStockSum($item->id));
        $this->assertSame($before, DB::table('inventory_transactions')->count());
        $this->assertStringContainsString('DRY-RUN', $output);
    }

    public function test_execute_without_force_is_rejected_and_changes_nothing(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 9, warehouseQty: 9);

        $csv = $this->writeCsv([$item->code => 1]);

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv, '--execute' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertSame(9, $this->ledgerStockSum($item->id));
        $this->assertSame(9, $this->warehouseStockSum($item->id));
        $this->assertStringContainsString('Menolak menulis', $output);
    }

    public function test_execute_creates_backup_tables(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 4, warehouseQty: 4);

        $csv = $this->writeCsv([$item->code => 1]);

        $exitCode = Artisan::call('inventory:write-off-physical-count', ['--file' => $csv, '--execute' => true, '--force' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Backup inventory_transactions: inventory_transactions_bak_', $output);
        $this->assertStringContainsString('Backup inventory_warehouse_stock: inventory_warehouse_stock_bak_', $output);
        $this->assertStringContainsString('Backup inventory_valuations: inventory_valuations_bak_', $output);

        $tables = DB::select('SHOW TABLES');
        $names = array_map(static fn ($row): string => (string) array_values((array) $row)[0], $tables);
        $backups = array_values(array_filter($names, static fn (string $name): bool => str_contains($name, '_bak_')));

        $this->assertCount(3, $backups, 'harus ada tiga tabel backup');
    }

    public function test_default_file_path_is_used_when_file_option_is_absent(): void
    {
        ['item' => $item] = $this->createStockItem(ledgerQty: 8, warehouseQty: 8);

        $default = storage_path('app/writeoff.csv');
        $this->writeCsv([$item->code => 3], $default);

        try {
            $exitCode = Artisan::call('inventory:write-off-physical-count', ['--execute' => true, '--force' => true]);
            $output = Artisan::output();

            $this->assertSame(0, $exitCode);
            $this->assertSame(3, $this->ledgerStockSum($item->id), 'jalur tanpa --file harus memakai berkas default');
            $this->assertSame(3, $this->warehouseStockSum($item->id));
            $this->assertStringContainsString('TOTAL NILAI WRITE-OFF', $output);
            $this->assertStringNotContainsString('tidak bisa dibaca', $output);
        } finally {
            if (Schema::hasTable('inventory_transactions') && file_exists($default)) {
                unlink($default);
            }
        }
    }

    public function test_missing_default_file_is_reported_clearly(): void
    {
        $default = storage_path('app/writeoff.csv');

        if (file_exists($default)) {
            unlink($default);
        }

        $exitCode = Artisan::call('inventory:write-off-physical-count');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Berkas tidak bisa dibaca', $output);
    }
}
