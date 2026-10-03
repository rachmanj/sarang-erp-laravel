<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryMovingAverageCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function createWeightedAverageItem(float $purchasePrice = 100): InventoryItem
    {
        $categoryId = (int) DB::table('product_categories')->value('id');

        return InventoryItem::create([
            'code' => 'TEST-WAVG-'.uniqid(),
            'name' => 'Weighted Average Test Item',
            'category_id' => $categoryId ?: null,
            'unit_of_measure' => 'PCS',
            'purchase_price' => $purchasePrice,
            'selling_price' => 150,
            'valuation_method' => 'weighted_average',
            'item_type' => 'item',
            'is_active' => true,
        ]);
    }

    public function test_kondensor_scenario_matches_production_moving_average(): void
    {
        $item = $this->createWeightedAverageItem();

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'adjustment',
            'quantity' => 1000,
            'unit_cost' => 0,
            'total_cost' => 0,
            'transaction_date' => '2025-07-22',
            'notes' => 'Opening adjustment',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 1,
            'unit_cost' => 1200000,
            'total_cost' => 1200000,
            'transaction_date' => '2025-07-22',
            'notes' => 'Single purchase',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'sale',
            'quantity' => -1,
            'unit_cost' => 1200000,
            'total_cost' => -1200000,
            'transaction_date' => '2025-07-23',
            'notes' => 'Sale',
            'created_by' => null,
        ]);

        $service = app(InventoryService::class);
        $unitCost = $service->calculateUnitCost($item->fresh());

        $this->assertEqualsWithDelta(1198.80, $unitCost, 0.01);
        $this->assertEqualsWithDelta(1198801.20, $unitCost * 1000, 0.10);
    }

    public function test_purchase_only_matches_legacy_weighted_average_formula(): void
    {
        $item = $this->createWeightedAverageItem();

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 100,
            'total_cost' => 1000,
            'transaction_date' => '2026-01-01',
            'created_by' => null,
        ]);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'purchase',
            'quantity' => 5,
            'unit_cost' => 400,
            'total_cost' => 2000,
            'transaction_date' => '2026-01-15',
            'created_by' => null,
        ]);

        $legacyAverage = (1000 + 2000) / (10 + 5);

        $service = app(InventoryService::class);
        $unitCost = $service->calculateUnitCost($item->fresh());

        $this->assertEqualsWithDelta($legacyAverage, $unitCost, 0.0001);
        $this->assertEqualsWithDelta(200.0, $unitCost, 0.0001);
    }

    public function test_zero_cost_adjustment_without_purchase_yields_zero_unit_cost(): void
    {
        $item = $this->createWeightedAverageItem(0);

        InventoryTransaction::create([
            'item_id' => $item->id,
            'transaction_type' => 'adjustment',
            'quantity' => 50,
            'unit_cost' => 0,
            'total_cost' => 0,
            'transaction_date' => '2026-02-01',
            'created_by' => null,
        ]);

        $service = app(InventoryService::class);

        $this->assertSame(0.0, (float) $service->calculateUnitCost($item->fresh()));
        $this->assertSame(0.0, (float) $service->calculateUnitCostForRepair($item->fresh()));
    }

    public function test_item_without_transactions_uses_purchase_price(): void
    {
        $item = $this->createWeightedAverageItem(87500.50);

        $service = app(InventoryService::class);

        $this->assertSame(87500.50, (float) $service->calculateUnitCost($item));
        $this->assertSame(87500.50, (float) $service->calculateUnitCostForRepair($item));
    }
}
