<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPricingTier extends Model
{
    protected $fillable = [
        'customer_id',
        'tier_name',
        'min_order_amount',
        'discount_percentage',
        'is_active',
        'valid_from',
        'valid_to',
        'notes'
    ];

    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }
}
