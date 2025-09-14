<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'unit_of_measure',
        'purchase_price',
        'selling_price',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'valuation_method',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }

    public function valuations(): HasMany
    {
        return $this->hasMany(InventoryValuation::class, 'item_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('current_stock <= reorder_point');
    }

    // Accessors
    public function getCurrentStockAttribute()
    {
        $lastTransaction = $this->transactions()
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastTransaction) {
            return 0;
        }

        $totalIn = $this->transactions()
            ->where('transaction_type', 'purchase')
            ->sum('quantity');

        $totalOut = $this->transactions()
            ->whereIn('transaction_type', ['sale', 'adjustment'])
            ->sum('quantity');

        return $totalIn - $totalOut;
    }

    public function getCurrentValueAttribute()
    {
        $lastValuation = $this->valuations()
            ->orderBy('valuation_date', 'desc')
            ->first();

        return $lastValuation ? $lastValuation->total_value : 0;
    }
}
