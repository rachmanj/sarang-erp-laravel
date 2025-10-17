<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyService
{
    /**
     * Get all currencies (active and inactive)
     */
    public function getAllCurrencies(): Collection
    {
        return Currency::orderBy('code')->get();
    }

    /**
     * Get all active currencies
     */
    public function getActiveCurrencies(): Collection
    {
        return Currency::active()->orderBy('code')->get();
    }

    /**
     * Get the base currency (IDR)
     */
    public function getBaseCurrency(): ?Currency
    {
        return Currency::getBaseCurrency();
    }

    /**
     * Validate if currency code exists and is active
     */
    public function validateCurrencyCode(string $code): bool
    {
        return Currency::validateCurrencyCode($code);
    }

    /**
     * Get currency by code
     */
    public function getCurrencyByCode(string $code): ?Currency
    {
        return Currency::getCurrencyByCode($code);
    }

    /**
     * Get currency by ID
     */
    public function getCurrencyById(int $id): ?Currency
    {
        return Currency::find($id);
    }

    /**
     * Create a new currency
     */
    public function createCurrency(array $data): Currency
    {
        // Ensure only one base currency exists
        if ($data['is_base_currency'] ?? false) {
            Currency::where('is_base_currency', true)->update(['is_base_currency' => false]);
        }

        return Currency::create($data);
    }

    /**
     * Update currency
     */
    public function updateCurrency(Currency $currency, array $data): Currency
    {
        // Ensure only one base currency exists
        if ($data['is_base_currency'] ?? false) {
            Currency::where('is_base_currency', true)
                ->where('id', '!=', $currency->id)
                ->update(['is_base_currency' => false]);
        }

        $currency->update($data);
        return $currency;
    }

    /**
     * Delete currency (soft delete by deactivating)
     */
    public function deleteCurrency(Currency $currency): bool
    {
        // Prevent deletion of base currency
        if ($currency->is_base_currency) {
            throw new \Exception('Cannot delete base currency');
        }

        // Check if currency is used in transactions
        if ($this->isCurrencyInUse($currency)) {
            // Deactivate instead of delete
            return $currency->update(['is_active' => false]);
        }

        return $currency->delete();
    }

    /**
     * Check if currency is in use
     */
    public function isCurrencyInUse(Currency $currency): bool
    {
        // Check various tables for currency usage
        $tables = [
            'purchase_orders' => 'currency_id',
            'sales_orders' => 'currency_id',
            'purchase_invoices' => 'currency_id',
            'sales_invoices' => 'currency_id',
            'purchase_payments' => 'currency_id',
            'sales_receipts' => 'currency_id',
            'journals' => 'currency_id',
            'journal_lines' => 'currency_id',
            'business_partners' => 'default_currency_id',
        ];

        foreach ($tables as $table => $column) {
            if (\DB::table($table)->where($column, $currency->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get currency statistics
     */
    public function getCurrencyStats(): array
    {
        $baseCurrency = $this->getBaseCurrency();

        return [
            'total_currencies' => Currency::count(),
            'active_currencies' => Currency::active()->count(),
            'base_currency' => $baseCurrency ? $baseCurrency->code : null,
            'currencies_in_use' => Currency::whereHas('exchangeRatesFrom')
                ->orWhereHas('exchangeRatesTo')
                ->count(),
        ];
    }

    /**
     * Get currencies for dropdown
     */
    public function getCurrenciesForDropdown(): array
    {
        return $this->getActiveCurrencies()
            ->mapWithKeys(function ($currency) {
                return [$currency->id => "{$currency->code} - {$currency->name}"];
            })
            ->toArray();
    }

    /**
     * Get currency pairs for exchange rate entry
     */
    public function getCurrencyPairs(): array
    {
        $currencies = $this->getActiveCurrencies();
        $baseCurrency = $this->getBaseCurrency();

        $pairs = [];

        foreach ($currencies as $currency) {
            if ($currency->id !== $baseCurrency->id) {
                $pairs[] = [
                    'from_currency_id' => $baseCurrency->id,
                    'to_currency_id' => $currency->id,
                    'from_currency_code' => $baseCurrency->code,
                    'to_currency_code' => $currency->code,
                    'display_name' => "{$baseCurrency->code}/{$currency->code}",
                ];
            }
        }

        return $pairs;
    }
}
