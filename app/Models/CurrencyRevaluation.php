<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurrencyRevaluation extends Model
{
    protected $fillable = [
        'revaluation_no',
        'revaluation_date',
        'currency_id',
        'reference_rate_id',
        'total_unrealized_gain',
        'total_unrealized_loss',
        'journal_id',
        'status',
        'revalued_by',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'total_unrealized_gain' => 'decimal:2',
        'total_unrealized_loss' => 'decimal:2',
        'status' => 'string',
        'posted_at' => 'datetime',
    ];

    // Relationships
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function referenceRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class, 'reference_rate_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Journal::class);
    }

    public function revaluedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revalued_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CurrencyRevaluationLine::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeForCurrency($query, $currencyId)
    {
        return $query->where('base_currency_id', $currencyId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('revaluation_date', $date);
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function canBePosted(): bool
    {
        return $this->isDraft() && $this->lines()->count() > 0;
    }

    public function canBeReversed(): bool
    {
        return $this->isPosted() && $this->journal_id !== null;
    }

    public function getNetGainLossAttribute(): float
    {
        return $this->total_unrealized_gain - $this->total_unrealized_loss;
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->revaluation_no} - {$this->currency->code} ({$this->revaluation_date->format('d/m/Y')})";
    }

    // Static methods
    public static function generateRevaluationNumber(): string
    {
        $currentDate = now();
        $prefix = 'REV-' . $currentDate->format('Ym') . '-';

        // Get the last sequence number for this month
        $lastRevaluation = static::where('revaluation_no', 'like', $prefix . '%')
            ->orderBy('revaluation_no', 'desc')
            ->first();

        if ($lastRevaluation) {
            $lastSequence = (int) substr($lastRevaluation->revaluation_no, -6);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
    }

    public static function createRevaluation($baseCurrencyId, $revaluationDate, $referenceRateId, $notes = null, $revaluedBy = null)
    {
        return static::create([
            'revaluation_no' => static::generateRevaluationNumber(),
            'revaluation_date' => $revaluationDate,
            'base_currency_id' => $baseCurrencyId,
            'reference_rate_id' => $referenceRateId,
            'description' => $notes,
            'status' => 'draft',
            'created_by' => $revaluedBy ?: auth()->id(),
            'notes' => $notes,
        ]);
    }
}
