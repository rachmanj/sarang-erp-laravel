<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouseStock;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryFifoTransferValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createFifoItem(): InventoryItem
    {
        $categoryId = (int) DB::table('product_categories')->value('id');

        return InventoryItem::query()->create([
            'code' => 'T-FIFO-'.uniqid(),
            'name' => 'FIFO Transfer Item',
            'category_id' => $categoryId,
            'unit_of_measure' => 'pcs',
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);
    }

    public function test_warehouse_transfer_rejects_over_consumption_of_fifo_layers(): void
    {
        $item = $this->createFifoItem();
        $fromWarehouse = Warehouse::query()->firstOrFail();
        $toWarehouse = Warehouse::query()->whereKeyNot($fromWarehouse->id)->firstOrFail();

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $fromWarehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => 1,
            'unit_cost' => 77000,
            'total_cost' => 77000,
            'transaction_date' => '2026-04-15',
            'notes' => 'Only one FIFO layer',
            'created_by' => null,
        ]);

        InventoryWarehouseStock::query()->updateOrCreate(
            ['item_id' => $item->id, 'warehouse_id' => $fromWarehouse->id],
            [
                'quantity_on_hand' => 12,
                'reserved_quantity' => 0,
                'available_quantity' => 12,
                'min_stock_level' => 0,
                'max_stock_level' => 0,
                'reorder_point' => 0,
            ]
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient FIFO inventory layers to consume 12 units. Available: 1.');

        app(WarehouseService::class)->transferStock(
            $item->id,
            $fromWarehouse->id,
            $toWarehouse->id,
            12
        );
    }
}
