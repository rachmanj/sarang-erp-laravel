<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExchangeRate;
use App\Models\Currency;
use Carbon\Carbon;

class SampleExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        // Get currency IDs
        $idrId = Currency::where('code', 'IDR')->first()->id;
        $usdId = Currency::where('code', 'USD')->first()->id;

        // Create exchange rates for today
        $today = Carbon::today();

        // USD to IDR: 1 USD = 16,500 IDR
        ExchangeRate::create([
            'from_currency_id' => $usdId,
            'to_currency_id' => $idrId,
            'rate' => 16500.000000,
            'effective_date' => $today,
            'rate_type' => 'daily',
            'source' => 'manual',
            'created_by' => null,
        ]);

        // IDR to USD: 1 IDR = 0.000061 USD (inverse rate)
        ExchangeRate::create([
            'from_currency_id' => $idrId,
            'to_currency_id' => $usdId,
            'rate' => 0.000061,
            'effective_date' => $today,
            'rate_type' => 'daily',
            'source' => 'manual',
            'created_by' => null,
        ]);

        $this->command->info('Sample exchange rates created successfully');
        $this->command->info('USD/IDR rate: 1 USD = 16,500 IDR');
    }
}
