<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryWarehouseStock extends Model
{
    protected $table = 'inventory_warehouse_stock';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'quantity_on_hand',
        'reserved_quantity',
        'available_quantity',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_point' => 'integer',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_on_hand <= reorder_point');
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    // Helper methods
    public function updateAvailableQuantity()
    {
        $this->available_quantity = $this->quantity_on_hand - $this->reserved_quantity;
        $this->save();
    }

    public function isLowStock()
    {
        return $this->quantity_on_hand <= $this->reorder_point;
    }
}
