<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;

class MultiCurrencyMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder migrates existing data to support multi-currency by setting all existing records to IDR.
     */
    public function run(): void
    {
        $baseCurrency = Currency::where('code', 'IDR')->first();

        if (!$baseCurrency) {
            $this->command->error('IDR currency not found. Please run CurrencySeeder first.');
            return;
        }

        $baseCurrencyId = $baseCurrency->id;
        $baseExchangeRate = 1.000000;

        $this->command->info('Starting multi-currency migration...');

        // Tables to migrate with currency_id column
        $tablesWithCurrency = [
            'purchase_orders',
            'sales_orders',
            'purchase_invoices',
            'sales_invoices',
            'purchase_payments',
            'sales_receipts',
            'journals',
        ];

        foreach ($tablesWithCurrency as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                try {
                    // Set currency_id to IDR for all existing records
                    DB::table($table)
                        ->whereNull('currency_id')
                        ->update(['currency_id' => $baseCurrencyId]);

                    // Set exchange_rate to 1.000000 for all existing records
                    if (DB::getSchemaBuilder()->hasColumn($table, 'exchange_rate')) {
                        DB::table($table)
                            ->whereNull('exchange_rate')
                            ->update(['exchange_rate' => $baseExchangeRate]);
                    }

                    $this->command->info("✓ Migrated {$table} to IDR");
                } catch (\Exception $e) {
                    $this->command->warn("⚠ Could not migrate {$table}: " . $e->getMessage());
                }
            }
        }

        // Tables with foreign currency amount columns
        $tablesWithForeignAmounts = [
            'purchase_orders' => ['total_amount_foreign', 'freight_cost_foreign', 'handling_cost_foreign', 'insurance_cost_foreign', 'total_cost_foreign'],
            'sales_orders' => ['total_amount_foreign'],
            'purchase_order_lines' => ['unit_price_foreign', 'amount_foreign'],
            'sales_order_lines' => ['unit_price_foreign', 'amount_foreign'],
            'journal_lines' => ['debit_foreign', 'credit_foreign'],
        ];

        foreach ($tablesWithForeignAmounts as $table => $columns) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                try {
                    foreach ($columns as $column) {
                        if (DB::getSchemaBuilder()->hasColumn($table, $column)) {
                            // Set foreign amounts equal to base amounts for existing records
                            if (strpos($column, 'foreign') !== false) {
                                $baseColumn = str_replace('_foreign', '', $column);

                                if (DB::getSchemaBuilder()->hasColumn($table, $baseColumn)) {
                                    DB::statement("UPDATE {$table} SET {$column} = {$baseColumn} WHERE {$column} IS NULL");
                                }
                            }
                        }
                    }
                    $this->command->info("✓ Migrated foreign amounts for {$table}");
                } catch (\Exception $e) {
                    $this->command->warn("⚠ Could not migrate foreign amounts for {$table}: " . $e->getMessage());
                }
            }
        }

        // Update inventory items with currency
        if (DB::getSchemaBuilder()->hasTable('inventory_items')) {
            try {
                DB::table('inventory_items')
                    ->whereNull('purchase_currency_id')
                    ->update(['purchase_currency_id' => $baseCurrencyId]);

                DB::table('inventory_items')
                    ->whereNull('selling_currency_id')
                    ->update(['selling_currency_id' => $baseCurrencyId]);

                $this->command->info("✓ Migrated inventory items to IDR");
            } catch (\Exception $e) {
                $this->command->warn("⚠ Could not migrate inventory items: " . $e->getMessage());
            }
        }

        // Update business partners with default currency
        if (DB::getSchemaBuilder()->hasTable('business_partners')) {
            try {
                DB::table('business_partners')
                    ->whereNull('default_currency_id')
                    ->update(['default_currency_id' => $baseCurrencyId]);

                $this->command->info("✓ Migrated business partners to IDR");
            } catch (\Exception $e) {
                $this->command->warn("⚠ Could not migrate business partners: " . $e->getMessage());
            }
        }

        $this->command->info('Multi-currency migration completed successfully!');
        $this->command->info('All existing data has been migrated to use IDR as the base currency.');
    }
}
