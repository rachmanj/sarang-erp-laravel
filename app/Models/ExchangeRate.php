<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
        'rate_type',
        'source',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
        'rate_type' => 'string',
        'source' => 'string',
    ];

    // Relationships
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revaluations(): HasMany
    {
        return $this->hasMany(CurrencyRevaluation::class, 'reference_rate_id');
    }

    // Scopes
    public function scopeDaily($query)
    {
        return $query->where('rate_type', 'daily');
    }

    public function scopeManual($query)
    {
        return $query->where('rate_type', 'manual');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('effective_date', $date);
    }

    public function scopeForCurrencyPair($query, $fromCurrency, $toCurrency)
    {
        return $query->where('from_currency_id', $fromCurrency)
            ->where('to_currency_id', $toCurrency);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    // Helper methods
    public function getInverseRateAttribute(): float
    {
        return $this->rate > 0 ? 1 / $this->rate : 0;
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->fromCurrency->code}/{$this->toCurrency->code} = {$this->rate}";
    }

    public function isForCurrencyPair($fromCurrency, $toCurrency): bool
    {
        return $this->from_currency_id == $fromCurrency && $this->to_currency_id == $toCurrency;
    }

    public function isValidForDate($date): bool
    {
        return $this->effective_date <= $date;
    }

    // Static methods
    public static function getRateForDate($fromCurrency, $toCurrency, $date, $rateType = 'auto')
    {
        $query = static::forCurrencyPair($fromCurrency, $toCurrency)
            ->where('effective_date', '<=', $date)
            ->latest();

        if ($rateType !== 'auto') {
            $query->where('rate_type', $rateType);
        }

        return $query->first();
    }

    public static function getLatestRate($fromCurrency, $toCurrency, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return static::getRateForDate($fromCurrency, $toCurrency, $date, 'auto');
    }

    public static function createManualRate($fromCurrency, $toCurrency, $rate, $effectiveDate, $createdBy = null)
    {
        return static::create([
            'from_currency_id' => $fromCurrency,
            'to_currency_id' => $toCurrency,
            'rate' => $rate,
            'effective_date' => $effectiveDate,
            'rate_type' => 'manual',
            'source' => 'manual',
            'created_by' => $createdBy ?: auth()->id(),
        ]);
    }

    public static function createDailyRates($date, $rates, $createdBy = null)
    {
        $createdRates = [];

        foreach ($rates as $rateData) {
            $createdRates[] = static::create([
                'from_currency_id' => $rateData['from_currency_id'],
                'to_currency_id' => $rateData['to_currency_id'],
                'rate' => $rateData['rate'],
                'effective_date' => $date,
                'rate_type' => 'daily',
                'source' => 'manual', // Can be changed to 'api' when implementing API integration
                'created_by' => $createdBy ?: auth()->id(),
            ]);
        }

        return $createdRates;
    }

    public static function validateRate($fromCurrency, $toCurrency, $rate, $date, $tolerance = null)
    {
        // Get previous rate for comparison
        $previousRate = static::getLatestRate($fromCurrency, $toCurrency, $date);

        if (!$previousRate) {
            return true; // No previous rate to compare against
        }

        // Get tolerance from ERP parameters if not provided
        if ($tolerance === null) {
            $toleranceParam = \App\Models\ErpParameter::where('parameter_key', 'exchange_rate_tolerance')->first();
            $tolerance = $toleranceParam ? (float) $toleranceParam->parameter_value : 10; // Default 10%
        }

        $percentageChange = abs(($rate - $previousRate->rate) / $previousRate->rate) * 100;

        return $percentageChange <= $tolerance;
    }
}
