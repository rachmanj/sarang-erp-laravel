<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FixPlasticWrapeFifoCorrectionCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * @return array{item: InventoryItem, outbound: InventoryTransaction, inbound: InventoryTransaction}
     */
    private function createBrokenFifoWarehouseTransferScenario(): array
    {
        $categoryId = (int) DB::table('product_categories')->value('id');
        $warehouseA = Warehouse::query()->firstOrFail();
        $warehouseB = Warehouse::query()->whereKeyNot($warehouseA->id)->firstOrFail();

        $item = InventoryItem::query()->create([
            'code' => 'T-FIFO-FIX-'.uniqid(),
            'name' => 'FIFO Fix Test Item',
            'category_id' => $categoryId,
            'unit_of_measure' => 'pcs',
            'purchase_price' => 77000,
            'selling_price' => 90000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseA->id,
            'transaction_type' => 'purchase',
            'quantity' => 1,
            'unit_cost' => 77000,
            'total_cost' => 77000,
            'transaction_date' => '2026-04-14',
            'notes' => 'Single FIFO layer',
            'created_by' => null,
        ]);

        InventoryWarehouseStock::query()->updateOrCreate(
            ['item_id' => $item->id, 'warehouse_id' => $warehouseA->id],
            [
                'quantity_on_hand' => 12,
                'reserved_quantity' => 0,
                'available_quantity' => 12,
                'min_stock_level' => 0,
                'max_stock_level' => 0,
                'reorder_point' => 0,
            ]
        );

        $outbound = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseA->id,
            'transaction_type' => 'transfer',
            'quantity' => -12,
            'unit_cost' => 0,
            'total_cost' => 0,
            'reference_type' => 'warehouse_transfer',
            'reference_id' => $warehouseB->id,
            'transaction_date' => '2026-04-15',
            'notes' => 'Transfer to warehouse '.$warehouseB->id,
            'created_by' => null,
        ]);

        $inbound = InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouseB->id,
            'transaction_type' => 'transfer',
            'quantity' => 12,
            'unit_cost' => 0,
            'total_cost' => 0,
            'reference_type' => 'warehouse_transfer',
            'reference_id' => $warehouseA->id,
            'transaction_date' => '2026-04-15',
            'notes' => 'Transfer from warehouse '.$warehouseA->id,
            'created_by' => null,
        ]);

        return compact('item', 'outbound', 'inbound');
    }

    public function test_dry_run_previews_correction_without_saving(): void
    {
        ['item' => $item, 'outbound' => $outbound, 'inbound' => $inbound] = $this->createBrokenFifoWarehouseTransferScenario();

        $this->artisan('inventory:fix-plastic-wrape-fifo', [
            '--item' => $item->code,
            '--out-id' => $outbound->id,
            '--in-id' => $inbound->id,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertExitCode(0);

        $outbound->refresh();
        $inbound->refresh();

        $this->assertEquals(-12, (float) $outbound->quantity);
        $this->assertEquals(12, (float) $inbound->quantity);

        $this->expectException(\Exception::class);
        app(InventoryService::class)->calculateUnitCost($item->fresh());
    }

    public function test_apply_corrects_transactions_and_restores_fifo_replay(): void
    {
        ['item' => $item, 'outbound' => $outbound, 'inbound' => $inbound] = $this->createBrokenFifoWarehouseTransferScenario();

        $this->artisan('inventory:fix-plastic-wrape-fifo', [
            '--item' => $item->code,
            '--out-id' => $outbound->id,
            '--in-id' => $inbound->id,
        ])
            ->expectsConfirmation('Apply these transaction corrections?', 'yes')
            ->assertExitCode(0);

        $outbound->refresh();
        $inbound->refresh();

        $this->assertEquals(-1, (float) $outbound->quantity);
        $this->assertEquals(1, (float) $inbound->quantity);
        $this->assertEquals(77000.0, (float) $outbound->unit_cost);
        $this->assertEquals(77000.0, (float) $inbound->unit_cost);

        $unitCost = app(InventoryService::class)->calculateUnitCost($item->fresh());
        $this->assertEqualsWithDelta(77000.0, $unitCost, 0.01);
    }

    public function test_command_reports_success_when_fifo_already_valid(): void
    {
        ['item' => $item, 'outbound' => $outbound, 'inbound' => $inbound] = $this->createBrokenFifoWarehouseTransferScenario();

        $outbound->update([
            'quantity' => -1,
            'unit_cost' => 77000,
            'total_cost' => -77000,
        ]);
        $inbound->update([
            'quantity' => 1,
            'unit_cost' => 77000,
            'total_cost' => 77000,
        ]);

        $this->artisan('inventory:fix-plastic-wrape-fifo', [
            '--item' => $item->code,
            '--out-id' => $outbound->id,
            '--in-id' => $inbound->id,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('FIFO replay already succeeds')
            ->assertExitCode(0);
    }
}
