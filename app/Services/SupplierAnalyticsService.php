<?php

namespace App\Services;

use App\Models\SupplierCostAnalysis;
use App\Models\SupplierPerformance;
use App\Models\SupplierComparison;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SupplierAnalyticsService
{
    /**
     * Generate comprehensive supplier analytics for a period
     */
    public function generateSupplierAnalytics($startDate, $endDate)
    {
        $suppliers = Vendor::with(['purchaseOrders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }])->get();

        $analytics = [];

        foreach ($suppliers as $supplier) {
            $analysis = SupplierCostAnalysis::calculateForSupplier(
                $supplier->id,
                $startDate,
                $endDate
            );

            $analytics[] = [
                'supplier' => $supplier,
                'analysis' => $analysis,
                'performance' => $this->getSupplierPerformance($supplier->id, $startDate, $endDate),
                'cost_breakdown' => $analysis->cost_breakdown,
                'recommendations' => $this->generateSupplierRecommendations($analysis),
            ];
        }

        return $analytics;
    }

    /**
     * Get supplier performance metrics
     */
    protected function getSupplierPerformance($supplierId, $startDate, $endDate)
    {
        $performance = SupplierPerformance::where('vendor_id', $supplierId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->first();

        if (!$performance) {
            // Create default performance if none exists
            $performance = new SupplierPerformance([
                'vendor_id' => $supplierId,
                'year' => Carbon::parse($endDate)->year,
                'month' => Carbon::parse($endDate)->month,
                'total_orders' => 0,
                'total_amount' => 0,
                'avg_delivery_days' => 0,
                'quality_rating' => 3.0,
                'price_rating' => 3.0,
                'service_rating' => 3.0,
                'overall_rating' => 3.0,
            ]);
        }

        return $performance;
    }

    /**
     * Generate supplier recommendations
     */
    protected function generateSupplierRecommendations($analysis)
    {
        $recommendations = [];

        if ($analysis->overall_score < 60) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Supplier performance is below acceptable standards. Consider reviewing contract terms or finding alternative suppliers.',
                'action' => 'Review supplier contract and performance metrics',
            ];
        }

        if ($analysis->on_time_delivery_rate < 80) {
            $recommendations[] = [
                'type' => 'delivery',
                'priority' => 'medium',
                'message' => 'Delivery performance is below 80%. Consider implementing delivery penalties or incentives.',
                'action' => 'Implement delivery performance clauses in contract',
            ];
        }

        if ($analysis->cost_efficiency_score < 70) {
            $recommendations[] = [
                'type' => 'cost',
                'priority' => 'medium',
                'message' => 'Cost efficiency is below market average. Negotiate better pricing or consider alternative suppliers.',
                'action' => 'Initiate price negotiation or supplier comparison',
            ];
        }

        if ($analysis->quality_score < 3.0) {
            $recommendations[] = [
                'type' => 'quality',
                'priority' => 'high',
                'message' => 'Quality rating is below acceptable standards. Implement quality control measures.',
                'action' => 'Implement quality inspection procedures',
            ];
        }

        return $recommendations;
    }

    /**
     * Compare suppliers for a specific product category
     */
    public function compareSuppliers($categoryId, $supplierIds = null)
    {
        if (!$supplierIds) {
            // Get top suppliers for this category
            $supplierIds = PurchaseOrder::whereHas('lines.inventoryItem', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
                ->groupBy('vendor_id')
                ->orderByRaw('SUM(total_amount) DESC')
                ->limit(3)
                ->pluck('vendor_id')
                ->toArray();
        }

        return SupplierComparison::createComparison($categoryId, $supplierIds);
    }

    /**
     * Get supplier ranking based on multiple criteria
     */
    public function getSupplierRanking($startDate, $endDate, $criteria = 'overall')
    {
        $analyses = SupplierCostAnalysis::whereBetween('analysis_date', [$startDate, $endDate])
            ->with('supplier')
            ->get();

        $ranking = $analyses->map(function ($analysis) use ($criteria) {
            $score = match ($criteria) {
                'cost' => $analysis->cost_efficiency_score,
                'delivery' => $analysis->delivery_performance_score,
                'quality' => $analysis->quality_score,
                default => $analysis->overall_score
            };

            return [
                'supplier' => $analysis->supplier,
                'score' => $score,
                'grade' => $analysis->performance_grade,
                'status' => $analysis->performance_status,
                'analysis' => $analysis,
            ];
        });

        return $ranking->sortByDesc('score')->values();
    }

    /**
     * Identify cost optimization opportunities
     */
    public function identifyCostOptimizationOpportunities($startDate, $endDate)
    {
        $opportunities = [];

        // High-cost suppliers
        $highCostSuppliers = SupplierCostAnalysis::whereBetween('analysis_date', [$startDate, $endDate])
            ->where('cost_efficiency_score', '<', 60)
            ->with('supplier')
            ->get();

        foreach ($highCostSuppliers as $analysis) {
            $opportunities[] = [
                'type' => 'high_cost_supplier',
                'supplier_id' => $analysis->supplier_id,
                'supplier_name' => $analysis->supplier->name ?? 'Unknown',
                'current_score' => $analysis->cost_efficiency_score,
                'potential_savings' => $analysis->total_cost * 0.1, // Assume 10% savings
                'recommendation' => 'Negotiate better pricing or consider alternative suppliers',
                'priority' => 'high',
            ];
        }

        // Poor delivery performance
        $poorDeliverySuppliers = SupplierCostAnalysis::whereBetween('analysis_date', [$startDate, $endDate])
            ->where('delivery_performance_score', '<', 70)
            ->with('supplier')
            ->get();

        foreach ($poorDeliverySuppliers as $analysis) {
            $opportunities[] = [
                'type' => 'poor_delivery',
                'supplier_id' => $analysis->supplier_id,
                'supplier_name' => $analysis->supplier->name ?? 'Unknown',
                'delivery_score' => $analysis->delivery_performance_score,
                'recommendation' => 'Implement delivery performance incentives or penalties',
                'priority' => 'medium',
            ];
        }

        // Quality issues
        $qualityIssuesSuppliers = SupplierCostAnalysis::whereBetween('analysis_date', [$startDate, $endDate])
            ->where('quality_score', '<', 3.0)
            ->with('supplier')
            ->get();

        foreach ($qualityIssuesSuppliers as $analysis) {
            $opportunities[] = [
                'type' => 'quality_issues',
                'supplier_id' => $analysis->supplier_id,
                'supplier_name' => $analysis->supplier->name ?? 'Unknown',
                'quality_score' => $analysis->quality_score,
                'recommendation' => 'Implement quality control measures and supplier training',
                'priority' => 'high',
            ];
        }

        return $opportunities;
    }

    /**
     * Generate supplier performance trends
     */
    public function getSupplierPerformanceTrends($supplierId, $months = 12)
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $analyses = SupplierCostAnalysis::where('supplier_id', $supplierId)
            ->whereBetween('analysis_date', [$startDate, $endDate])
            ->orderBy('analysis_date')
            ->get();

        return $analyses->map(function ($analysis) {
            return [
                'period' => $analysis->analysis_date->format('Y-m'),
                'overall_score' => $analysis->overall_score,
                'cost_efficiency' => $analysis->cost_efficiency_score,
                'delivery_performance' => $analysis->delivery_performance_score,
                'quality_score' => $analysis->quality_score,
                'total_cost' => $analysis->total_cost,
                'total_orders' => $analysis->total_orders,
            ];
        });
    }

    /**
     * Calculate supplier risk assessment
     */
    public function calculateSupplierRisk($supplierId, $startDate, $endDate)
    {
        $analysis = SupplierCostAnalysis::where('supplier_id', $supplierId)
            ->whereBetween('analysis_date', [$startDate, $endDate])
            ->latest()
            ->first();

        if (!$analysis) {
            return [
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'risk_factors' => ['No performance data available'],
            ];
        }

        $riskFactors = [];
        $riskScore = 0;

        // Performance risk
        if ($analysis->overall_score < 50) {
            $riskFactors[] = 'Poor overall performance';
            $riskScore += 30;
        } elseif ($analysis->overall_score < 70) {
            $riskFactors[] = 'Below average performance';
            $riskScore += 15;
        }

        // Delivery risk
        if ($analysis->on_time_delivery_rate < 60) {
            $riskFactors[] = 'Poor delivery performance';
            $riskScore += 25;
        } elseif ($analysis->on_time_delivery_rate < 80) {
            $riskFactors[] = 'Inconsistent delivery performance';
            $riskScore += 10;
        }

        // Quality risk
        if ($analysis->quality_score < 2.5) {
            $riskFactors[] = 'Quality issues identified';
            $riskScore += 20;
        } elseif ($analysis->quality_score < 3.5) {
            $riskFactors[] = 'Quality concerns';
            $riskScore += 10;
        }

        // Cost risk
        if ($analysis->cost_efficiency_score < 50) {
            $riskFactors[] = 'High cost inefficiency';
            $riskScore += 15;
        }

        // Dependency risk (high percentage of total purchases)
        $totalPurchases = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');
        $supplierPurchases = PurchaseOrder::where('vendor_id', $supplierId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->sum('total_amount');

        $dependencyPercentage = $totalPurchases > 0 ? ($supplierPurchases / $totalPurchases) * 100 : 0;

        if ($dependencyPercentage > 30) {
            $riskFactors[] = 'High supplier dependency (' . number_format($dependencyPercentage, 1) . '%)';
            $riskScore += 20;
        }

        $riskLevel = match (true) {
            $riskScore >= 70 => 'high',
            $riskScore >= 40 => 'medium',
            $riskScore >= 20 => 'low',
            default => 'minimal'
        };

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
            'dependency_percentage' => $dependencyPercentage,
        ];
    }

    /**
     * Generate supplier dashboard data
     */
    public function getSupplierDashboardData($startDate, $endDate)
    {
        $totalSuppliers = Vendor::count();
        $activeSuppliers = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->distinct('vendor_id')
            ->count();

        $analyses = SupplierCostAnalysis::whereBetween('analysis_date', [$startDate, $endDate])->get();

        $averagePerformance = $analyses->avg('overall_score');
        $topPerformers = $analyses->sortByDesc('overall_score')->take(5);
        $underperformers = $analyses->where('overall_score', '<', 60)->count();

        $totalPurchaseValue = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');

        return [
            'summary' => [
                'total_suppliers' => $totalSuppliers,
                'active_suppliers' => $activeSuppliers,
                'average_performance_score' => $averagePerformance,
                'underperformers_count' => $underperformers,
                'total_purchase_value' => $totalPurchaseValue,
            ],
            'top_performers' => $topPerformers->map(function ($analysis) {
                return [
                    'supplier' => $analysis->supplier,
                    'score' => $analysis->overall_score,
                    'grade' => $analysis->performance_grade,
                ];
            }),
            'opportunities' => $this->identifyCostOptimizationOpportunities($startDate, $endDate),
        ];
    }
}
