<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCostSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'period_start',
        'period_end',
        'total_purchase_cost',
        'total_freight_cost',
        'total_handling_cost',
        'total_overhead_cost',
        'total_cost',
        'average_unit_cost',
        'total_quantity',
        'valuation_method',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_purchase_cost' => 'decimal:4',
        'total_freight_cost' => 'decimal:4',
        'total_handling_cost' => 'decimal:4',
        'total_overhead_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'average_unit_cost' => 'decimal:4',
        'total_quantity' => 'decimal:4',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeByValuationMethod($query, $method)
    {
        return $query->where('valuation_method', $method);
    }

    public function getCostPerUnitAttribute()
    {
        return $this->total_quantity > 0 ? $this->total_cost / $this->total_quantity : 0;
    }

    public function getDirectCostAttribute()
    {
        return $this->total_purchase_cost;
    }

    public function getIndirectCostAttribute()
    {
        return $this->total_freight_cost + $this->total_handling_cost;
    }

    public function getOverheadCostAttribute()
    {
        return $this->total_overhead_cost;
    }

    public function getCostBreakdownAttribute()
    {
        return [
            'direct' => $this->direct_cost,
            'indirect' => $this->indirect_cost,
            'overhead' => $this->overhead_cost,
            'total' => $this->total_cost,
        ];
    }

    public static function calculateForPeriod($itemId, $startDate, $endDate, $valuationMethod = 'fifo')
    {
        $costHistories = CostHistory::where('inventory_item_id', $itemId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $summary = new self([
            'inventory_item_id' => $itemId,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'valuation_method' => $valuationMethod,
        ]);

        foreach ($costHistories as $cost) {
            switch ($cost->costCategory->type) {
                case 'direct':
                    $summary->total_purchase_cost += $cost->total_cost;
                    break;
                case 'indirect':
                    if ($cost->costCategory->code === 'FRT') {
                        $summary->total_freight_cost += $cost->total_cost;
                    } elseif ($cost->costCategory->code === 'HDL') {
                        $summary->total_handling_cost += $cost->total_cost;
                    }
                    break;
                case 'overhead':
                    $summary->total_overhead_cost += $cost->total_cost;
                    break;
            }
        }

        $summary->total_cost = $summary->total_purchase_cost +
            $summary->total_freight_cost +
            $summary->total_handling_cost +
            $summary->total_overhead_cost;

        $summary->total_quantity = $costHistories->sum('quantity');
        $summary->average_unit_cost = $summary->total_quantity > 0 ?
            $summary->total_cost / $summary->total_quantity : 0;

        return $summary;
    }
}
