<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert currency-related ERP parameters
        $parameters = [
            [
                'category' => 'currency_settings',
                'parameter_key' => 'default_currency_id',
                'parameter_name' => 'Default Currency',
                'parameter_value' => '1', // IDR will be ID 1
                'data_type' => 'integer',
                'description' => 'System default currency (IDR)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency_settings',
                'parameter_key' => 'auto_exchange_rate_enabled',
                'parameter_name' => 'Auto Exchange Rate Enabled',
                'parameter_value' => 'true',
                'data_type' => 'boolean',
                'description' => 'Enable/disable automatic exchange rate fetching',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency_settings',
                'parameter_key' => 'exchange_rate_tolerance',
                'parameter_name' => 'Exchange Rate Tolerance',
                'parameter_value' => '10',
                'data_type' => 'decimal',
                'description' => 'Percentage variance allowed for manual rates',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency_settings',
                'parameter_key' => 'realized_gain_loss_account_id',
                'parameter_name' => 'Realized FX Gain/Loss Account',
                'parameter_value' => '0', // Will be updated after COA is seeded
                'data_type' => 'integer',
                'description' => 'Account for realized FX gains/losses',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category' => 'currency_settings',
                'parameter_key' => 'unrealized_gain_loss_account_id',
                'parameter_name' => 'Unrealized FX Gain/Loss Account',
                'parameter_value' => '0', // Will be updated after COA is seeded
                'data_type' => 'integer',
                'description' => 'Account for unrealized FX gains/losses',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($parameters as $param) {
            DB::table('erp_parameters')->insert($param);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('erp_parameters')
            ->where('category', 'currency_settings')
            ->delete();
    }
};
