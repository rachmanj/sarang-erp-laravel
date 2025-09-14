<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPricingTier extends Model
{
    protected $fillable = [
        'customer_id',
        'tier_name',
        'discount_percentage',
        'min_order_amount',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOrderAmount($query, $amount)
    {
        return $query->where('min_order_amount', '<=', $amount);
    }

    // Methods
    public function calculateDiscount($orderAmount)
    {
        if ($orderAmount < $this->min_order_amount) {
            return 0;
        }

        return ($orderAmount * $this->discount_percentage) / 100;
    }

    public function getDiscountAmount($orderAmount)
    {
        return $this->calculateDiscount($orderAmount);
    }

    public function getNetAmount($orderAmount)
    {
        return $orderAmount - $this->calculateDiscount($orderAmount);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function isEligible($orderAmount)
    {
        return $this->is_active && $orderAmount >= $this->min_order_amount;
    }
}
