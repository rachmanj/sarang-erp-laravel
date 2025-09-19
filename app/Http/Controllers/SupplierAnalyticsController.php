<?php

namespace App\Http\Controllers;

use App\Models\SupplierCostAnalysis;
use App\Models\SupplierPerformance;
use App\Models\SupplierComparison;
use App\Models\Vendor;
use App\Models\ProductCategory;
use App\Services\SupplierAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SupplierAnalyticsController extends Controller
{
    protected $supplierAnalyticsService;

    public function __construct(SupplierAnalyticsService $supplierAnalyticsService)
    {
        $this->supplierAnalyticsService = $supplierAnalyticsService;
    }

    /**
     * Display supplier analytics dashboard
     */
    public function index()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $dashboardData = $this->supplierAnalyticsService->getSupplierDashboardData(
            $currentMonth,
            Carbon::now()->endOfMonth()
        );

        $lastMonthData = $this->supplierAnalyticsService->getSupplierDashboardData(
            $lastMonth,
            Carbon::now()->subMonth()->endOfMonth()
        );

        // Calculate percentage changes
        $performanceChange = $lastMonthData['summary']['average_performance_score'] > 0 ?
            (($dashboardData['summary']['average_performance_score'] - $lastMonthData['summary']['average_performance_score']) / $lastMonthData['summary']['average_performance_score']) * 100 : 0;

        return view('supplier-analytics.index', compact(
            'dashboardData',
            'lastMonthData',
            'performanceChange'
        ));
    }

    /**
     * Display supplier performance analysis
     */
    public function performance(Request $request)
    {
        $query = SupplierCostAnalysis::with('supplier');

        // Apply filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->where('analysis_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('analysis_date', '<=', $request->date_to);
        }

        if ($request->filled('min_score')) {
            $query->where('overall_score', '>=', $request->min_score);
        }

        $supplierAnalyses = $query->orderBy('overall_score', 'desc')
            ->paginate(20);

        $suppliers = \App\Models\BusinessPartner::where('partner_type', 'supplier')->get();

        return view('supplier-analytics.performance', compact('supplierAnalyses', 'suppliers'));
    }

    /**
     * Display supplier comparisons
     */
    public function comparisons(Request $request)
    {
        $query = SupplierComparison::with(['productCategory', 'supplier1', 'supplier2', 'supplier3', 'recommendedSupplier']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('product_category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->where('comparison_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('comparison_date', '<=', $request->date_to);
        }

        $comparisons = $query->orderBy('comparison_date', 'desc')
            ->paginate(20);

        $categories = ProductCategory::all();

        return view('supplier-analytics.comparisons', compact('comparisons', 'categories'));
    }

    /**
     * Display cost optimization opportunities
     */
    public function optimization(Request $request)
    {
        $startDate = $request->filled('date_from') ? $request->date_from : Carbon::now()->startOfMonth();
        $endDate = $request->filled('date_to') ? $request->date_to : Carbon::now()->endOfMonth();

        $opportunities = $this->supplierAnalyticsService->identifyCostOptimizationOpportunities($startDate, $endDate);

        return view('supplier-analytics.optimization', compact('opportunities', 'startDate', 'endDate'));
    }

    /**
     * Generate supplier analytics report
     */
    public function generateAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $analytics = $this->supplierAnalyticsService->generateSupplierAnalytics(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'message' => 'Supplier analytics generated successfully'
        ]);
    }

    /**
     * Get supplier ranking
     */
    public function getSupplierRanking(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'criteria' => 'in:overall,cost,delivery,quality',
        ]);

        $ranking = $this->supplierAnalyticsService->getSupplierRanking(
            $request->start_date,
            $request->end_date,
            $request->criteria ?? 'overall'
        );

        return response()->json([
            'success' => true,
            'data' => $ranking,
            'message' => 'Supplier ranking retrieved successfully'
        ]);
    }

    /**
     * Compare suppliers for a category
     */
    public function compareSuppliers(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'supplier_ids' => 'array|max:3',
            'supplier_ids.*' => 'exists:business_partners,id',
        ]);

        $comparison = $this->supplierAnalyticsService->compareSuppliers(
            $request->category_id,
            $request->supplier_ids
        );

        return response()->json([
            'success' => true,
            'data' => $comparison,
            'message' => 'Supplier comparison created successfully'
        ]);
    }

    /**
     * Get supplier performance trends
     */
    public function getSupplierTrends(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:business_partners,id',
            'months' => 'integer|min:1|max:24',
        ]);

        $trends = $this->supplierAnalyticsService->getSupplierPerformanceTrends(
            $request->supplier_id,
            $request->months ?? 12
        );

        return response()->json([
            'success' => true,
            'data' => $trends,
            'message' => 'Supplier trends retrieved successfully'
        ]);
    }

    /**
     * Calculate supplier risk assessment
     */
    public function calculateSupplierRisk(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:business_partners,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $riskAssessment = $this->supplierAnalyticsService->calculateSupplierRisk(
            $request->supplier_id,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $riskAssessment,
            'message' => 'Supplier risk assessment completed successfully'
        ]);
    }

    /**
     * Get supplier details
     */
    public function getSupplierDetails(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:business_partners,id',
        ]);

        $supplier = \App\Models\BusinessPartner::with(['purchaseOrders' => function ($query) {
            $query->latest()->limit(10);
        }])->find($request->supplier_id);

        $currentMonth = Carbon::now()->startOfMonth();
        $analysis = SupplierCostAnalysis::where('supplier_id', $request->supplier_id)
            ->whereBetween('analysis_date', [$currentMonth, Carbon::now()->endOfMonth()])
            ->latest()
            ->first();

        $performance = SupplierPerformance::where('vendor_id', $request->supplier_id)
            ->latest()
            ->first();

        $riskAssessment = $this->supplierAnalyticsService->calculateSupplierRisk(
            $request->supplier_id,
            $currentMonth,
            Carbon::now()->endOfMonth()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $supplier,
                'analysis' => $analysis,
                'performance' => $performance,
                'risk_assessment' => $riskAssessment,
            ],
            'message' => 'Supplier details retrieved successfully'
        ]);
    }

    /**
     * Export supplier analytics data
     */
    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|in:performance,comparisons,optimization',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'in:csv,excel',
        ]);

        $data = [];
        $filename = '';

        switch ($request->type) {
            case 'performance':
                $data = SupplierCostAnalysis::with('supplier')
                    ->whereBetween('analysis_date', [$request->start_date, $request->end_date])
                    ->get();
                $filename = 'supplier_performance_' . $request->start_date . '_to_' . $request->end_date;
                break;

            case 'comparisons':
                $data = SupplierComparison::with(['productCategory', 'supplier1', 'supplier2', 'supplier3'])
                    ->whereBetween('comparison_date', [$request->start_date, $request->end_date])
                    ->get();
                $filename = 'supplier_comparisons_' . $request->start_date . '_to_' . $request->end_date;
                break;

            case 'optimization':
                $data = $this->supplierAnalyticsService->identifyCostOptimizationOpportunities(
                    $request->start_date,
                    $request->end_date
                );
                $filename = 'supplier_optimization_' . $request->start_date . '_to_' . $request->end_date;
                break;
        }

        if ($request->format === 'csv') {
            return $this->exportToCSV($data, $filename);
        }

        return $this->exportToExcel($data, $filename);
    }

    /**
     * Export data to CSV
     */
    protected function exportToCSV($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            if (!empty($data)) {
                if (is_array($data) && isset($data[0])) {
                    // Write headers for array data
                    fputcsv($file, array_keys($data[0]));

                    // Write data
                    foreach ($data as $row) {
                        fputcsv($file, $row);
                    }
                } else {
                    // Write headers for collection data
                    fputcsv($file, array_keys($data->first()->toArray()));

                    // Write data
                    foreach ($data as $row) {
                        fputcsv($file, $row->toArray());
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export data to Excel
     */
    protected function exportToExcel($data, $filename)
    {
        // This would typically use Laravel Excel package
        // For now, return CSV format
        return $this->exportToCSV($data, $filename);
    }
}
