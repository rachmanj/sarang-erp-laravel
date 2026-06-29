<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryItemTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createFifoItem(string $code): InventoryItem
    {
        $categoryId = (int) DB::table('product_categories')->value('id');

        return InventoryItem::query()->create([
            'code' => $code,
            'name' => 'Transfer Item '.$code,
            'category_id' => $categoryId,
            'unit_of_measure' => 'pcs',
            'purchase_price' => 31000,
            'selling_price' => 35000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);
    }

    public function test_item_transfer_succeeds_when_destination_has_broken_fifo_history(): void
    {
        $user = User::query()->where('username', 'superadmin')->firstOrFail();
        $sourceItem = $this->createFifoItem('TR-SRC-'.uniqid());
        $destinationItem = $this->createFifoItem('TR-DST-'.uniqid());

        InventoryTransaction::query()->create([
            'item_id' => $sourceItem->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 31000,
            'total_cost' => 310000,
            'transaction_date' => '2026-04-01',
            'notes' => 'Source stock',
            'created_by' => $user->id,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $destinationItem->id,
            'transaction_type' => 'sale',
            'quantity' => -24,
            'unit_cost' => 31000,
            'total_cost' => -744000,
            'transaction_date' => '2026-03-05',
            'notes' => 'Sale before purchase breaks strict FIFO replay',
            'created_by' => $user->id,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $destinationItem->id,
            'transaction_type' => 'purchase',
            'quantity' => 24,
            'unit_cost' => 31000,
            'total_cost' => 744000,
            'transaction_date' => '2026-03-11',
            'notes' => 'Purchase after earlier sale',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson(route('inventory.transfer-stock', $sourceItem->id), [
            'from_item_id' => $sourceItem->id,
            'to_item_id' => $destinationItem->id,
            'quantity' => 5,
            'unit_cost' => 45,
            'notes' => 'Merge duplicate SKU stock',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Stock transfer completed successfully',
            ]);

        $this->assertSame(5, $sourceItem->fresh()->current_stock);
        $this->assertSame(5, $destinationItem->fresh()->current_stock);

        $this->assertDatabaseHas('inventory_transactions', [
            'item_id' => $sourceItem->id,
            'transaction_type' => 'transfer',
            'quantity' => -5,
            'reference_type' => 'stock_transfer',
            'reference_id' => $destinationItem->id,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'item_id' => $destinationItem->id,
            'transaction_type' => 'transfer',
            'quantity' => 5,
            'reference_type' => 'stock_transfer',
            'reference_id' => $sourceItem->id,
        ]);
    }
}
