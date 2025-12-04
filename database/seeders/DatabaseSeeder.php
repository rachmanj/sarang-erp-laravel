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
            CompanyEntitySeeder::class,
            CurrencySeeder::class, // Add currencies FIRST
            TradingCoASeeder::class,
            TradingTaxCodeSeeder::class,
            ProductCategoryAccountSeeder::class, // Add product categories with account mapping FIRST
            TradingSampleDataSeeder::class,
            // FundProjectSeeder::class,
            RolePermissionSeeder::class,
            // DemoJournalSeeder::class,
            TermsSeeder::class,
            AssetCategorySeeder::class,
            BusinessPartnerSampleSeeder::class, // Add sample business partners
            WarehouseSeeder::class, // Add warehouses
            ExchangeRateSeeder::class, // Add exchange rates AFTER currencies
            FXAccountSeeder::class, // Add FX gain/loss accounts
            MultiCurrencyMigrationSeeder::class, // Migrate existing data to IDR
            ControlAccountSeeder::class, // Set up control accounts AFTER all data is seeded
            // TrainingCustomerSeeder::class, // Commented out - requires additional models
            // TrainingVendorSeeder::class, // Commented out - requires additional models
            // TrainingAssetSeeder::class, // Commented out - requires additional models
        ]);
    }
}
