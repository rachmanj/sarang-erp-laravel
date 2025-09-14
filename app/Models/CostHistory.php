<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CostHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'transaction_type',
        'inventory_item_id',
        'purchase_order_id',
        'sales_order_id',
        'cost_category_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'allocated_cost',
        'transaction_date',
        'notes',
        'reference_number',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'allocated_cost' => 'decimal:4',
        'transaction_date' => 'date',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function costCategory(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeAllocated($query)
    {
        return $query->where('allocated_cost', '>', 0);
    }

    public function scopeUnallocated($query)
    {
        return $query->where('allocated_cost', 0);
    }

    public function getUnallocatedAmountAttribute()
    {
        return $this->total_cost - $this->allocated_cost;
    }

    public function getIsFullyAllocatedAttribute()
    {
        return $this->allocated_cost >= $this->total_cost;
    }

    public static function generateTransactionCode($type)
    {
        $prefix = match ($type) {
            'purchase' => 'PUR',
            'freight' => 'FRT',
            'handling' => 'HDL',
            'overhead' => 'OVH',
            'adjustment' => 'ADJ',
            default => 'COS'
        };

        return $prefix . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
