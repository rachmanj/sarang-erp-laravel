<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryFifoCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function createTestItem(string $valuationMethod = 'fifo'): InventoryItem
    {
        $categoryId = (int) DB::table('product_categories')->value('id');

        return InventoryItem::create([
            'code' => 'TEST-FIFO-'.uniqid(),
            'name' => 'FIFO Test Item',
            'category_id' => $categoryId ?: null,
            'unit_of_measure' => 'PCS',
            'purchase_price' => 100,
            'selling_price' => 150,
            'valuation_method' => $valuationMethod,
            'item_type' => 'item',
            'is_active' => true,
        ]);
    }

    public function test_fifo_uses_oldest_remaining_layers_after_sales(): void
    {
        $item = $this->createTestItem('fifo');

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 100,
            'total_cost' => 1000,
            'transaction_date' => '2026-01-01',
            'notes' => 'Layer 1',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 200,
            'total_cost' => 2000,
            'transaction_date' => '2026-01-15',
            'notes' => 'Layer 2',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'sale',
            'quantity' => -8,
            'unit_cost' => 100,
            'total_cost' => -800,
            'transaction_date' => '2026-02-01',
            'notes' => 'Sale',
            'created_by' => null,
        ]);

        $service = app(InventoryService::class);
        $unitCost = $service->calculateUnitCost($item->fresh());

        $this->assertEqualsWithDelta(183.3333, $unitCost, 0.01);
    }

    public function test_weighted_average_differs_from_fifo_after_partial_sale(): void
    {
        $item = $this->createTestItem('fifo');

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 5,
            'unit_cost' => 100,
            'total_cost' => 500,
            'transaction_date' => '2026-01-01',
            'notes' => 'Cheap batch',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 5,
            'unit_cost' => 300,
            'total_cost' => 1500,
            'transaction_date' => '2026-01-10',
            'notes' => 'Expensive batch',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'sale',
            'quantity' => -5,
            'unit_cost' => 100,
            'total_cost' => -500,
            'transaction_date' => '2026-01-20',
            'notes' => 'Partial sale',
            'created_by' => null,
        ]);

        $service = app(InventoryService::class);

        $fifoCost = $service->calculateUnitCost($item->fresh());

        $item->update(['valuation_method' => 'weighted_average']);
        $avgCost = $service->calculateUnitCost($item->fresh());

        $this->assertEqualsWithDelta(200.0, $avgCost, 0.01);
        $this->assertEqualsWithDelta(300.0, $fifoCost, 0.01);
    }
}
