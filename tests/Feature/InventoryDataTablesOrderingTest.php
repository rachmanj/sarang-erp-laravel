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

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password')
        ]);
    }

    public function test_inventory_datatables_orders_by_code()
    {
        // Create test items
        $category = ProductCategory::factory()->create();

        InventoryItem::factory()->create(['code' => 'ITEM001', 'name' => 'Item A', 'category_id' => $category->id]);
        InventoryItem::factory()->create(['code' => 'ITEM002', 'name' => 'Item B', 'category_id' => $category->id]);
        InventoryItem::factory()->create(['code' => 'ITEM003', 'name' => 'Item C', 'category_id' => $category->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/inventory/data?order[0][column]=0&order[0][dir]=asc');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        $this->assertEquals('ITEM001', $data[0]['code']);
        $this->assertEquals('ITEM002', $data[1]['code']);
        $this->assertEquals('ITEM003', $data[2]['code']);
    }

    public function test_inventory_datatables_orders_by_name_desc()
    {
        $category = ProductCategory::factory()->create();

        InventoryItem::factory()->create(['name' => 'Alpha', 'category_id' => $category->id]);
        InventoryItem::factory()->create(['name' => 'Beta', 'category_id' => $category->id]);
        InventoryItem::factory()->create(['name' => 'Gamma', 'category_id' => $category->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/inventory/data?order[0][column]=1&order[0][dir]=desc');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        $this->assertEquals('Gamma', $data[0]['name']);
        $this->assertEquals('Beta', $data[1]['name']);
        $this->assertEquals('Alpha', $data[2]['name']);
    }

    public function test_inventory_datatables_orders_by_purchase_price()
    {
        $category = ProductCategory::factory()->create();

        InventoryItem::factory()->create(['purchase_price' => 1000, 'category_id' => $category->id]);
        InventoryItem::factory()->create(['purchase_price' => 2000, 'category_id' => $category->id]);
        InventoryItem::factory()->create(['purchase_price' => 500, 'category_id' => $category->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/inventory/data?order[0][column]=4&order[0][dir]=asc');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        $this->assertEquals(500, $data[0]['purchase_price']);
        $this->assertEquals(1000, $data[1]['purchase_price']);
        $this->assertEquals(2000, $data[2]['purchase_price']);
    }
}
