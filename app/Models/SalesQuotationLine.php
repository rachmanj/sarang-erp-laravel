<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationLine extends Model
{
    protected $fillable = [
        'quotation_id',
        'account_id',
        'inventory_item_id',
        'item_code',
        'item_name',
        'unit_of_measure',
        'order_unit_id',
        'description',
        'qty',
        'base_quantity',
        'unit_conversion_factor',
        'unit_price',
        'unit_price_foreign',
        'amount',
        'amount_foreign',
        'freight_cost',
        'handling_cost',
        'discount_amount',
        'discount_percentage',
        'net_amount',
        'tax_code_id',
        'vat_rate',
        'wtax_rate',
        'notes',
        'line_order'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'base_quantity' => 'decimal:2',
        'unit_conversion_factor' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_price_foreign' => 'decimal:2',
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'wtax_rate' => 'decimal:2',
    ];

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
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

    public function orderUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'order_unit_id');
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

    // Unit conversion helper methods
    public function calculateBaseQuantity(): float
    {
        if ($this->unit_conversion_factor && $this->unit_conversion_factor > 0) {
            return $this->qty * $this->unit_conversion_factor;
        }
        return $this->qty;
    }

    public function getBaseUnitPrice(): float
    {
        if ($this->unit_conversion_factor && $this->unit_conversion_factor > 0) {
            return $this->unit_price / $this->unit_conversion_factor;
        }
        return $this->unit_price;
    }

    public function updateBaseQuantity(): void
    {
        $this->base_quantity = $this->calculateBaseQuantity();
    }

    public function getUnitDisplayName(): string
    {
        return $this->orderUnit ? $this->orderUnit->display_name : $this->unit_of_measure;
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
    public function calculateDiscount($discountPercentage)
    {
        $this->discount_percentage = $discountPercentage;
        $this->discount_amount = ($this->amount * $discountPercentage) / 100;
        $this->net_amount = $this->amount - $this->discount_amount;
        $this->save();
    }
}
