<?php

namespace App\Http\Controllers;

use App\Services\COGSService;
use App\Services\SupplierAnalyticsService;
use App\Services\BusinessIntelligenceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $cogsService;
    protected $supplierAnalyticsService;
    protected $businessIntelligenceService;

    public function __construct(
        COGSService $cogsService,
        SupplierAnalyticsService $supplierAnalyticsService,
        BusinessIntelligenceService $businessIntelligenceService
    ) {
        $this->cogsService = $cogsService;
        $this->supplierAnalyticsService = $supplierAnalyticsService;
        $this->businessIntelligenceService = $businessIntelligenceService;
    }

    /**
     * Display unified analytics dashboard
     */
    public function unifiedDashboard(Request $request)
    {
        $startDate = $request->filled('date_from') ? $request->date_from : Carbon::now()->startOfMonth();
        $endDate = $request->filled('date_to') ? $request->date_to : Carbon::now()->endOfMonth();

        // Get data from all analytics services
        $dashboardData = [
            'cogs_summary' => $this->getCOGSSummary($startDate, $endDate),
            'supplier_summary' => $this->getSupplierSummary($startDate, $endDate),
            'business_intelligence' => $this->getBusinessIntelligenceSummary($startDate, $endDate),
            'integrated_insights' => $this->getIntegratedInsights($startDate, $endDate),
            'performance_metrics' => $this->getPerformanceMetrics($startDate, $endDate),
            'optimization_opportunities' => $this->getOptimizationOpportunities($startDate, $endDate),
        ];

        return view('analytics.unified-dashboard', compact('dashboardData', 'startDate', 'endDate'));
    }

    /**
     * Get COGS summary data
     */
    protected function getCOGSSummary($startDate, $endDate)
    {
        // This would typically call COGSService methods
        // For now, return mock data structure
        return [
            'total_cogs' => 150000,
            'average_margin' => 25.5,
            'cost_allocation_efficiency' => 85.2,
            'top_cost_products' => [
                ['name' => 'Product A', 'cost' => 25000, 'margin' => 30.5],
                ['name' => 'Product B', 'cost' => 22000, 'margin' => 22.8],
                ['name' => 'Product C', 'cost' => 18000, 'margin' => 28.2],
            ],
            'cost_trends' => [
                'current_period' => 150000,
                'previous_period' => 145000,
                'growth_rate' => 3.4,
            ],
        ];
    }

    /**
     * Get supplier analytics summary
     */
    protected function getSupplierSummary($startDate, $endDate)
    {
        return [
            'total_suppliers' => 45,
            'active_suppliers' => 38,
            'average_performance_score' => 78.5,
            'top_performers' => [
                ['name' => 'Supplier Alpha', 'score' => 95.2, 'cost_efficiency' => 88.5],
                ['name' => 'Supplier Beta', 'score' => 92.8, 'cost_efficiency' => 85.2],
                ['name' => 'Supplier Gamma', 'score' => 89.1, 'cost_efficiency' => 82.8],
            ],
            'performance_trends' => [
                'current_period' => 78.5,
                'previous_period' => 75.2,
                'improvement' => 3.3,
            ],
        ];
    }

    /**
     * Get business intelligence summary
     */
    protected function getBusinessIntelligenceSummary($startDate, $endDate)
    {
        return [
            'revenue_growth' => 12.5,
            'profit_margin' => 18.2,
            'customer_retention' => 85.5,
            'roi' => 22.8,
            'key_insights' => [
                'Revenue increased by 12.5% compared to previous period',
                'Profit margins improved due to cost optimization',
                'Customer retention rate remains strong at 85.5%',
                'ROI improved to 22.8% through better supplier management',
            ],
        ];
    }

    /**
     * Get integrated insights across all modules
     */
    protected function getIntegratedInsights($startDate, $endDate)
    {
        return [
            [
                'type' => 'cost_optimization',
                'priority' => 'high',
                'title' => 'Cost Optimization Opportunity',
                'description' => 'Combined COGS and supplier analysis reveals potential 15% cost reduction',
                'impact' => 'Could save approximately $22,500 monthly',
                'action_required' => 'Review supplier contracts and renegotiate terms',
            ],
            [
                'type' => 'margin_improvement',
                'priority' => 'medium',
                'title' => 'Margin Enhancement',
                'description' => 'Product mix optimization could improve overall margins by 3-5%',
                'impact' => 'Potential additional profit of $8,000-12,000 monthly',
                'action_required' => 'Analyze product profitability and adjust pricing strategy',
            ],
            [
                'type' => 'supplier_consolidation',
                'priority' => 'medium',
                'title' => 'Supplier Consolidation',
                'description' => 'Consolidating suppliers could reduce procurement costs',
                'impact' => 'Estimated savings of $5,000-8,000 monthly',
                'action_required' => 'Evaluate supplier consolidation opportunities',
            ],
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics($startDate, $endDate)
    {
        return [
            'financial' => [
                'revenue' => 450000,
                'costs' => 300000,
                'profit' => 150000,
                'margin_percentage' => 33.3,
            ],
            'operational' => [
                'inventory_turnover' => 4.2,
                'supplier_performance' => 78.5,
                'cost_allocation_accuracy' => 92.3,
                'customer_satisfaction' => 88.7,
            ],
            'efficiency' => [
                'procurement_efficiency' => 85.2,
                'cost_control' => 78.9,
                'margin_optimization' => 82.1,
                'supplier_management' => 79.4,
            ],
        ];
    }

    /**
     * Get optimization opportunities
     */
    protected function getOptimizationOpportunities($startDate, $endDate)
    {
        return [
            [
                'category' => 'cost_reduction',
                'title' => 'Supplier Negotiation',
                'description' => 'Renegotiate contracts with top 5 suppliers',
                'potential_savings' => 15000,
                'effort_level' => 'medium',
                'timeline' => '2-3 months',
            ],
            [
                'category' => 'margin_improvement',
                'title' => 'Product Mix Optimization',
                'description' => 'Focus on high-margin products',
                'potential_savings' => 12000,
                'effort_level' => 'low',
                'timeline' => '1 month',
            ],
            [
                'category' => 'efficiency',
                'title' => 'Automated Cost Allocation',
                'description' => 'Implement automated cost allocation system',
                'potential_savings' => 8000,
                'effort_level' => 'high',
                'timeline' => '3-4 months',
            ],
        ];
    }

    /**
     * Get comprehensive analytics data
     */
    public function getComprehensiveAnalytics(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->start_date : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? $request->end_date : Carbon::now()->endOfMonth();

        $data = [
            'cogs_data' => $this->getCOGSSummary($startDate, $endDate),
            'supplier_data' => $this->getSupplierSummary($startDate, $endDate),
            'bi_data' => $this->getBusinessIntelligenceSummary($startDate, $endDate),
            'integrated_insights' => $this->getIntegratedInsights($startDate, $endDate),
            'performance_metrics' => $this->getPerformanceMetrics($startDate, $endDate),
            'optimization_opportunities' => $this->getOptimizationOpportunities($startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Comprehensive analytics data retrieved successfully'
        ]);
    }

    /**
     * Generate integrated report
     */
    public function generateIntegratedReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            // Generate reports from all services
            $cogsReport = $this->cogsService->generateReport($request->start_date, $request->end_date);
            $supplierReport = $this->supplierAnalyticsService->generateAnalytics($request->start_date, $request->end_date);
            $biReport = $this->businessIntelligenceService->generateTradingAnalytics($request->start_date, $request->end_date);

            $integratedReport = [
                'report_period' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
                'cogs_summary' => $cogsReport,
                'supplier_analytics' => $supplierReport,
                'business_intelligence' => $biReport,
                'generated_at' => now(),
                'generated_by' => auth()->user()->name,
            ];

            return response()->json([
                'success' => true,
                'data' => $integratedReport,
                'message' => 'Integrated analytics report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating integrated report: ' . $e->getMessage()
            ], 500);
        }
    }
}
