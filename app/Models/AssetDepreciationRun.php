<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDepreciationRun extends Model
{
    protected $fillable = [
        'period',
        'status',
        'total_depreciation',
        'asset_count',
        'journal_id',
        'created_by',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'total_depreciation' => 'decimal:2',
        'asset_count' => 'integer',
        'posted_at' => 'datetime',
    ];

    // Relationships
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(AssetDepreciationEntry::class, 'period', 'period');
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

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
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

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function canBePosted(): bool
    {
        return $this->status === 'draft' && $this->total_depreciation > 0;
    }

    public function canBeReversed(): bool
    {
        return $this->status === 'posted' && !is_null($this->journal_id);
    }

    public function getPeriodYearAttribute(): int
    {
        return (int) substr($this->period, 0, 4);
    }

    public function getPeriodMonthAttribute(): int
    {
        return (int) substr($this->period, 5, 2);
    }

    public function getPeriodDisplayAttribute(): string
    {
        $year = $this->period_year;
        $month = $this->period_month;
        $monthName = date('F', mktime(0, 0, 0, $month, 1));

        return "{$monthName} {$year}";
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => '<span class="badge badge-warning">Draft</span>',
            'posted' => '<span class="badge badge-success">Posted</span>',
            'reversed' => '<span class="badge badge-danger">Reversed</span>',
            default => '<span class="badge badge-secondary">Unknown</span>',
        };
    }
}
