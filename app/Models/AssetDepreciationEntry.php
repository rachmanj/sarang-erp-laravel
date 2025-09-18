<?php

namespace App\Models;

use App\Models\Accounting\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciationEntry extends Model
{
    protected $fillable = [
        'asset_id',
        'period',
        'amount',
        'book',
        'journal_id',
        'project_id',
        'department_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Scopes
    public function scopeFinancial($query)
    {
        return $query->where('book', 'financial');
    }

    public function scopeTax($query)
    {
        return $query->where('book', 'tax');
    }

    public function scopePosted($query)
    {
        return $query->whereNotNull('journal_id');
    }

    public function scopeDraft($query)
    {
        return $query->whereNull('journal_id');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    // Helper methods
    public function isPosted(): bool
    {
        return !is_null($this->journal_id);
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
}
