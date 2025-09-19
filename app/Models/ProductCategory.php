<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
        'inventory_account_id',
        'cogs_account_id',
        'sales_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class, 'inventory_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class, 'cogs_account_id');
    }

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class, 'sales_account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
