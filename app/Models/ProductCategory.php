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

    // Account Inheritance Methods
    // These methods return the effective account, inheriting from parent if not set

    /**
     * Get effective inventory account, inheriting from parent if not set
     */
    public function getEffectiveInventoryAccount()
    {
        if ($this->inventory_account_id) {
            return $this->inventoryAccount;
        }

        if ($this->parent) {
            return $this->parent->getEffectiveInventoryAccount();
        }

        return null;
    }

    /**
     * Get effective COGS account, inheriting from parent if not set
     */
    public function getEffectiveCogsAccount()
    {
        if ($this->cogs_account_id) {
            return $this->cogsAccount;
        }

        if ($this->parent) {
            return $this->parent->getEffectiveCogsAccount();
        }

        return null;
    }

    /**
     * Get effective sales account, inheriting from parent if not set
     */
    public function getEffectiveSalesAccount()
    {
        if ($this->sales_account_id) {
            return $this->salesAccount;
        }

        if ($this->parent) {
            return $this->parent->getEffectiveSalesAccount();
        }

        return null;
    }

    /**
     * Get effective account by type (inventory, cogs, sales)
     */
    public function getEffectiveAccountByType($type)
    {
        switch ($type) {
            case 'inventory':
                return $this->getEffectiveInventoryAccount();
            case 'cogs':
                return $this->getEffectiveCogsAccount();
            case 'sales':
                return $this->getEffectiveSalesAccount();
            default:
                return null;
        }
    }

    /**
     * Check if this category has its own account set (not inherited)
     */
    public function hasOwnInventoryAccount(): bool
    {
        return !is_null($this->inventory_account_id);
    }

    public function hasOwnCogsAccount(): bool
    {
        return !is_null($this->cogs_account_id);
    }

    public function hasOwnSalesAccount(): bool
    {
        return !is_null($this->sales_account_id);
    }

    /**
     * Get account source information (own or inherited)
     */
    public function getAccountSource($type): array
    {
        $hasOwn = false;
        $account = null;
        $sourceCategory = null;

        switch ($type) {
            case 'inventory':
                $hasOwn = $this->hasOwnInventoryAccount();
                $account = $this->getEffectiveInventoryAccount();
                break;
            case 'cogs':
                $hasOwn = $this->hasOwnCogsAccount();
                $account = $this->getEffectiveCogsAccount();
                break;
            case 'sales':
                $hasOwn = $this->hasOwnSalesAccount();
                $account = $this->getEffectiveSalesAccount();
                break;
        }

        if (!$hasOwn && $this->parent) {
            $sourceCategory = $this->parent;
        }

        return [
            'account' => $account,
            'is_inherited' => !$hasOwn,
            'source_category' => $sourceCategory,
        ];
    }
}
