<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'IDR',
                'name' => 'Indonesian Rupiah',
                'symbol' => 'Rp',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'SGD',
                'name' => 'Singapore Dollar',
                'symbol' => 'S$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'decimal_places' => 0, // Yen typically doesn't use decimal places
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'MYR',
                'name' => 'Malaysian Ringgit',
                'symbol' => 'RM',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
            [
                'code' => 'HKD',
                'name' => 'Hong Kong Dollar',
                'symbol' => 'HK$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base_currency' => false,
            ],
        ];

        // Ensure IDR (base currency) is created first
        $idr = array_shift($currencies); // Remove IDR from array
        
        // Check if IDR exists, if not create it (will get next available ID)
        $idrRecord = Currency::where('code', 'IDR')->first();
        if (!$idrRecord) {
            // Create IDR - it should get id = 1 if table is empty
            Currency::create($idr);
        } else {
            // Update existing IDR to ensure it's marked as base currency
            $idrRecord->update($idr);
        }
        
        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('Currencies seeded successfully!');
    }
}
