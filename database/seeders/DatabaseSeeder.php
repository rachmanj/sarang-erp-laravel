<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'username' => 'superadmin', 'password' => bcrypt('password')]
        );

        $this->call([
            TradingCoASeeder::class,
            TradingTaxCodeSeeder::class,
            ProductCategoryAccountSeeder::class, // Add product categories with account mapping FIRST
            TradingSampleDataSeeder::class,
            FundProjectSeeder::class,
            RolePermissionSeeder::class,
            DemoJournalSeeder::class,
            TermsSeeder::class,
            AssetCategorySeeder::class,
            BusinessPartnerSampleSeeder::class, // Add sample business partners
            WarehouseSeeder::class, // Add warehouses
            ControlAccountSeeder::class, // Set up control accounts AFTER all data is seeded
            // TrainingCustomerSeeder::class, // Commented out - requires additional models
            // TrainingVendorSeeder::class, // Commented out - requires additional models
            // TrainingAssetSeeder::class, // Commented out - requires additional models
        ]);
    }
}
