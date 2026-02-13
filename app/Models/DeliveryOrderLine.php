<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderLine extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'sales_order_line_id',
        'inventory_item_id',
        'account_id',
        'item_code',
        'item_name',
        'description',
        'ordered_qty',
        'reserved_qty',
        'picked_qty',
        'delivered_qty',
        'unit_price',
        'amount',
        'tax_code_id',
        'warehouse_location',
        'serial_numbers',
        'batch_codes',
        'packing_details',
        'status',
        'notes',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:2',
        'reserved_qty' => 'decimal:2',
        'picked_qty' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'serial_numbers' => 'array',
        'batch_codes' => 'array',
    ];

    // Relationships
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Accounting\Account::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\TaxCode::class, 'tax_code_id');
    }

    // Accessors
    public function getPendingQtyAttribute()
    {
        return $this->ordered_qty - $this->delivered_qty;
    }

    public function getPickingProgressAttribute()
    {
        if ($this->ordered_qty == 0) return 0;
        return round(($this->picked_qty / $this->ordered_qty) * 100);
    }

    public function getDeliveryProgressAttribute()
    {
        if ($this->ordered_qty == 0) return 0;
        return round(($this->delivered_qty / $this->ordered_qty) * 100);
    }

    public function getIsFullyPickedAttribute()
    {
        return $this->picked_qty >= $this->ordered_qty;
    }

    public function getIsFullyDeliveredAttribute()
    {
        return $this->delivered_qty >= $this->ordered_qty;
    }

    public function getIsPartiallyDeliveredAttribute()
    {
        return $this->delivered_qty > 0 && $this->delivered_qty < $this->ordered_qty;
    }

    // Methods
    public function updatePickedQuantity($quantity)
    {
        $this->picked_qty = $quantity;

        if ($this->is_fully_picked) {
            $this->status = 'picked';
        } elseif ($this->picked_qty > 0) {
            $this->status = 'partial_picked';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }

    public function updateDeliveredQuantity($quantity)
    {
        $this->delivered_qty = $quantity;

        if ($this->is_fully_delivered) {
            $this->status = 'delivered';
        } elseif ($this->is_partially_delivered) {
            $this->status = 'partial_delivered';
        } else {
            $this->status = 'ready';
        }

        $this->save();
    }

    public function canPickQuantity($quantity)
    {
        return $quantity <= $this->ordered_qty && $quantity <= ($this->ordered_qty - $this->picked_qty);
    }

    public function canDeliverQuantity($quantity)
    {
        return $quantity >= 0 && $quantity <= $this->picked_qty;
    }
}
