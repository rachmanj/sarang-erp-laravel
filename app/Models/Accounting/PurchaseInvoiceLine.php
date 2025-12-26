<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceLine extends Model
{
    protected $table = 'purchase_invoice_lines';

    protected $fillable = [
        'invoice_id',
        'inventory_item_id',
        'warehouse_id',
        'account_id',
        'description',
        'qty',
        'order_unit_id',
        'base_quantity',
        'unit_conversion_factor',
        'unit_price',
        'amount',
        'tax_code_id',
        'project_id',
        'dept_id',
    ];

    protected $casts = [
        'qty' => 'float',
        'base_quantity' => 'float',
        'unit_conversion_factor' => 'float',
        'unit_price' => 'float',
        'amount' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoice_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryItem::class, 'inventory_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse::class, 'warehouse_id');
    }

    public function orderUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UnitOfMeasure::class, 'order_unit_id');
    }

    /**
     * Calculate base quantity from order quantity
     */
    public function calculateBaseQuantity(): float
    {
        if ($this->unit_conversion_factor && $this->unit_conversion_factor > 0) {
            return $this->qty * $this->unit_conversion_factor;
        }
        return $this->qty;
    }

    /**
     * Get base unit price from order unit price
     */
    public function getBaseUnitPrice(): float
    {
        if ($this->unit_conversion_factor && $this->unit_conversion_factor > 0) {
            return $this->unit_price / $this->unit_conversion_factor;
        }
        return $this->unit_price;
    }

    /**
     * Get unit display name
     */
    public function getUnitDisplayName(): string
    {
        return $this->orderUnit ? $this->orderUnit->display_name : ($this->inventoryItem->unit_of_measure ?? '');
    }
}
