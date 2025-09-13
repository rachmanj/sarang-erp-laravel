<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\AssetReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AssetReportsController extends Controller
{
    public function __construct(
        private AssetReportService $assetReportService
    ) {}

    /**
     * Display asset reports index
     */
    public function index()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.index', compact('filterOptions'));
    }

    /**
     * Asset Register Report
     */
    public function assetRegister(Request $request)
    {
        $this->authorize('view', \App\Models\Asset::class);

        $filters = $request->only([
            'category_id',
            'fund_id',
            'project_id',
            'department_id',
            'status',
            'date_from',
            'date_to'
        ]);

        $assets = $this->assetReportService->getAssetRegister($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportAssetRegisterToExcel($filters);
            } else {
                return $this->assetReportService->exportToCsv($assets, 'asset_register_' . date('Y-m-d') . '.csv');
            }
        }

        return view('reports.assets.asset-register', compact('assets', 'filterOptions', 'filters'));
    }

    /**
     * Depreciation Schedule Report
     */
    public function depreciationSchedule(Request $request)
    {
        $this->authorize('view', \App\Models\AssetDepreciationEntry::class);

        $filters = $request->only([
            'asset_id',
            'category_id',
            'period_from',
            'period_to',
            'status'
        ]);

        $entries = $this->assetReportService->getDepreciationSchedule($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportDepreciationScheduleToExcel($filters);
            } else {
                return $this->assetReportService->exportToCsv($entries, 'depreciation_schedule_' . date('Y-m-d') . '.csv');
            }
        }

        return view('reports.assets.depreciation-schedule', compact('entries', 'filterOptions', 'filters'));
    }

    /**
     * Disposal Summary Report
     */
    public function disposalSummary(Request $request)
    {
        $this->authorize('view', \App\Models\AssetDisposal::class);

        $filters = $request->only([
            'disposal_type',
            'status',
            'date_from',
            'date_to',
            'category_id'
        ]);

        $disposals = $this->assetReportService->getDisposalSummary($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportDisposalSummaryToExcel($filters);
            } else {
                return $this->assetReportService->exportToCsv($disposals, 'disposal_summary_' . date('Y-m-d') . '.csv');
            }
        }

        return view('reports.assets.disposal-summary', compact('disposals', 'filterOptions', 'filters'));
    }

    /**
     * Movement Log Report
     */
    public function movementLog(Request $request)
    {
        $this->authorize('view', \App\Models\AssetMovement::class);

        $filters = $request->only([
            'movement_type',
            'status',
            'date_from',
            'date_to',
            'asset_id'
        ]);

        $movements = $this->assetReportService->getMovementLog($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportMovementLogToExcel($filters);
            } else {
                return $this->assetReportService->exportToCsv($movements, 'movement_log_' . date('Y-m-d') . '.csv');
            }
        }

        return view('reports.assets.movement-log', compact('movements', 'filterOptions', 'filters'));
    }

    /**
     * Asset Summary Dashboard
     */
    public function summary()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $summary = $this->assetReportService->getAssetSummary();

        return view('reports.assets.summary', compact('summary'));
    }

    /**
     * Asset Aging Report
     */
    public function assetAging(Request $request)
    {
        $this->authorize('view', \App\Models\Asset::class);

        $assets = $this->assetReportService->getAssetAging();

        if ($request->has('export')) {
            return $this->assetReportService->exportToCsv($assets, 'asset_aging_' . date('Y-m-d') . '.csv');
        }

        return view('reports.assets.asset-aging', compact('assets'));
    }

    /**
     * Low Value Assets Report
     */
    public function lowValueAssets(Request $request)
    {
        $this->authorize('view', \App\Models\Asset::class);

        $threshold = $request->get('threshold', 1000000);
        $assets = $this->assetReportService->getLowValueAssets($threshold);

        if ($request->has('export')) {
            return $this->assetReportService->exportToCsv($assets, 'low_value_assets_' . date('Y-m-d') . '.csv');
        }

        return view('reports.assets.low-value-assets', compact('assets', 'threshold'));
    }

    /**
     * Depreciation Run History
     */
    public function depreciationRunHistory(Request $request)
    {
        $this->authorize('view', \App\Models\AssetDepreciationRun::class);

        $filters = $request->only(['status', 'period_from', 'period_to']);
        $runs = $this->assetReportService->getDepreciationRunHistory($filters);

        if ($request->has('export')) {
            return $this->assetReportService->exportToCsv($runs, 'depreciation_run_history_' . date('Y-m-d') . '.csv');
        }

        return view('reports.assets.depreciation-run-history', compact('runs', 'filters'));
    }

    /**
     * Get report data for AJAX requests
     */
    public function getReportData(Request $request)
    {
        $reportType = $request->get('report_type');
        $filters = $request->except(['report_type', '_token']);

        switch ($reportType) {
            case 'asset_register':
                $data = $this->assetReportService->getAssetRegister($filters);
                break;
            case 'depreciation_schedule':
                $data = $this->assetReportService->getDepreciationSchedule($filters);
                break;
            case 'disposal_summary':
                $data = $this->assetReportService->getDisposalSummary($filters);
                break;
            case 'movement_log':
                $data = $this->assetReportService->getMovementLog($filters);
                break;
            case 'asset_aging':
                $data = $this->assetReportService->getAssetAging();
                break;
            case 'low_value_assets':
                $threshold = $filters['threshold'] ?? 1000000;
                $data = $this->assetReportService->getLowValueAssets($threshold);
                break;
            case 'depreciation_run_history':
                $data = $this->assetReportService->getDepreciationRunHistory($filters);
                break;
            default:
                return response()->json(['error' => 'Invalid report type'], 400);
        }

        return response()->json([
            'data' => $data,
            'count' => $data->count(),
            'generated_at' => now()->format('Y-m-d H:i:s')
        ]);
    }
}
