<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\Accounting\Account;
use Illuminate\Database\Seeder;

class ProductCategoryAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create product categories with account mappings
        $categories = [
            [
                'code' => 'STATIONERY',
                'name' => 'Stationery',
                'description' => 'Office supplies and stationery items',
                'inventory_account' => '1.1.3.01', // Persediaan Barang Dagangan - Stationery
                'cogs_account' => '5.1.1.01', // HPP - Stationery
                'sales_account' => '4.1.1.01', // Penjualan - Stationery
            ],
            [
                'code' => 'ELECTRONICS',
                'name' => 'Electronics',
                'description' => 'Electronic devices and components',
                'inventory_account' => '1.1.3.02', // Persediaan Barang Dagangan - Electronics
                'cogs_account' => '5.1.1.02', // HPP - Electronics
                'sales_account' => '4.1.1.02', // Penjualan - Electronics
            ],
            [
                'code' => 'FURNITURE',
                'name' => 'Furniture',
                'description' => 'Office furniture and fixtures',
                'inventory_account' => '1.1.3.03', // Persediaan Barang Dagangan - Furniture
                'cogs_account' => '5.1.1.03', // HPP - Furniture
                'sales_account' => '4.1.1.03', // Penjualan - Furniture
            ],
            [
                'code' => 'VEHICLES',
                'name' => 'Vehicles',
                'description' => 'Motor vehicles and transportation',
                'inventory_account' => '1.1.3.04', // Persediaan Barang Dagangan - Vehicles
                'cogs_account' => '5.1.1.04', // HPP - Vehicles
                'sales_account' => '4.1.1.04', // Penjualan - Vehicles
            ],
            [
                'code' => 'SERVICES',
                'name' => 'Services',
                'description' => 'Service-based offerings',
                'inventory_account' => null, // Services don't have inventory
                'cogs_account' => '5.1.2.01', // HPP - Services
                'sales_account' => '4.1.2.01', // Penjualan - Services
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = ProductCategory::where('code', $categoryData['code'])->first();

            if (!$category) {
                $category = ProductCategory::create([
                    'code' => $categoryData['code'],
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'is_active' => true,
                ]);
            }

            // Map accounts
            if ($categoryData['inventory_account']) {
                $inventoryAccount = Account::where('code', $categoryData['inventory_account'])->first();
                if ($inventoryAccount) {
                    $category->update(['inventory_account_id' => $inventoryAccount->id]);
                }
            }

            if ($categoryData['cogs_account']) {
                $cogsAccount = Account::where('code', $categoryData['cogs_account'])->first();
                if ($cogsAccount) {
                    $category->update(['cogs_account_id' => $cogsAccount->id]);
                }
            }

            if ($categoryData['sales_account']) {
                $salesAccount = Account::where('code', $categoryData['sales_account'])->first();
                if ($salesAccount) {
                    $category->update(['sales_account_id' => $salesAccount->id]);
                }
            }
        }

        $this->command->info('Updated product categories with account mappings.');
    }
}
