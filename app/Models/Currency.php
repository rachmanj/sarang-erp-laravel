<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
        'is_base_currency',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
        'is_base_currency' => 'boolean',
    ];

    // Relationships
    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    public function revaluations(): HasMany
    {
        return $this->hasMany(CurrencyRevaluation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseCurrency($query)
    {
        return $query->where('is_base_currency', true);
    }

    // Helper methods
    public function isBase(): bool
    {
        return $this->is_base_currency;
    }

    public function getLatestRate($toCurrency, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return $this->exchangeRatesFrom()
            ->where('to_currency_id', $toCurrency)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    public function getFormattedSymbolAttribute(): string
    {
        return $this->symbol ?: $this->code;
    }

    // Static methods
    public static function getBaseCurrency(): ?self
    {
        return static::baseCurrency()->first();
    }

    public static function getActiveCurrencies(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderBy('code')->get();
    }

    public static function validateCurrencyCode(string $code): bool
    {
        return static::where('code', strtoupper($code))
            ->where('is_active', true)
            ->exists();
    }

    public static function getCurrencyByCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))
            ->where('is_active', true)
            ->first();
    }
}
