<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierCostAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'analysis_date',
        'total_purchase_value',
        'total_freight_cost',
        'total_handling_cost',
        'total_cost',
        'average_cost_per_unit',
        'delivery_performance_score',
        'quality_score',
        'cost_efficiency_score',
        'overall_score',
        'total_orders',
        'on_time_deliveries',
        'late_deliveries',
    ];

    protected $casts = [
        'analysis_date' => 'date',
        'total_purchase_value' => 'decimal:4',
        'total_freight_cost' => 'decimal:4',
        'total_handling_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'average_cost_per_unit' => 'decimal:4',
        'delivery_performance_score' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'cost_efficiency_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'supplier_id');
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('analysis_date', [$startDate, $endDate]);
    }

    public function scopeTopPerformers($query, $limit = 10)
    {
        return $query->orderBy('overall_score', 'desc')->limit($limit);
    }

    public function scopeCostEfficient($query, $threshold = 80)
    {
        return $query->where('cost_efficiency_score', '>=', $threshold);
    }

    public function getOnTimeDeliveryRateAttribute()
    {
        return $this->total_orders > 0 ? ($this->on_time_deliveries / $this->total_orders) * 100 : 0;
    }

    public function getLateDeliveryRateAttribute()
    {
        return $this->total_orders > 0 ? ($this->late_deliveries / $this->total_orders) * 100 : 0;
    }

    public function getCostBreakdownAttribute()
    {
        return [
            'purchase_value' => $this->total_purchase_value,
            'freight_cost' => $this->total_freight_cost,
            'handling_cost' => $this->total_handling_cost,
            'total_cost' => $this->total_cost,
            'freight_percentage' => $this->total_purchase_value > 0 ? ($this->total_freight_cost / $this->total_purchase_value) * 100 : 0,
            'handling_percentage' => $this->total_purchase_value > 0 ? ($this->total_handling_cost / $this->total_purchase_value) * 100 : 0,
        ];
    }

    public function getPerformanceGradeAttribute()
    {
        if ($this->overall_score >= 90) return 'A+';
        if ($this->overall_score >= 80) return 'A';
        if ($this->overall_score >= 70) return 'B+';
        if ($this->overall_score >= 60) return 'B';
        if ($this->overall_score >= 50) return 'C+';
        if ($this->overall_score >= 40) return 'C';
        return 'D';
    }

    public function getPerformanceStatusAttribute()
    {
        if ($this->overall_score >= 80) return 'excellent';
        if ($this->overall_score >= 60) return 'good';
        if ($this->overall_score >= 40) return 'fair';
        return 'poor';
    }

    public static function calculateForSupplier($supplierId, $startDate, $endDate)
    {
        $purchaseOrders = PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->with('lines')
            ->get();

        $totalPurchaseValue = $purchaseOrders->sum('total_amount');
        $totalOrders = $purchaseOrders->count();

        // Calculate freight and handling costs from cost history
        $freightCosts = CostHistory::whereHas('purchaseOrder', function ($query) use ($supplierId) {
            $query->where('vendor_id', $supplierId);
        })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('costCategory', function ($query) {
                $query->where('code', 'FRT');
            })
            ->sum('total_cost');

        $handlingCosts = CostHistory::whereHas('purchaseOrder', function ($query) use ($supplierId) {
            $query->where('vendor_id', $supplierId);
        })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('costCategory', function ($query) {
                $query->where('code', 'HDL');
            })
            ->sum('total_cost');

        $totalCost = $totalPurchaseValue + $freightCosts + $handlingCosts;
        $averageCostPerUnit = $totalOrders > 0 ? $totalCost / $totalOrders : 0;

        // Calculate performance scores
        $deliveryPerformanceScore = self::calculateDeliveryScore($supplierId, $startDate, $endDate);
        $qualityScore = self::calculateQualityScore($supplierId, $startDate, $endDate);
        $costEfficiencyScore = self::calculateCostEfficiencyScore($supplierId, $startDate, $endDate);

        $overallScore = ($deliveryPerformanceScore + $qualityScore + $costEfficiencyScore) / 3;

        return new self([
            'supplier_id' => $supplierId,
            'analysis_date' => $endDate,
            'total_purchase_value' => $totalPurchaseValue,
            'total_freight_cost' => $freightCosts,
            'total_handling_cost' => $handlingCosts,
            'total_cost' => $totalCost,
            'average_cost_per_unit' => $averageCostPerUnit,
            'delivery_performance_score' => $deliveryPerformanceScore,
            'quality_score' => $qualityScore,
            'cost_efficiency_score' => $costEfficiencyScore,
            'overall_score' => $overallScore,
            'total_orders' => $totalOrders,
            'on_time_deliveries' => self::countOnTimeDeliveries($supplierId, $startDate, $endDate),
            'late_deliveries' => self::countLateDeliveries($supplierId, $startDate, $endDate),
        ]);
    }

    protected static function calculateDeliveryScore($supplierId, $startDate, $endDate)
    {
        $totalOrders = PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        $onTimeOrders = self::countOnTimeDeliveries($supplierId, $startDate, $endDate);

        return $totalOrders > 0 ? ($onTimeOrders / $totalOrders) * 100 : 0;
    }

    protected static function calculateQualityScore($supplierId, $startDate, $endDate)
    {
        // This would typically integrate with quality management system
        // For now, return a default score based on return rates
        $totalOrders = PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        // Simplified quality calculation - in real system, this would be more complex
        return $totalOrders > 0 ? 85 : 0; // Default 85% quality score
    }

    protected static function calculateCostEfficiencyScore($supplierId, $startDate, $endDate)
    {
        // Calculate cost efficiency based on price competitiveness
        $supplierAvgPrice = PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->avg('total_amount');

        $marketAvgPrice = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->avg('total_amount');

        if ($marketAvgPrice > 0) {
            $efficiencyRatio = $supplierAvgPrice / $marketAvgPrice;
            return max(0, min(100, (1 - $efficiencyRatio + 1) * 50)); // Convert to 0-100 scale
        }

        return 50; // Default score
    }

    protected static function countOnTimeDeliveries($supplierId, $startDate, $endDate)
    {
        return PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereColumn('delivery_date', '<=', 'expected_delivery_date')
            ->count();
    }

    protected static function countLateDeliveries($supplierId, $startDate, $endDate)
    {
        return PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereColumn('delivery_date', '>', 'expected_delivery_date')
            ->count();
    }
}
