<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\ExchangeRate;

class ExchangeRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseCurrency = Currency::where('code', 'IDR')->first();

        if (!$baseCurrency) {
            $this->command->error('IDR currency not found. Please run CurrencySeeder first.');
            return;
        }

        $today = now()->toDateString();

        // Sample exchange rates (as of a recent date - these are example rates)
        $rates = [
            'USD' => 16500.00,  // 1 USD = 16,500 IDR (updated for testing)
            'SGD' => 11500.00,  // 1 SGD = 11,500 IDR
            'EUR' => 16800.00,  // 1 EUR = 16,800 IDR
            'CNY' => 2150.00,   // 1 CNY = 2,150 IDR
            'JPY' => 105.00,    // 1 JPY = 105 IDR
            'MYR' => 3350.00,   // 1 MYR = 3,350 IDR
            'AUD' => 10200.00,  // 1 AUD = 10,200 IDR
            'GBP' => 19500.00,  // 1 GBP = 19,500 IDR
            'HKD' => 1980.00,   // 1 HKD = 1,980 IDR
        ];

        foreach ($rates as $currencyCode => $rate) {
            $currency = Currency::where('code', $currencyCode)->first();

            if ($currency) {
                ExchangeRate::updateOrCreate(
                    [
                        'from_currency_id' => $baseCurrency->id,
                        'to_currency_id' => $currency->id,
                        'effective_date' => $today,
                    ],
                    [
                        'rate' => $rate,
                        'rate_type' => 'daily',
                        'source' => 'manual',
                        'created_by' => null, // Will be set when users are created
                    ]
                );

                // Also create inverse rates (foreign currency to IDR)
                ExchangeRate::updateOrCreate(
                    [
                        'from_currency_id' => $currency->id,
                        'to_currency_id' => $baseCurrency->id,
                        'effective_date' => $today,
                    ],
                    [
                        'rate' => 1 / $rate, // Inverse rate
                        'rate_type' => 'daily',
                        'source' => 'manual',
                        'created_by' => null,
                    ]
                );
            }
        }

        $this->command->info('Exchange rates seeded successfully!');
    }
}
