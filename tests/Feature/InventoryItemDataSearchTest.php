<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryItemDataSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = User::factory()->create([
            'username' => 'searchuser',
            'password' => bcrypt('password'),
        ]);
        $this->user->givePermissionTo('inventory.view');
    }

    private function createCategory(string $name = 'Category Alpha'): ProductCategory
    {
        return ProductCategory::query()->create([
            'code' => 'S-CAT-'.uniqid(),
            'name' => $name,
            'description' => 'Category for search tests',
            'is_active' => true,
        ]);
    }

    private function createInventoryItem(ProductCategory $category, array $attributes = []): InventoryItem
    {
        return InventoryItem::query()->create(array_merge([
            'code' => 'S-ITEM-'.uniqid(),
            'name' => 'Sumato Widget',
            'category_id' => $category->id,
            'unit_of_measure' => 'pcs',
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ], $attributes));
    }

    public function test_inventory_data_search_without_order_returns_ok(): void
    {
        $category = $this->createCategory();
        $this->createInventoryItem($category);

        $response = $this->actingAs($this->user)->getJson(route('inventory.data', [
            'q' => 'Sumato',
            'length' => 100,
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_inventory_data_search_with_category_column_order_returns_ok(): void
    {
        $category = $this->createCategory('Zebra Category');
        $this->createInventoryItem($category);

        $response = $this->actingAs($this->user)->getJson(route('inventory.data', [
            'q' => 'Sumato',
            'length' => 100,
            'order' => [
                0 => [
                    'column' => 2,
                    'dir' => 'asc',
                ],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_inventory_data_search_with_name_column_order_returns_ok(): void
    {
        $category = $this->createCategory();
        $this->createInventoryItem($category);

        $response = $this->actingAs($this->user)->getJson(route('inventory.data', [
            'q' => 'Sumato',
            'length' => 100,
            'order' => [
                0 => [
                    'column' => 1,
                    'dir' => 'asc',
                ],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }
}
