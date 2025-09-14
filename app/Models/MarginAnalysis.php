<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarginAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_type',
        'inventory_item_id',
        'customer_id',
        'supplier_id',
        'analysis_date',
        'revenue',
        'cost_of_goods_sold',
        'gross_margin',
        'gross_margin_percentage',
        'operating_expenses',
        'net_margin',
        'net_margin_percentage',
        'quantity_sold',
        'average_selling_price',
        'average_cost',
    ];

    protected $casts = [
        'analysis_date' => 'date',
        'revenue' => 'decimal:4',
        'cost_of_goods_sold' => 'decimal:4',
        'gross_margin' => 'decimal:4',
        'gross_margin_percentage' => 'decimal:4',
        'operating_expenses' => 'decimal:4',
        'net_margin' => 'decimal:4',
        'net_margin_percentage' => 'decimal:4',
        'quantity_sold' => 'decimal:4',
        'average_selling_price' => 'decimal:4',
        'average_cost' => 'decimal:4',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('analysis_type', $type);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('analysis_date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('analysis_date', [$startDate, $endDate]);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeHighMargin($query, $threshold = 30)
    {
        return $query->where('gross_margin_percentage', '>=', $threshold);
    }

    public function scopeLowMargin($query, $threshold = 10)
    {
        return $query->where('gross_margin_percentage', '<=', $threshold);
    }

    public function getContributionMarginAttribute()
    {
        return $this->revenue - $this->cost_of_goods_sold;
    }

    public function getContributionMarginPercentageAttribute()
    {
        return $this->revenue > 0 ? ($this->contribution_margin / $this->revenue) * 100 : 0;
    }

    public function getProfitabilityScoreAttribute()
    {
        $score = 0;

        // Gross margin score (0-40 points)
        if ($this->gross_margin_percentage >= 40) $score += 40;
        elseif ($this->gross_margin_percentage >= 30) $score += 30;
        elseif ($this->gross_margin_percentage >= 20) $score += 20;
        elseif ($this->gross_margin_percentage >= 10) $score += 10;

        // Net margin score (0-30 points)
        if ($this->net_margin_percentage >= 20) $score += 30;
        elseif ($this->net_margin_percentage >= 15) $score += 25;
        elseif ($this->net_margin_percentage >= 10) $score += 20;
        elseif ($this->net_margin_percentage >= 5) $score += 15;
        elseif ($this->net_margin_percentage >= 0) $score += 10;

        // Volume score (0-30 points)
        if ($this->quantity_sold >= 1000) $score += 30;
        elseif ($this->quantity_sold >= 500) $score += 25;
        elseif ($this->quantity_sold >= 100) $score += 20;
        elseif ($this->quantity_sold >= 50) $score += 15;
        elseif ($this->quantity_sold >= 10) $score += 10;

        return min($score, 100);
    }

    public function getProfitabilityRatingAttribute()
    {
        $score = $this->profitability_score;

        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Very Good';
        if ($score >= 70) return 'Good';
        if ($score >= 60) return 'Average';
        if ($score >= 50) return 'Below Average';
        return 'Poor';
    }

    public static function calculateForProduct($itemId, $startDate, $endDate)
    {
        $salesOrders = SalesOrder::whereHas('lines', function ($query) use ($itemId) {
            $query->where('inventory_item_id', $itemId);
        })->whereBetween('order_date', [$startDate, $endDate])->get();

        $totalRevenue = 0;
        $totalCOGS = 0;
        $totalQuantity = 0;
        $totalOperatingExpenses = 0;

        foreach ($salesOrders as $order) {
            $lines = $order->lines()->where('inventory_item_id', $itemId)->get();

            foreach ($lines as $line) {
                $totalRevenue += $line->total_amount;
                $totalQuantity += $line->quantity;

                // Calculate COGS from cost history
                $costSummary = ProductCostSummary::calculateForPeriod(
                    $itemId,
                    $startDate,
                    $endDate
                );
                $totalCOGS += $line->quantity * $costSummary->average_unit_cost;
            }

            // Add operating expenses (simplified)
            $totalOperatingExpenses += $order->total_amount * 0.1; // 10% operating expense ratio
        }

        $grossMargin = $totalRevenue - $totalCOGS;
        $grossMarginPercentage = $totalRevenue > 0 ? ($grossMargin / $totalRevenue) * 100 : 0;

        $netMargin = $grossMargin - $totalOperatingExpenses;
        $netMarginPercentage = $totalRevenue > 0 ? ($netMargin / $totalRevenue) * 100 : 0;

        return new self([
            'analysis_type' => 'product',
            'inventory_item_id' => $itemId,
            'analysis_date' => $endDate,
            'revenue' => $totalRevenue,
            'cost_of_goods_sold' => $totalCOGS,
            'gross_margin' => $grossMargin,
            'gross_margin_percentage' => $grossMarginPercentage,
            'operating_expenses' => $totalOperatingExpenses,
            'net_margin' => $netMargin,
            'net_margin_percentage' => $netMarginPercentage,
            'quantity_sold' => $totalQuantity,
            'average_selling_price' => $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0,
            'average_cost' => $totalQuantity > 0 ? $totalCOGS / $totalQuantity : 0,
        ]);
    }
}
