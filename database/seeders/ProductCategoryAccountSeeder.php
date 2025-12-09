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
        // Delete obsolete categories first
        $obsoleteCodes = ['FURNITURE', 'VEHICLES', 'SERVICES'];
        foreach ($obsoleteCodes as $code) {
            $category = ProductCategory::where('code', $code)->first();
            if ($category) {
                // Check if category has items or child categories before deleting
                if ($category->items()->count() === 0 && $category->children()->count() === 0) {
                    $category->delete();
                    $this->command->info("Deleted obsolete category: {$code}");
                } else {
                    $this->command->warn("Cannot delete category {$code}: has items or child categories");
                }
            }
        }

        // Update ELECTRONICS to Electronics (rename if exists)
        $electronicsCategory = ProductCategory::where('code', 'ELECTRONICS')->first();
        if ($electronicsCategory) {
            $electronicsCategory->update([
                'code' => 'ELECTRONICS',
                'name' => 'Electronics',
            ]);
            $this->command->info('Updated ELECTRONICS category name to Electronics');
        }

        // Create/update product categories with account mappings (sequential numbering 01-11)
        $categories = [
            [
                'code' => 'STATIONERY',
                'name' => 'Stationery',
                'description' => 'Office supplies and stationery items',
                'inventory_account' => '1.1.3.01.01',
                'cogs_account' => '5.1.01',
                'sales_account' => '4.1.1.01',
            ],
            [
                'code' => 'ELECTRONICS',
                'name' => 'Electronics',
                'description' => 'Electronic devices and components',
                'inventory_account' => '1.1.3.01.02',
                'cogs_account' => '5.1.02',
                'sales_account' => '4.1.1.02',
            ],
            [
                'code' => 'WELDING',
                'name' => 'Welding',
                'description' => 'Welding equipment and supplies',
                'inventory_account' => '1.1.3.01.03',
                'cogs_account' => '5.1.03',
                'sales_account' => '4.1.1.03',
            ],
            [
                'code' => 'ELECTRICAL',
                'name' => 'Electrical',
                'description' => 'Electrical components and equipment',
                'inventory_account' => '1.1.3.01.04',
                'cogs_account' => '5.1.04',
                'sales_account' => '4.1.1.04',
            ],
            [
                'code' => 'OTOMOTIF',
                'name' => 'Otomotif',
                'description' => 'Automotive parts and equipment',
                'inventory_account' => '1.1.3.01.05',
                'cogs_account' => '5.1.05',
                'sales_account' => '4.1.1.05',
            ],
            [
                'code' => 'LIFTING_TOOLS',
                'name' => 'Lifting Tools',
                'description' => 'Lifting and hoisting equipment',
                'inventory_account' => '1.1.3.01.06',
                'cogs_account' => '5.1.06',
                'sales_account' => '4.1.1.06',
            ],
            [
                'code' => 'CONSUMABLES',
                'name' => 'Consumables',
                'description' => 'Consumable supplies and materials',
                'inventory_account' => '1.1.3.01.07',
                'cogs_account' => '5.1.07',
                'sales_account' => '4.1.1.07',
            ],
            [
                'code' => 'CHEMICAL',
                'name' => 'Chemical',
                'description' => 'Chemical products and supplies',
                'inventory_account' => '1.1.3.01.08',
                'cogs_account' => '5.1.08',
                'sales_account' => '4.1.1.08',
            ],
            [
                'code' => 'BOLT_NUT',
                'name' => 'Bolt Nut',
                'description' => 'Bolts, nuts, and fasteners',
                'inventory_account' => '1.1.3.01.09',
                'cogs_account' => '5.1.09',
                'sales_account' => '4.1.1.09',
            ],
            [
                'code' => 'SAFETY',
                'name' => 'Safety',
                'description' => 'Safety equipment and supplies',
                'inventory_account' => '1.1.3.01.10',
                'cogs_account' => '5.1.10',
                'sales_account' => '4.1.1.10',
            ],
            [
                'code' => 'TOOLS',
                'name' => 'Tools',
                'description' => 'Hand tools and equipment',
                'inventory_account' => '1.1.3.01.11',
                'cogs_account' => '5.1.11',
                'sales_account' => '4.1.1.11',
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
                $this->command->info("Created category: {$categoryData['name']}");
            } else {
                // Update existing category
                $category->update([
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'is_active' => true,
                ]);
                $this->command->info("Updated category: {$categoryData['name']}");
            }

            // Map accounts
            $updateData = [];

            if ($categoryData['inventory_account']) {
                $inventoryAccount = Account::where('code', $categoryData['inventory_account'])->first();
                if ($inventoryAccount) {
                    $updateData['inventory_account_id'] = $inventoryAccount->id;
                } else {
                    $this->command->warn("Inventory account not found: {$categoryData['inventory_account']}");
                }
            } else {
                $updateData['inventory_account_id'] = null;
            }

            if ($categoryData['cogs_account']) {
                $cogsAccount = Account::where('code', $categoryData['cogs_account'])->first();
                if ($cogsAccount) {
                    $updateData['cogs_account_id'] = $cogsAccount->id;
                } else {
                    $this->command->warn("COGS account not found: {$categoryData['cogs_account']}");
                }
            }

            if ($categoryData['sales_account']) {
                $salesAccount = Account::where('code', $categoryData['sales_account'])->first();
                if ($salesAccount) {
                    $updateData['sales_account_id'] = $salesAccount->id;
                } else {
                    $this->command->warn("Sales account not found: {$categoryData['sales_account']}");
                }
            }

            // Update all account mappings at once
            if (!empty($updateData)) {
                $category->update($updateData);
            }
        }

        $this->command->info('Completed product categories update with account mappings.');
    }
}
