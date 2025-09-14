<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'order_id',
        'account_id',
        'inventory_item_id',
        'item_code',
        'item_name',
        'unit_of_measure',
        'description',
        'qty',
        'delivered_qty',
        'pending_qty',
        'unit_price',
        'amount',
        'freight_cost',
        'handling_cost',
        'total_cost',
        'discount_amount',
        'discount_percentage',
        'net_amount',
        'tax_code_id',
        'notes',
        'status'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'pending_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class, 'account_id');
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\TaxCode::class, 'tax_code_id');
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->amount - $this->discount_amount;
    }

    public function getTotalCostAttribute()
    {
        return $this->amount + $this->freight_cost + $this->handling_cost;
    }

    public function getDeliveryProgressAttribute()
    {
        if ($this->qty == 0) {
            return 0;
        }

        return round(($this->delivered_qty / $this->qty) * 100);
    }

    public function getIsFullyDeliveredAttribute()
    {
        return $this->delivered_qty >= $this->qty;
    }

    public function getIsPartiallyDeliveredAttribute()
    {
        return $this->delivered_qty > 0 && $this->delivered_qty < $this->qty;
    }

    public function getGrossProfitAttribute()
    {
        return $this->net_amount - $this->total_cost;
    }

    public function getGrossProfitMarginAttribute()
    {
        if ($this->net_amount == 0) {
            return 0;
        }

        return round(($this->gross_profit / $this->net_amount) * 100, 2);
    }

    // Methods
    public function updateDeliveredQuantity($quantity)
    {
        $this->delivered_qty = $quantity;
        $this->pending_qty = $this->qty - $quantity;

        if ($this->is_fully_delivered) {
            $this->status = 'delivered';
        } elseif ($this->is_partially_delivered) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }

    public function canDeliverQuantity($quantity)
    {
        return $quantity <= $this->pending_qty;
    }

    public function calculateDiscount($discountPercentage)
    {
        $this->discount_percentage = $discountPercentage;
        $this->discount_amount = ($this->amount * $discountPercentage) / 100;
        $this->net_amount = $this->amount - $this->discount_amount;
        $this->save();
    }
}
