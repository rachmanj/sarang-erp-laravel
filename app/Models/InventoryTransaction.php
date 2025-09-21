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
        'transfer_status',
        'transfer_out_id',
        'transfer_in_id',
        'transfer_notes',
        'transit_date',
        'received_date',
        'transaction_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
        'transit_date' => 'datetime',
        'received_date' => 'datetime',
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

    // Transfer status scopes
    public function scopePendingTransfer($query)
    {
        return $query->where('transfer_status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('transfer_status', 'in_transit');
    }

    public function scopeReceived($query)
    {
        return $query->where('transfer_status', 'received');
    }

    public function scopeCompletedTransfer($query)
    {
        return $query->where('transfer_status', 'completed');
    }

    public function scopeCancelledTransfer($query)
    {
        return $query->where('transfer_status', 'cancelled');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
