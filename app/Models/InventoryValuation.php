<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryValuation extends Model
{
    protected $fillable = [
        'item_id',
        'valuation_date',
        'quantity_on_hand',
        'unit_cost',
        'total_value',
        'valuation_method',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'quantity_on_hand' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('valuation_date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('valuation_date', [$startDate, $endDate]);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('valuation_method', $method);
    }
}
