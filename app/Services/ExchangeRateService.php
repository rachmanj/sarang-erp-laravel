<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\ErpParameter;
use Illuminate\Support\Facades\DB;

class ExchangeRateService
{
    /**
     * Get exchange rate with fallback logic
     */
    public function getRate($fromCurrency, $toCurrency, $date, $rateType = 'auto'): ?ExchangeRate
    {
        $date = $date ?: now()->toDateString();

        $query = ExchangeRate::forCurrencyPair($fromCurrency, $toCurrency)
            ->where('effective_date', '<=', $date);

        if ($rateType !== 'auto') {
            $query->where('rate_type', $rateType);
        }

        $rate = $query->latest()->first();

        if (!$rate && $rateType === 'auto') {
            // Try to get the latest available rate before the date
            $rate = ExchangeRate::forCurrencyPair($fromCurrency, $toCurrency)
                ->latest()
                ->first();
        }

        return $rate;
    }

    /**
     * Get rate for transaction with manual override option
     */
    public function getRateForTransaction($currency, $date, $manualRate = null): array
    {
        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            throw new \Exception('Base currency not found');
        }

        if ($manualRate !== null) {
            return [
                'rate' => $manualRate,
                'rate_type' => 'manual',
                'source' => 'manual_override',
                'effective_date' => $date,
            ];
        }

        $exchangeRate = $this->getRate($baseCurrency->id, $currency, $date);

        if (!$exchangeRate) {
            throw new \Exception("No exchange rate available for {$currency} on {$date}");
        }

        return [
            'rate' => $exchangeRate->rate,
            'rate_type' => $exchangeRate->rate_type,
            'source' => $exchangeRate->source,
            'effective_date' => $exchangeRate->effective_date,
            'exchange_rate_id' => $exchangeRate->id,
        ];
    }

    /**
     * Create manual exchange rate
     */
    public function createManualRate($fromCurrency, $toCurrency, $rate, $effectiveDate, $createdBy = null): ExchangeRate
    {
        // Validate rate
        if (!$this->validateRate($fromCurrency, $toCurrency, $rate, $effectiveDate)) {
            throw new \Exception('Exchange rate exceeds tolerance limit');
        }

        return ExchangeRate::createManualRate($fromCurrency, $toCurrency, $rate, $effectiveDate, $createdBy);
    }

    /**
     * Create daily exchange rates
     */
    public function createDailyRates($date, $rates, $createdBy = null): array
    {
        DB::beginTransaction();

        try {
            $createdRates = ExchangeRate::createDailyRates($date, $rates, $createdBy);
            DB::commit();

            return $createdRates;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Convert amount to base currency (IDR)
     */
    public function convertToBase($amount, $currency, $date): float
    {
        if ($currency instanceof Currency) {
            $currency = $currency->id;
        }

        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            throw new \Exception('Base currency not found');
        }

        if ($currency == $baseCurrency->id) {
            return $amount;
        }

        $rate = $this->getRate($baseCurrency->id, $currency, $date);

        if (!$rate) {
            throw new \Exception("No exchange rate available for conversion to base currency");
        }

        return $amount * $rate->rate;
    }

    /**
     * Convert amount from base currency (IDR) to foreign currency
     */
    public function convertFromBase($amount, $toCurrency, $date): float
    {
        if ($toCurrency instanceof Currency) {
            $toCurrency = $toCurrency->id;
        }

        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            throw new \Exception('Base currency not found');
        }

        if ($toCurrency == $baseCurrency->id) {
            return $amount;
        }

        $rate = $this->getRate($baseCurrency->id, $toCurrency, $date);

        if (!$rate) {
            throw new \Exception("No exchange rate available for conversion from base currency");
        }

        return $amount / $rate->rate;
    }

    /**
     * Convert between two currencies
     */
    public function convert($amount, $fromCurrency, $toCurrency, $date): float
    {
        if ($fromCurrency instanceof Currency) {
            $fromCurrency = $fromCurrency->id;
        }

        if ($toCurrency instanceof Currency) {
            $toCurrency = $toCurrency->id;
        }

        if ($fromCurrency == $toCurrency) {
            return $amount;
        }

        // Convert to base currency first, then to target currency
        $baseAmount = $this->convertToBase($amount, $fromCurrency, $date);
        return $this->convertFromBase($baseAmount, $toCurrency, $date);
    }

    /**
     * Validate exchange rate within tolerance
     */
    public function validateRate($fromCurrency, $toCurrency, $rate, $date): bool
    {
        return ExchangeRate::validateRate($fromCurrency, $toCurrency, $rate, $date);
    }

    /**
     * Get rate history for currency pair
     */
    public function getRateHistory($fromCurrency, $toCurrency, $startDate = null, $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = ExchangeRate::forCurrencyPair($fromCurrency, $toCurrency);

        if ($startDate) {
            $query->where('effective_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('effective_date', '<=', $endDate);
        }

        return $query->orderBy('effective_date', 'desc')->get();
    }

    /**
     * Get today's rates for all currencies
     */
    public function getTodaysRates(): array
    {
        $today = now()->toDateString();
        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            return [];
        }

        $currencies = Currency::active()->where('id', '!=', $baseCurrency->id)->get();
        $rates = [];

        foreach ($currencies as $currency) {
            $rate = $this->getRate($baseCurrency->id, $currency->id, $today);

            $rates[] = [
                'currency_id' => $currency->id,
                'currency_code' => $currency->code,
                'currency_name' => $currency->name,
                'rate' => $rate ? $rate->rate : null,
                'rate_type' => $rate ? $rate->rate_type : null,
                'effective_date' => $rate ? $rate->effective_date : null,
                'is_available' => $rate !== null,
            ];
        }

        return $rates;
    }

    /**
     * Get exchange rate statistics
     */
    public function getExchangeRateStats(): array
    {
        return [
            'total_rates' => ExchangeRate::count(),
            'daily_rates' => ExchangeRate::daily()->count(),
            'manual_rates' => ExchangeRate::manual()->count(),
            'rates_today' => ExchangeRate::forDate(now()->toDateString())->count(),
            'rates_this_month' => ExchangeRate::whereMonth('effective_date', now()->month)
                ->whereYear('effective_date', now()->year)
                ->count(),
        ];
    }

    /**
     * Update currency revaluation
     */
    public function updateRevaluation($currencyId, $newRate, $revaluationDate): array
    {
        $baseCurrency = Currency::getBaseCurrency();

        if (!$baseCurrency) {
            throw new \Exception('Base currency not found');
        }

        $oldRate = $this->getRate($baseCurrency->id, $currencyId, $revaluationDate);

        if (!$oldRate) {
            throw new \Exception('No existing rate found for revaluation');
        }

        $rateChange = abs($newRate - $oldRate->rate);
        $percentageChange = ($rateChange / $oldRate->rate) * 100;

        return [
            'old_rate' => $oldRate->rate,
            'new_rate' => $newRate,
            'rate_change' => $rateChange,
            'percentage_change' => $percentageChange,
            'is_significant' => $percentageChange > 5, // 5% threshold for significant change
        ];
    }
}
