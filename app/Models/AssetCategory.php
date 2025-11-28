<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'life_months_default',
        'method_default',
        'salvage_value_policy',
        'non_depreciable',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'gain_on_disposal_account_id',
        'loss_on_disposal_account_id',
        'is_active',
    ];

    protected $casts = [
        'salvage_value_policy' => 'decimal:2',
        'non_depreciable' => 'boolean',
        'is_active' => 'boolean',
        'life_months_default' => 'integer',
    ];

    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'asset_category';

    // Relationships
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function gainOnDisposalAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gain_on_disposal_account_id');
    }

    public function lossOnDisposalAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'loss_on_disposal_account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDepreciable($query)
    {
        return $query->where('non_depreciable', false);
    }

    // Helper methods
    public function getDefaultDepreciationRateAttribute(): float
    {
        if ($this->non_depreciable || !$this->life_months_default) {
            return 0;
        }

        return 1 / $this->life_months_default;
    }

    public function canBeDeleted(): bool
    {
        return $this->assets()->count() === 0;
    }
}
