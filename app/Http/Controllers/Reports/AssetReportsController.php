<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\AssetReportService;
use Illuminate\Http\Request;

class AssetReportsController extends Controller
{
    public function __construct(
        private AssetReportService $assetReportService
    ) {}

    public function index()
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.index', compact('filterOptions'));
    }

    public function assetRegister(Request $request)
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only([
            'category_id',
            'project_id',
            'department_id',
            'status',
            'date_from',
            'date_to',
        ]);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportAssetRegisterToExcel($filters);
            }

            $assets = $this->assetReportService->getAssetRegister($filters);

            return $this->assetReportService->exportToCsv(
                $assets,
                'asset_register_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('asset_register')
            );
        }

        $assets = $this->assetReportService->getAssetRegister($filters, true);
        $totals = $this->assetReportService->getAssetRegisterTotals($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.asset-register', compact('assets', 'totals', 'filterOptions', 'filters'));
    }

    public function depreciationSchedule(Request $request)
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only([
            'asset_id',
            'category_id',
            'period_from',
            'period_to',
            'status',
        ]);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportDepreciationScheduleToExcel($filters);
            }

            $entries = $this->assetReportService->getDepreciationSchedule($filters);

            return $this->assetReportService->exportToCsv(
                $entries,
                'depreciation_schedule_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('depreciation_schedule')
            );
        }

        $entries = $this->assetReportService->getDepreciationSchedule($filters, true);
        $totals = $this->assetReportService->getDepreciationScheduleTotals($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.depreciation-schedule', compact('entries', 'totals', 'filterOptions', 'filters'));
    }

    public function disposalSummary(Request $request)
    {
        if (! auth()->user()->can('assets.disposal.view')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only([
            'disposal_type',
            'status',
            'date_from',
            'date_to',
            'category_id',
        ]);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportDisposalSummaryToExcel($filters);
            }

            $disposals = $this->assetReportService->getDisposalSummary($filters);

            return $this->assetReportService->exportToCsv(
                $disposals,
                'disposal_summary_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('disposal_summary')
            );
        }

        $disposals = $this->assetReportService->getDisposalSummary($filters, true);
        $totals = $this->assetReportService->getDisposalSummaryTotals($filters);
        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.disposal-summary', compact('disposals', 'totals', 'filterOptions', 'filters'));
    }

    public function movementLog(Request $request)
    {
        if (! auth()->user()->can('assets.movement.view')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only([
            'movement_type',
            'status',
            'date_from',
            'date_to',
            'asset_id',
        ]);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportMovementLogToExcel($filters);
            }

            $movements = $this->assetReportService->getMovementLog($filters);

            return $this->assetReportService->exportToCsv(
                $movements,
                'movement_log_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('movement_log')
            );
        }

        $movements = $this->assetReportService->getMovementLog($filters, true);
        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.movement-log', compact('movements', 'filterOptions', 'filters'));
    }

    public function summary()
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $summary = $this->assetReportService->getAssetSummary();

        return view('reports.assets.summary', compact('summary'));
    }

    public function assetAging(Request $request)
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only(['category_id']);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportAssetAgingToExcel($filters);
            }

            $assets = $this->assetReportService->getAssetAging($filters);

            return $this->assetReportService->exportToCsv(
                $assets,
                'asset_aging_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('asset_aging')
            );
        }

        $assets = $this->assetReportService->getAssetAging($filters, true);
        $filterOptions = $this->assetReportService->getFilterOptions();

        return view('reports.assets.asset-aging', compact('assets', 'filterOptions', 'filters'));
    }

    public function lowValueAssets(Request $request)
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $threshold = (float) $request->get('threshold', 1000000);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportLowValueAssetsToExcel($threshold);
            }

            $assets = $this->assetReportService->getLowValueAssets($threshold);

            return $this->assetReportService->exportToCsv(
                $assets,
                'low_value_assets_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('low_value_assets')
            );
        }

        $assets = $this->assetReportService->getLowValueAssets($threshold, true);

        return view('reports.assets.low-value-assets', compact('assets', 'threshold'));
    }

    public function depreciationRunHistory(Request $request)
    {
        if (! auth()->user()->can('assets.depreciation.run')) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only(['status', 'period_from', 'period_to']);

        if ($request->has('export')) {
            $exportType = $request->get('export', 'csv');
            if ($exportType === 'excel') {
                return $this->assetReportService->exportDepreciationRunHistoryToExcel($filters);
            }

            $runs = $this->assetReportService->getDepreciationRunHistory($filters);

            return $this->assetReportService->exportToCsv(
                $runs,
                'depreciation_run_history_'.date('Y-m-d').'.csv',
                $this->assetReportService->csvColumnsFor('depreciation_run_history')
            );
        }

        $runs = $this->assetReportService->getDepreciationRunHistory($filters, true);

        return view('reports.assets.depreciation-run-history', compact('runs', 'filters'));
    }

    public function getReportData(Request $request)
    {
        if (! auth()->user()->can('assets.view')) {
            abort(403, 'Unauthorized action.');
        }

        $reportType = $request->get('report_type');
        $filters = $request->except(['report_type', '_token']);

        switch ($reportType) {
            case 'asset_summary':
                $data = $this->assetReportService->getAssetSummary();

                return response()->json([
                    'data' => $data,
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                ]);
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
                $data = $this->assetReportService->getAssetAging($filters);
                break;
            case 'low_value_assets':
                $threshold = (float) ($filters['threshold'] ?? 1000000);
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
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
