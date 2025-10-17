<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'order_id',
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
        'received_qty',
        'pending_qty',
        'unit_price',
        'unit_price_foreign',
        'amount',
        'amount_foreign',
        'freight_cost',
        'handling_cost',
        'total_cost',
        'tax_code_id',
        'vat_rate',
        'wtax_rate',
        'notes',
        'status'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'base_quantity' => 'decimal:2',
        'unit_conversion_factor' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'pending_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_price_foreign' => 'decimal:2',
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'wtax_rate' => 'decimal:2',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
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
    public function getTotalCostAttribute()
    {
        return $this->amount + $this->freight_cost + $this->handling_cost;
    }

    public function getReceiptProgressAttribute()
    {
        if ($this->qty == 0) {
            return 0;
        }

        return round(($this->received_qty / $this->qty) * 100);
    }

    public function getIsFullyReceivedAttribute()
    {
        return $this->received_qty >= $this->qty;
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

    public function getIsPartiallyReceivedAttribute()
    {
        return $this->received_qty > 0 && $this->received_qty < $this->qty;
    }

    // Methods
    public function updateReceivedQuantity($quantity)
    {
        $this->received_qty = $quantity;
        $this->pending_qty = $this->qty - $quantity;

        if ($this->is_fully_received) {
            $this->status = 'received';
        } elseif ($this->is_partially_received) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }

    public function canReceiveQuantity($quantity)
    {
        return $quantity <= $this->pending_qty;
    }
}
