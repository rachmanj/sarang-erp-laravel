<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'allocation_code',
        'name',
        'description',
        'cost_category_id',
        'allocation_method_id',
        'allocation_rate',
        'allocation_base',
        'is_active',
    ];

    protected $casts = [
        'allocation_rate' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function costCategory(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class);
    }

    public function allocationMethod(): BelongsTo
    {
        return $this->belongsTo(CostAllocationMethod::class);
    }

    public function customerCostAllocations(): HasMany
    {
        return $this->hasMany(CustomerCostAllocation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('cost_category_id', $categoryId);
    }

    public function calculateAllocation($baseValue, $totalBaseValue = null)
    {
        switch ($this->allocation_base) {
            case 'percentage':
                return $baseValue * ($this->allocation_rate / 100);
            case 'fixed_amount':
                return $this->allocation_rate;
            case 'proportional':
                if ($totalBaseValue && $totalBaseValue > 0) {
                    return ($baseValue / $totalBaseValue) * $this->allocation_rate;
                }
                return 0;
            default:
                return 0;
        }
    }
}
