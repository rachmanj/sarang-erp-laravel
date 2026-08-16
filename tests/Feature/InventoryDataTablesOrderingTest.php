<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDataTablesOrderingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);
        $this->user->givePermissionTo('inventory.view');
    }

    private function createCategory(): ProductCategory
    {
        return ProductCategory::query()->create([
            'code' => 'T-CAT-'.uniqid(),
            'name' => 'Test Category',
            'description' => 'Category for DataTables ordering tests',
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createInventoryItem(ProductCategory $category, array $attributes = []): InventoryItem
    {
        return InventoryItem::query()->create(array_merge([
            'code' => 'T-ITEM-'.uniqid(),
            'name' => 'Test Item',
            'category_id' => $category->id,
            'unit_of_measure' => 'pcs',
            'purchase_price' => 1000,
            'selling_price' => 1500,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ], $attributes));
    }

    public function test_inventory_datatables_orders_by_code(): void
    {
        $category = $this->createCategory();

        $this->createInventoryItem($category, ['code' => 'ITEM001', 'name' => 'Item A']);
        $this->createInventoryItem($category, ['code' => 'ITEM002', 'name' => 'Item B']);
        $this->createInventoryItem($category, ['code' => 'ITEM003', 'name' => 'Item C']);

        $response = $this->actingAs($this->user)
            ->getJson('/inventory/data?length=100&order[0][column]=0&order[0][dir]=asc');

        $response->assertStatus(200);
        $data = collect($response->json('data'))->whereIn('code', ['ITEM001', 'ITEM002', 'ITEM003'])->values();

        $this->assertCount(3, $data);
        $this->assertEquals('ITEM001', $data[0]['code']);
        $this->assertEquals('ITEM002', $data[1]['code']);
        $this->assertEquals('ITEM003', $data[2]['code']);
    }

    public function test_inventory_datatables_orders_by_name_desc(): void
    {
        $category = $this->createCategory();

        $this->createInventoryItem($category, ['name' => 'Alpha']);
        $this->createInventoryItem($category, ['name' => 'Beta']);
        $this->createInventoryItem($category, ['name' => 'Gamma']);

        $response = $this->actingAs($this->user)
            ->getJson('/inventory/data?length=100&order[0][column]=1&order[0][dir]=desc');

        $response->assertStatus(200);
        $data = collect($response->json('data'))->whereIn('name', ['Alpha', 'Beta', 'Gamma'])->values();

        $this->assertCount(3, $data);
        $this->assertEquals('Gamma', $data[0]['name']);
        $this->assertEquals('Beta', $data[1]['name']);
        $this->assertEquals('Alpha', $data[2]['name']);
    }

    public function test_inventory_datatables_orders_by_purchase_price(): void
    {
        $category = $this->createCategory();

        $this->createInventoryItem($category, ['purchase_price' => 1000]);
        $this->createInventoryItem($category, ['purchase_price' => 2000]);
        $this->createInventoryItem($category, ['purchase_price' => 500]);

        $response = $this->actingAs($this->user)
            ->getJson('/inventory/data?length=100&order[0][column]=4&order[0][dir]=asc');

        $response->assertStatus(200);
        $codes = InventoryItem::query()
            ->where('category_id', $category->id)
            ->orderBy('purchase_price')
            ->pluck('code')
            ->all();

        $data = collect($response->json('data'))->whereIn('code', $codes)->values();

        $this->assertCount(3, $data);
        $this->assertEquals('500.00', $data[0]['purchase_price']);
        $this->assertEquals('1000.00', $data[1]['purchase_price']);
        $this->assertEquals('2000.00', $data[2]['purchase_price']);
    }
}
