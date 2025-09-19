<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'transaction_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // Scopes
    public function scopePurchase($query)
    {
        return $query->where('transaction_type', 'purchase');
    }

    public function scopeSale($query)
    {
        return $query->where('transaction_type', 'sale');
    }

    public function scopeAdjustment($query)
    {
        return $query->where('transaction_type', 'adjustment');
    }

    public function scopeTransfer($query)
    {
        return $query->where('transaction_type', 'transfer');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
