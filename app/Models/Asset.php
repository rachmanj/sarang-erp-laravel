<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Asset extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'serial_number',
        'category_id',
        'acquisition_cost',
        'salvage_value',
        'current_book_value',
        'accumulated_depreciation',
        'method',
        'life_months',
        'placed_in_service_date',
        'status',
        'disposal_date',
        'project_id',
        'department_id',
        'vendor_id',
        'purchase_invoice_id',
    ];

    protected $casts = [
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'life_months' => 'integer',
        'placed_in_service_date' => 'date',
        'disposal_date' => 'date',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(AssetDepreciationEntry::class, 'asset_id');
    }


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function disposal(): HasMany
    {
        return $this->hasMany(AssetDisposal::class, 'asset_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class, 'asset_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDepreciable($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('non_depreciable', false);
        });
    }

    // Helper methods
    public function getDepreciationRateAttribute(): float
    {
        if ($this->category && $this->category->non_depreciable) {
            return 0;
        }

        return 1 / $this->life_months;
    }

    public function getDepreciableCostAttribute(): float
    {
        return $this->acquisition_cost - $this->salvage_value;
    }

    public function getRemainingLifeMonthsAttribute(): int
    {
        if ($this->category && $this->category->non_depreciable) {
            return 0;
        }

        $serviceDate = Carbon::parse($this->placed_in_service_date);
        $monthsInService = $serviceDate->diffInMonths(now());

        return max(0, $this->life_months - $monthsInService);
    }

    public function calculateMonthlyDepreciation(): float
    {
        if ($this->category && $this->category->non_depreciable) {
            return 0;
        }

        return $this->depreciable_cost / $this->life_months;
    }

    public function getTotalDepreciationToDate(): float
    {
        return $this->depreciationEntries()->sum('amount');
    }

    public function isDepreciated(): bool
    {
        return $this->accumulated_depreciation >= $this->depreciable_cost;
    }

    public function canBeDeleted(): bool
    {
        return $this->depreciationEntries()->count() === 0;
    }

    public function canBeDisposed(): bool
    {
        return $this->status === 'active' && $this->disposal()->where('status', '!=', 'reversed')->count() === 0;
    }

    public function isDisposed(): bool
    {
        return $this->disposal()->where('status', 'posted')->exists();
    }

    // Boot method to set current_book_value
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($asset) {
            $asset->current_book_value = $asset->acquisition_cost - $asset->accumulated_depreciation;
        });
    }
}
