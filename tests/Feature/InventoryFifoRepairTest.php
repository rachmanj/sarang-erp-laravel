<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryWarehouseStock;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryFifoRepairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_repair_backfills_missing_fifo_layers_and_allows_strict_replay(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['inventory.view', 'inventory.adjust']);
        $this->actingAs($user);

        [$itemId, $warehouseId] = $this->createFifoItem();

        DB::table('inventory_transactions')->insert([
            [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'purchase',
                'quantity' => 9,
                'unit_cost' => 100,
                'total_cost' => 900,
                'reference_type' => 'purchase_invoice',
                'reference_id' => 1,
                'transaction_date' => '2026-03-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'sale',
                'quantity' => -20,
                'unit_cost' => 100,
                'total_cost' => -2000,
                'reference_type' => 'delivery_order_line',
                'reference_id' => 1,
                'transaction_date' => '2026-03-02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        InventoryWarehouseStock::query()->create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => -11,
            'reserved_quantity' => 0,
            'available_quantity' => -11,
        ]);

        $item = InventoryItem::query()->findOrFail($itemId);
        $this->assertNotNull(app(InventoryService::class)->getFifoReplayError($item));

        $this->post(route('inventory.fifo-repair.repair', $itemId))
            ->assertRedirect(route('inventory.fifo-repair.show', $itemId));

        $item->refresh();
        $this->assertNull(app(InventoryService::class)->getFifoReplayError($item));
        $this->assertSame(0, (int) $item->current_stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'item_id' => $itemId,
            'reference_type' => 'fifo_layer_repair',
            'quantity' => 11,
        ]);
    }

    public function test_index_lists_items_with_fifo_issues(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['inventory.view', 'inventory.adjust']);
        $this->actingAs($user);

        [$itemId, $warehouseId] = $this->createFifoItem();

        DB::table('inventory_transactions')->insert([
            [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'purchase',
                'quantity' => 1,
                'unit_cost' => 50,
                'total_cost' => 50,
                'transaction_date' => '2026-03-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'transaction_type' => 'sale',
                'quantity' => -5,
                'unit_cost' => 50,
                'total_cost' => -250,
                'transaction_date' => '2026-03-02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        InventoryWarehouseStock::query()->create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => -4,
            'reserved_quantity' => 0,
            'available_quantity' => -4,
        ]);

        $this->get(route('inventory.fifo-repair.index'))
            ->assertOk()
            ->assertSee('FIFO Layer Repair');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createFifoItem(): array
    {
        $warehouseId = (int) DB::table('warehouses')->orderBy('id')->value('id');
        $categoryId = (int) DB::table('product_categories')->where('is_active', true)->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'FIFO-REPAIR-'.uniqid(),
            'name' => 'FIFO Repair Test Item',
            'category_id' => $categoryId,
            'default_warehouse_id' => $warehouseId,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 100,
            'selling_price' => 120,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        return [(int) $item->id, $warehouseId];
    }
}
