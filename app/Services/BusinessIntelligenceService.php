<?php

namespace App\Services;

use App\Models\BusinessIntelligence;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\MarginAnalysis;
use App\Models\SupplierCostAnalysis;
use App\Models\ProductCostSummary;
use App\Models\CostHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessIntelligenceService
{
    /**
     * Generate comprehensive trading analytics report
     */
    public function generateTradingAnalytics($startDate, $endDate)
    {
        $data = [
            'sales_performance' => $this->getSalesPerformance($startDate, $endDate),
            'purchase_analysis' => $this->getPurchaseAnalysis($startDate, $endDate),
            'inventory_metrics' => $this->getInventoryMetrics($startDate, $endDate),
            'supplier_performance' => $this->getSupplierPerformance($startDate, $endDate),
            'customer_analysis' => $this->getCustomerAnalysis($startDate, $endDate),
            'margin_analysis' => $this->getMarginAnalysis($startDate, $endDate),
            'cost_analysis' => $this->getCostAnalysis($startDate, $endDate),
            'financial_metrics' => $this->getFinancialMetrics($startDate, $endDate),
        ];

        $insights = $this->generateInsights($data);
        $recommendations = $this->generateRecommendations($data, $insights);
        $kpiMetrics = $this->calculateKpiMetrics($data);
        $trendAnalysis = $this->analyzeTrends($startDate, $endDate);

        return BusinessIntelligence::create([
            'report_type' => 'trading_analytics',
            'report_name' => 'Comprehensive Trading Analytics Report',
            'report_date' => now(),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data_json' => $data,
            'insights_json' => $insights,
            'recommendations_json' => $recommendations,
            'kpi_metrics_json' => $kpiMetrics,
            'trend_analysis_json' => $trendAnalysis,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Get sales performance metrics
     */
    protected function getSalesPerformance($startDate, $endDate)
    {
        $salesOrders = SalesOrder::whereBetween('order_date', [$startDate, $endDate])->get();

        $totalRevenue = $salesOrders->sum('total_amount');
        $totalOrders = $salesOrders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Top customers by revenue
        $topCustomers = $salesOrders->groupBy('customer_id')
            ->map(function ($orders) {
                return [
                    'customer_id' => $orders->first()->customer_id,
                    'customer_name' => $orders->first()->customer->name ?? 'Unknown',
                    'total_orders' => $orders->count(),
                    'total_revenue' => $orders->sum('total_amount'),
                    'average_order_value' => $orders->avg('total_amount'),
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values();

        // Sales by month
        $monthlySales = $salesOrders->groupBy(function ($order) {
            return $order->order_date->format('Y-m');
        })->map(function ($orders) {
            return [
                'month' => $orders->first()->order_date->format('Y-m'),
                'revenue' => $orders->sum('total_amount'),
                'orders' => $orders->count(),
            ];
        })->values();

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'top_customers' => $topCustomers,
            'monthly_sales' => $monthlySales,
            'growth_rate' => $this->calculateGrowthRate($startDate, $endDate, 'sales'),
        ];
    }

    /**
     * Get purchase analysis metrics
     */
    protected function getPurchaseAnalysis($startDate, $endDate)
    {
        $purchaseOrders = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->get();

        $totalCost = $purchaseOrders->sum('total_amount');
        $totalOrders = $purchaseOrders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalCost / $totalOrders : 0;

        // Top suppliers by cost
        $topSuppliers = $purchaseOrders->groupBy('vendor_id')
            ->map(function ($orders) {
                return [
                    'supplier_id' => $orders->first()->vendor_id,
                    'supplier_name' => $orders->first()->vendor->name ?? 'Unknown',
                    'total_orders' => $orders->count(),
                    'total_cost' => $orders->sum('total_amount'),
                    'average_order_value' => $orders->avg('total_amount'),
                ];
            })
            ->sortByDesc('total_cost')
            ->take(10)
            ->values();

        // Purchase trends
        $monthlyPurchases = $purchaseOrders->groupBy(function ($order) {
            return $order->order_date->format('Y-m');
        })->map(function ($orders) {
            return [
                'month' => $orders->first()->order_date->format('Y-m'),
                'cost' => $orders->sum('total_amount'),
                'orders' => $orders->count(),
            ];
        })->values();

        return [
            'total_cost' => $totalCost,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'top_suppliers' => $topSuppliers,
            'monthly_purchases' => $monthlyPurchases,
            'growth_rate' => $this->calculateGrowthRate($startDate, $endDate, 'purchases'),
        ];
    }

    /**
     * Get inventory metrics
     */
    protected function getInventoryMetrics($startDate, $endDate)
    {
        $inventoryItems = InventoryItem::with('transactions')->get();

        $totalProducts = $inventoryItems->count();
        $totalInventoryValue = $inventoryItems->sum(function ($item) {
            return $item->current_stock * $item->average_cost;
        });

        // Inventory categories
        $inventoryCategories = $inventoryItems->groupBy('category_id')
            ->map(function ($items) {
                return [
                    'category_id' => $items->first()->category_id,
                    'category_name' => $items->first()->category->name ?? 'Unknown',
                    'product_count' => $items->count(),
                    'total_value' => $items->sum(function ($item) {
                        return $item->current_stock * $item->average_cost;
                    }),
                ];
            })
            ->sortByDesc('total_value')
            ->values();

        // Stock status
        $stockStatus = [
            'out_of_stock' => $inventoryItems->where('current_stock', 0)->count(),
            'low_stock' => $inventoryItems->filter(function ($item) {
                return $item->current_stock <= $item->reorder_point && $item->current_stock > 0;
            })->count(),
            'normal_stock' => $inventoryItems->filter(function ($item) {
                return $item->current_stock > $item->reorder_point;
            })->count(),
            'overstock' => $inventoryItems->filter(function ($item) {
                return $item->current_stock > $item->reorder_point * 2;
            })->count(),
        ];

        return [
            'total_products' => $totalProducts,
            'total_inventory_value' => $totalInventoryValue,
            'inventory_categories' => $inventoryCategories,
            'stock_status' => $stockStatus,
            'turnover_rate' => $this->calculateInventoryTurnover($startDate, $endDate),
        ];
    }

    /**
     * Get supplier performance metrics
     */
    protected function getSupplierPerformance($startDate, $endDate)
    {
        $supplierAnalyses = SupplierCostAnalysis::whereBetween('analysis_date', [$startDate, $endDate])->get();

        $totalSuppliers = Vendor::count();
        $activeSuppliers = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->distinct('vendor_id')
            ->count();

        $averagePerformanceScore = $supplierAnalyses->avg('overall_score');
        $topPerformers = $supplierAnalyses->sortByDesc('overall_score')->take(5);
        $underperformers = $supplierAnalyses->where('overall_score', '<', 60)->count();

        return [
            'total_suppliers' => $totalSuppliers,
            'active_suppliers' => $activeSuppliers,
            'average_performance_score' => $averagePerformanceScore,
            'top_performers' => $topPerformers->values(),
            'underperformers_count' => $underperformers,
            'performance_distribution' => $this->getPerformanceDistribution($supplierAnalyses),
        ];
    }

    /**
     * Get customer analysis metrics
     */
    protected function getCustomerAnalysis($startDate, $endDate)
    {
        $customers = Customer::with(['salesOrders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }])->get();

        $totalCustomers = $customers->count();
        $activeCustomers = $customers->filter(function ($customer) {
            return $customer->salesOrders->count() > 0;
        })->count();

        $customerRetentionRate = $this->calculateCustomerRetentionRate($startDate, $endDate);
        $averageCustomerValue = $customers->filter(function ($customer) {
            return $customer->salesOrders->count() > 0;
        })->avg(function ($customer) {
            return $customer->salesOrders->sum('total_amount');
        });

        // Customer segments
        $customerSegments = $customers->filter(function ($customer) {
            return $customer->salesOrders->count() > 0;
        })->groupBy(function ($customer) {
            $totalValue = $customer->salesOrders->sum('total_amount');
            if ($totalValue >= 100000) return 'high_value';
            if ($totalValue >= 50000) return 'medium_value';
            return 'low_value';
        })->map(function ($customers, $segment) {
            return [
                'segment' => $segment,
                'count' => $customers->count(),
                'total_value' => $customers->sum(function ($customer) {
                    return $customer->salesOrders->sum('total_amount');
                }),
            ];
        });

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'customer_retention_rate' => $customerRetentionRate,
            'average_customer_value' => $averageCustomerValue,
            'customer_segments' => $customerSegments,
        ];
    }

    /**
     * Get margin analysis metrics
     */
    protected function getMarginAnalysis($startDate, $endDate)
    {
        $marginAnalyses = MarginAnalysis::whereBetween('analysis_date', [$startDate, $endDate])->get();

        $averageGrossMargin = $marginAnalyses->avg('gross_margin_percentage');
        $averageNetMargin = $marginAnalyses->avg('net_margin_percentage');

        $highMarginProducts = $marginAnalyses->where('gross_margin_percentage', '>', 30)->count();
        $lowMarginProducts = $marginAnalyses->where('gross_margin_percentage', '<', 10)->count();

        // Margin by product category
        $marginByCategory = $marginAnalyses->groupBy('inventory_item_id')
            ->map(function ($analyses) {
                $item = $analyses->first()->inventoryItem;
                return [
                    'category_id' => $item->category_id ?? null,
                    'category_name' => $item->category->name ?? 'Unknown',
                    'average_margin' => $analyses->avg('gross_margin_percentage'),
                    'product_count' => $analyses->count(),
                ];
            })
            ->groupBy('category_id')
            ->map(function ($items, $categoryId) {
                return [
                    'category_id' => $categoryId,
                    'category_name' => $items->first()['category_name'],
                    'average_margin' => $items->avg('average_margin'),
                    'product_count' => $items->sum('product_count'),
                ];
            })
            ->sortByDesc('average_margin')
            ->values();

        return [
            'average_gross_margin' => $averageGrossMargin,
            'average_net_margin' => $averageNetMargin,
            'high_margin_products' => $highMarginProducts,
            'low_margin_products' => $lowMarginProducts,
            'margin_by_category' => $marginByCategory,
        ];
    }

    /**
     * Get cost analysis metrics
     */
    protected function getCostAnalysis($startDate, $endDate)
    {
        $costSummaries = ProductCostSummary::whereBetween('period_start', [$startDate, $endDate])->get();

        $totalCOGS = $costSummaries->sum('total_cost');
        $averageUnitCost = $costSummaries->avg('average_unit_cost');
        $costTrend = $this->calculateCostTrend($startDate, $endDate);

        // Cost optimization opportunities
        $costOptimizationOpportunities = $costSummaries->filter(function ($summary) {
            return $summary->total_cost > $summary->total_purchase_cost * 1.2; // 20% overhead
        })->count();

        // Cost by category
        $costByCategory = $costSummaries->groupBy('inventory_item_id')
            ->map(function ($summaries) {
                $item = InventoryItem::find($summaries->first()->inventory_item_id);
                return [
                    'category_id' => $item->category_id ?? null,
                    'category_name' => $item->category->name ?? 'Unknown',
                    'total_cost' => $summaries->sum('total_cost'),
                    'product_count' => $summaries->count(),
                ];
            })
            ->groupBy('category_id')
            ->map(function ($items, $categoryId) {
                return [
                    'category_id' => $categoryId,
                    'category_name' => $items->first()['category_name'],
                    'total_cost' => $items->sum('total_cost'),
                    'product_count' => $items->sum('product_count'),
                ];
            })
            ->sortByDesc('total_cost')
            ->values();

        return [
            'total_cogs' => $totalCOGS,
            'average_unit_cost' => $averageUnitCost,
            'cost_trend' => $costTrend,
            'cost_optimization_opportunities' => $costOptimizationOpportunities,
            'cost_by_category' => $costByCategory,
        ];
    }

    /**
     * Get financial metrics
     */
    protected function getFinancialMetrics($startDate, $endDate)
    {
        $salesRevenue = SalesOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');
        $purchaseCosts = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');

        $grossProfit = $salesRevenue - $purchaseCosts;
        $grossProfitMargin = $salesRevenue > 0 ? ($grossProfit / $salesRevenue) * 100 : 0;

        // Return on Investment (ROI)
        $totalInventoryValue = InventoryItem::sum(DB::raw('current_stock * average_cost'));
        $roi = $totalInventoryValue > 0 ? ($grossProfit / $totalInventoryValue) * 100 : 0;

        // Cash flow analysis
        $cashFlow = [
            'inflow' => $salesRevenue,
            'outflow' => $purchaseCosts,
            'net_cash_flow' => $salesRevenue - $purchaseCosts,
        ];

        return [
            'sales_revenue' => $salesRevenue,
            'purchase_costs' => $purchaseCosts,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $grossProfitMargin,
            'roi' => $roi,
            'cash_flow' => $cashFlow,
        ];
    }

    /**
     * Generate business insights
     */
    protected function generateInsights($data)
    {
        $insights = [];

        // Sales insights
        if ($data['sales_performance']['total_revenue'] > 0) {
            $insights[] = [
                'type' => 'sales',
                'priority' => 'high',
                'message' => "Generated total revenue of " . number_format($data['sales_performance']['total_revenue']) . " from " . $data['sales_performance']['total_orders'] . " orders",
                'impact' => 'positive',
            ];
        }

        // Inventory insights
        if ($data['inventory_metrics']['stock_status']['out_of_stock'] > 0) {
            $insights[] = [
                'type' => 'inventory',
                'priority' => 'high',
                'message' => $data['inventory_metrics']['stock_status']['out_of_stock'] . " products are out of stock",
                'impact' => 'negative',
            ];
        }

        if ($data['inventory_metrics']['stock_status']['overstock'] > 0) {
            $insights[] = [
                'type' => 'inventory',
                'priority' => 'medium',
                'message' => $data['inventory_metrics']['stock_status']['overstock'] . " products are overstocked",
                'impact' => 'negative',
            ];
        }

        // Margin insights
        if ($data['margin_analysis']['average_gross_margin'] > 0) {
            $insights[] = [
                'type' => 'margin',
                'priority' => 'medium',
                'message' => "Average gross margin of " . number_format($data['margin_analysis']['average_gross_margin'], 1) . "% across all products",
                'impact' => $data['margin_analysis']['average_gross_margin'] > 20 ? 'positive' : 'negative',
            ];
        }

        // Supplier insights
        if ($data['supplier_performance']['average_performance_score'] > 0) {
            $insights[] = [
                'type' => 'supplier',
                'priority' => 'medium',
                'message' => "Average supplier performance score of " . number_format($data['supplier_performance']['average_performance_score'], 1) . "/100",
                'impact' => $data['supplier_performance']['average_performance_score'] > 70 ? 'positive' : 'negative',
            ];
        }

        // Customer insights
        if ($data['customer_analysis']['customer_retention_rate'] > 0) {
            $insights[] = [
                'type' => 'customer',
                'priority' => 'medium',
                'message' => "Customer retention rate of " . number_format($data['customer_analysis']['customer_retention_rate'], 1) . "%",
                'impact' => $data['customer_analysis']['customer_retention_rate'] > 80 ? 'positive' : 'negative',
            ];
        }

        return $insights;
    }

    /**
     * Generate business recommendations
     */
    protected function generateRecommendations($data, $insights)
    {
        $recommendations = [];

        // Inventory recommendations
        if ($data['inventory_metrics']['stock_status']['out_of_stock'] > 0) {
            $recommendations[] = [
                'type' => 'inventory',
                'priority' => 'high',
                'title' => 'Stock Management',
                'description' => 'Implement automated reorder point alerts to prevent stockouts',
                'action' => 'Set up automated inventory alerts and review reorder points',
            ];
        }

        if ($data['inventory_metrics']['stock_status']['overstock'] > 0) {
            $recommendations[] = [
                'type' => 'inventory',
                'priority' => 'medium',
                'title' => 'Overstock Management',
                'description' => 'Consider promotional activities for overstocked items',
                'action' => 'Create promotional campaigns for overstocked products',
            ];
        }

        // Margin recommendations
        if ($data['margin_analysis']['low_margin_products'] > 0) {
            $recommendations[] = [
                'type' => 'margin',
                'priority' => 'high',
                'title' => 'Margin Optimization',
                'description' => 'Review pricing strategy for low-margin products',
                'action' => 'Analyze low-margin products and consider price adjustments or discontinuation',
            ];
        }

        // Supplier recommendations
        if ($data['supplier_performance']['underperformers_count'] > 0) {
            $recommendations[] = [
                'type' => 'supplier',
                'priority' => 'medium',
                'title' => 'Supplier Performance',
                'description' => 'Review contracts with underperforming suppliers',
                'action' => 'Conduct supplier performance reviews and consider alternative vendors',
            ];
        }

        // Cost recommendations
        if ($data['cost_analysis']['cost_optimization_opportunities'] > 0) {
            $recommendations[] = [
                'type' => 'cost',
                'priority' => 'medium',
                'title' => 'Cost Optimization',
                'description' => 'Investigate cost optimization opportunities',
                'action' => 'Review procurement processes and supplier negotiations',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate KPI metrics
     */
    protected function calculateKpiMetrics($data)
    {
        return [
            'revenue_growth' => $data['sales_performance']['growth_rate'],
            'profit_margin' => $data['margin_analysis']['average_net_margin'] ?? 0,
            'inventory_turnover' => $data['inventory_metrics']['turnover_rate'],
            'supplier_performance' => $data['supplier_performance']['average_performance_score'] ?? 0,
            'customer_retention' => $data['customer_analysis']['customer_retention_rate'] ?? 0,
            'cost_efficiency' => $this->calculateCostEfficiency($data),
            'roi' => $data['financial_metrics']['roi'] ?? 0,
        ];
    }

    /**
     * Analyze trends
     */
    protected function analyzeTrends($startDate, $endDate)
    {
        $previousPeriodStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousPeriodEnd = $startDate->copy()->subDay();

        $currentSales = SalesOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');
        $previousSales = SalesOrder::whereBetween('order_date', [$previousPeriodStart, $previousPeriodEnd])->sum('total_amount');

        $currentPurchases = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');
        $previousPurchases = PurchaseOrder::whereBetween('order_date', [$previousPeriodStart, $previousPeriodEnd])->sum('total_amount');

        return [
            'revenue_trend' => $previousSales > 0 ? (($currentSales - $previousSales) / $previousSales) * 100 : 0,
            'purchase_trend' => $previousPurchases > 0 ? (($currentPurchases - $previousPurchases) / $previousPurchases) * 100 : 0,
            'profit_trend' => $this->calculateProfitTrend($startDate, $endDate, $previousPeriodStart, $previousPeriodEnd),
        ];
    }

    // Helper methods
    protected function calculateGrowthRate($startDate, $endDate, $type)
    {
        $previousPeriodStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousPeriodEnd = $startDate->copy()->subDay();

        if ($type === 'sales') {
            $current = SalesOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');
            $previous = SalesOrder::whereBetween('order_date', [$previousPeriodStart, $previousPeriodEnd])->sum('total_amount');
        } else {
            $current = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');
            $previous = PurchaseOrder::whereBetween('order_date', [$previousPeriodStart, $previousPeriodEnd])->sum('total_amount');
        }

        return $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
    }

    protected function calculateInventoryTurnover($startDate, $endDate)
    {
        $cogs = ProductCostSummary::whereBetween('period_start', [$startDate, $endDate])->sum('total_cost');
        $averageInventory = InventoryItem::avg(DB::raw('current_stock * average_cost'));

        return $averageInventory > 0 ? $cogs / $averageInventory : 0;
    }

    protected function calculateCustomerRetentionRate($startDate, $endDate)
    {
        $totalCustomers = Customer::count();
        $activeCustomers = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
            ->distinct('customer_id')
            ->count();

        return $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0;
    }

    protected function calculateCostTrend($startDate, $endDate)
    {
        $currentPeriod = ProductCostSummary::whereBetween('period_start', [$startDate, $endDate])->sum('total_cost');
        $previousPeriod = ProductCostSummary::where('period_end', '<', $startDate)->sum('total_cost');

        return $previousPeriod > 0 ? (($currentPeriod - $previousPeriod) / $previousPeriod) * 100 : 0;
    }

    protected function calculateCostEfficiency($data)
    {
        $totalRevenue = $data['sales_performance']['total_revenue'];
        $totalCosts = $data['cost_analysis']['total_cogs'];

        return $totalRevenue > 0 ? (($totalRevenue - $totalCosts) / $totalRevenue) * 100 : 0;
    }

    protected function calculateProfitTrend($startDate, $endDate, $previousStart, $previousEnd)
    {
        $currentProfit = SalesOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount') -
            PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total_amount');

        $previousProfit = SalesOrder::whereBetween('order_date', [$previousStart, $previousEnd])->sum('total_amount') -
            PurchaseOrder::whereBetween('order_date', [$previousStart, $previousEnd])->sum('total_amount');

        return $previousProfit > 0 ? (($currentProfit - $previousProfit) / $previousProfit) * 100 : 0;
    }

    protected function getPerformanceDistribution($supplierAnalyses)
    {
        return [
            'excellent' => $supplierAnalyses->where('overall_score', '>=', 90)->count(),
            'good' => $supplierAnalyses->whereBetween('overall_score', [70, 89])->count(),
            'average' => $supplierAnalyses->whereBetween('overall_score', [50, 69])->count(),
            'poor' => $supplierAnalyses->where('overall_score', '<', 50)->count(),
        ];
    }
}
