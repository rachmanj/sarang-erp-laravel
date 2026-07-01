<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryItemPartNumber;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryItemPartNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_store_creates_inventory_item_with_part_numbers(): void
    {
        $this->seed(UnitOfMeasureSeeder::class);

        $user = User::query()->where('username', 'superadmin')->firstOrFail();
        $categoryId = (int) DB::table('product_categories')->value('id');
        $baseUnitId = UnitOfMeasure::query()->where('is_active', true)->value('id');

        $this->assertNotNull($baseUnitId);

        $code = 'PN-TEST-'.uniqid();

        $response = $this->actingAs($user)->post(route('inventory.store'), [
            'code' => $code,
            'name' => 'Part Number Test Item',
            'category_id' => $categoryId,
            'base_unit_id' => $baseUnitId,
            'selling_price' => 100000,
            'item_type' => 'item',
            'valuation_method' => 'fifo',
            'is_active' => 'on',
            'part_numbers' => [
                [
                    'id' => '',
                    'part_number' => 'PN-5800C',
                    'description' => 'Customer PN',
                    'is_default' => '1',
                ],
                [
                    'id' => '',
                    'part_number' => 'MFG-123',
                    'description' => 'Manufacturer PN',
                    'is_default' => '0',
                ],
            ],
        ]);

        $item = InventoryItem::query()->where('code', $code)->firstOrFail();

        $response->assertRedirect(route('inventory.show', $item->id));

        $this->assertDatabaseHas('inventory_item_part_numbers', [
            'inventory_item_id' => $item->id,
            'part_number' => 'PN-5800C',
            'description' => 'Customer PN',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('inventory_item_part_numbers', [
            'inventory_item_id' => $item->id,
            'part_number' => 'MFG-123',
            'description' => 'Manufacturer PN',
            'is_default' => false,
        ]);

        $this->assertSame(2, InventoryItemPartNumber::query()->where('inventory_item_id', $item->id)->count());
    }

    public function test_create_form_includes_part_numbers_section(): void
    {
        $user = User::query()->where('username', 'superadmin')->firstOrFail();

        $response = $this->actingAs($user)->get(route('inventory.create'));

        $response->assertOk();
        $response->assertSee('id="part-numbers-table"', false);
        $response->assertSee('name="part_numbers[0][part_number]"', false);
        $response->assertSee('id="add-part-number"', false);
    }
}
