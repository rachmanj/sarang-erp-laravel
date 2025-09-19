<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\InventoryItem;

class TradingSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing product categories
        $categoryIds = [];
        $existingCategories = [
            'ELECTRONICS' => ProductCategory::where('code', 'ELECTRONICS')->first(),
            'STATIONERY' => ProductCategory::where('code', 'STATIONERY')->first(),
            'FURNITURE' => ProductCategory::where('code', 'FURNITURE')->first(),
            'VEHICLES' => ProductCategory::where('code', 'VEHICLES')->first(),
            'SERVICES' => ProductCategory::where('code', 'SERVICES')->first(),
        ];

        foreach ($existingCategories as $code => $category) {
            if ($category) {
                $categoryIds[$code] = $category->id;
            }
        }

        // Create sample inventory items
        $items = [
            [
                'code' => 'ITEM001',
                'name' => 'Laptop Dell Inspiron 15',
                'description' => 'Laptop Dell Inspiron 15 3000 Series dengan Intel Core i5',
                'category_id' => $categoryIds['ELECTRONICS'],
                'unit_of_measure' => 'pcs',
                'purchase_price' => 8000000,
                'selling_price' => 9500000,
                'min_stock_level' => 5,
                'max_stock_level' => 50,
                'reorder_point' => 10,
                'valuation_method' => 'fifo'
            ],
            [
                'code' => 'ITEM002',
                'name' => 'Smartphone Samsung Galaxy A54',
                'description' => 'Smartphone Samsung Galaxy A54 128GB',
                'category_id' => $categoryIds['ELECTRONICS'],
                'unit_of_measure' => 'pcs',
                'purchase_price' => 3500000,
                'selling_price' => 4200000,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'reorder_point' => 20,
                'valuation_method' => 'fifo'
            ],
            [
                'code' => 'ITEM003',
                'name' => 'Kaos Polo Cotton',
                'description' => 'Kaos polo cotton premium berbagai warna',
                'category_id' => $categoryIds['STATIONERY'],
                'unit_of_measure' => 'pcs',
                'purchase_price' => 85000,
                'selling_price' => 125000,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'reorder_point' => 100,
                'valuation_method' => 'fifo'
            ],
            [
                'code' => 'ITEM004',
                'name' => 'Sepatu Nike Air Max',
                'description' => 'Sepatu olahraga Nike Air Max original',
                'category_id' => $categoryIds['VEHICLES'],
                'unit_of_measure' => 'pcs',
                'purchase_price' => 1200000,
                'selling_price' => 1500000,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'reorder_point' => 40,
                'valuation_method' => 'fifo'
            ],
            [
                'code' => 'ITEM005',
                'name' => 'Blender Philips',
                'description' => 'Blender Philips HR2115 dengan 2 liter kapasitas',
                'category_id' => $categoryIds['FURNITURE'],
                'unit_of_measure' => 'pcs',
                'purchase_price' => 450000,
                'selling_price' => 650000,
                'min_stock_level' => 15,
                'max_stock_level' => 150,
                'reorder_point' => 30,
                'valuation_method' => 'fifo'
            ],
            [
                'code' => 'ITEM006',
                'name' => 'Snack Keripik Singkong',
                'description' => 'Keripik singkong pedas kemasan 200gr',
                'category_id' => $categoryIds['SERVICES'],
                'unit_of_measure' => 'pcs',
                'purchase_price' => 8000,
                'selling_price' => 12000,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'reorder_point' => 200,
                'valuation_method' => 'fifo'
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::create($item);
        }
    }
}
