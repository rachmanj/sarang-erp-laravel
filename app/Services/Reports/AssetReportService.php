<?php

namespace App\Services\Reports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationEntry;
use App\Models\AssetDepreciationRun;
use App\Models\AssetDisposal;
use App\Models\AssetMovement;
use App\Exports\AssetRegisterExport;
use App\Exports\DepreciationScheduleExport;
use App\Exports\DisposalSummaryExport;
use App\Exports\MovementLogExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AssetReportService
{
    /**
     * Generate Asset Register Report
     */
    public function getAssetRegister($filters = [])
    {
        $query = Asset::with(['category', 'fund', 'project', 'department', 'vendor'])
            ->select([
                'assets.*',
                'asset_categories.name as category_name',
                'funds.name as fund_name',
                'projects.name as project_name',
                'departments.name as department_name',
                'vendors.name as vendor_name'
            ])
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->leftJoin('funds', 'assets.fund_id', '=', 'funds.id')
            ->leftJoin('projects', 'assets.project_id', '=', 'projects.id')
            ->leftJoin('departments', 'assets.department_id', '=', 'departments.id')
            ->leftJoin('vendors', 'assets.vendor_id', '=', 'vendors.id');

        // Apply filters
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        if (isset($filters['fund_id']) && $filters['fund_id']) {
            $query->where('assets.fund_id', $filters['fund_id']);
        }

        if (isset($filters['project_id']) && $filters['project_id']) {
            $query->where('assets.project_id', $filters['project_id']);
        }

        if (isset($filters['department_id']) && $filters['department_id']) {
            $query->where('assets.department_id', $filters['department_id']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('assets.status', $filters['status']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('assets.acquisition_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('assets.acquisition_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('assets.code')->get();
    }

    /**
     * Generate Depreciation Schedule Report
     */
    public function getDepreciationSchedule($filters = [])
    {
        $query = AssetDepreciationEntry::with(['asset.category', 'journal'])
            ->select([
                'asset_depreciation_entries.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name',
                'asset_depreciation_runs.period_start',
                'asset_depreciation_runs.period_end'
            ])
            ->join('assets', 'asset_depreciation_entries.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->join('asset_depreciation_runs', 'asset_depreciation_entries.run_id', '=', 'asset_depreciation_runs.id');

        // Apply filters
        if (isset($filters['asset_id']) && $filters['asset_id']) {
            $query->where('asset_depreciation_entries.asset_id', $filters['asset_id']);
        }

        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        if (isset($filters['period_from']) && $filters['period_from']) {
            $query->where('asset_depreciation_runs.period_start', '>=', $filters['period_from']);
        }

        if (isset($filters['period_to']) && $filters['period_to']) {
            $query->where('asset_depreciation_runs.period_end', '<=', $filters['period_to']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('asset_depreciation_entries.status', $filters['status']);
        }

        return $query->orderBy('asset_depreciation_runs.period_start', 'desc')
            ->orderBy('assets.code')
            ->get();
    }

    /**
     * Generate Disposal Summary Report
     */
    public function getDisposalSummary($filters = [])
    {
        $query = AssetDisposal::with(['asset.category', 'creator', 'poster'])
            ->select([
                'asset_disposals.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name'
            ])
            ->join('assets', 'asset_disposals.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        // Apply filters
        if (isset($filters['disposal_type']) && $filters['disposal_type']) {
            $query->where('asset_disposals.disposal_type', $filters['disposal_type']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('asset_disposals.status', $filters['status']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('asset_disposals.disposal_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('asset_disposals.disposal_date', '<=', $filters['date_to']);
        }

        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        return $query->orderBy('asset_disposals.disposal_date', 'desc')->get();
    }

    /**
     * Generate Movement Log Report
     */
    public function getMovementLog($filters = [])
    {
        $query = AssetMovement::with(['asset.category', 'creator', 'approver'])
            ->select([
                'asset_movements.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name'
            ])
            ->join('assets', 'asset_movements.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        // Apply filters
        if (isset($filters['movement_type']) && $filters['movement_type']) {
            $query->where('asset_movements.movement_type', $filters['movement_type']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('asset_movements.status', $filters['status']);
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->where('asset_movements.movement_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->where('asset_movements.movement_date', '<=', $filters['date_to']);
        }

        if (isset($filters['asset_id']) && $filters['asset_id']) {
            $query->where('asset_movements.asset_id', $filters['asset_id']);
        }

        return $query->orderBy('asset_movements.movement_date', 'desc')->get();
    }

    /**
     * Generate Asset Summary Statistics
     */
    public function getAssetSummary()
    {
        $summary = [];

        // Total assets by status
        $summary['by_status'] = Asset::select('status', DB::raw('count(*) as count'), DB::raw('sum(acquisition_cost) as total_cost'))
            ->groupBy('status')
            ->get();

        // Total assets by category
        $summary['by_category'] = Asset::select('asset_categories.name as category_name', DB::raw('count(*) as count'), DB::raw('sum(assets.acquisition_cost) as total_cost'))
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->get();

        // Depreciation summary
        $summary['depreciation'] = [
            'total_depreciation' => Asset::sum('accumulated_depreciation'),
            'total_book_value' => Asset::sum('current_book_value'),
            'depreciable_assets' => Asset::where('is_depreciable', true)->count(),
            'fully_depreciated' => Asset::whereRaw('accumulated_depreciation >= depreciable_cost')->count(),
        ];

        // Recent disposals
        $summary['recent_disposals'] = AssetDisposal::with(['asset'])
            ->where('disposal_date', '>=', now()->subMonths(6))
            ->orderBy('disposal_date', 'desc')
            ->limit(10)
            ->get();

        // Recent movements
        $summary['recent_movements'] = AssetMovement::with(['asset'])
            ->where('movement_date', '>=', now()->subMonths(3))
            ->orderBy('movement_date', 'desc')
            ->limit(10)
            ->get();

        return $summary;
    }

    /**
     * Generate Asset Aging Report
     */
    public function getAssetAging()
    {
        return Asset::with(['category'])
            ->select([
                'assets.*',
                'asset_categories.name as category_name',
                DB::raw('DATEDIFF(CURDATE(), assets.acquisition_date) as days_owned'),
                DB::raw('ROUND(DATEDIFF(CURDATE(), assets.acquisition_date) / 365, 2) as years_owned')
            ])
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->where('assets.status', 'active')
            ->orderBy('assets.acquisition_date', 'asc')
            ->get();
    }

    /**
     * Generate Low Value Asset Report
     */
    public function getLowValueAssets($threshold = 1000000)
    {
        return Asset::with(['category'])
            ->where('acquisition_cost', '<=', $threshold)
            ->where('status', 'active')
            ->orderBy('acquisition_cost', 'asc')
            ->get();
    }

    /**
     * Generate Depreciation Run History
     */
    public function getDepreciationRunHistory($filters = [])
    {
        $query = AssetDepreciationRun::with(['creator', 'poster'])
            ->select([
                'asset_depreciation_runs.*',
                DB::raw('COUNT(asset_depreciation_entries.id) as entry_count'),
                DB::raw('SUM(asset_depreciation_entries.amount) as total_depreciation')
            ])
            ->leftJoin('asset_depreciation_entries', 'asset_depreciation_runs.id', '=', 'asset_depreciation_entries.run_id')
            ->groupBy('asset_depreciation_runs.id');

        // Apply filters
        if (isset($filters['status']) && $filters['status']) {
            $query->where('asset_depreciation_runs.status', $filters['status']);
        }

        if (isset($filters['period_from']) && $filters['period_from']) {
            $query->where('asset_depreciation_runs.period_start', '>=', $filters['period_from']);
        }

        if (isset($filters['period_to']) && $filters['period_to']) {
            $query->where('asset_depreciation_runs.period_end', '<=', $filters['period_to']);
        }

        return $query->orderBy('asset_depreciation_runs.period_start', 'desc')->get();
    }

    /**
     * Export data to CSV format
     */
    public function exportToCsv($data, $filename = 'asset_report.csv')
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            if (!empty($data)) {
                // Write headers
                fputcsv($file, array_keys($data[0]->toArray()));

                // Write data
                foreach ($data as $row) {
                    fputcsv($file, $row->toArray());
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Asset Register to Excel
     */
    public function exportAssetRegisterToExcel($filters = [])
    {
        $filename = 'asset_register_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new AssetRegisterExport($filters), $filename);
    }

    /**
     * Export Depreciation Schedule to Excel
     */
    public function exportDepreciationScheduleToExcel($filters = [])
    {
        $filename = 'depreciation_schedule_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new DepreciationScheduleExport($filters), $filename);
    }

    /**
     * Export Disposal Summary to Excel
     */
    public function exportDisposalSummaryToExcel($filters = [])
    {
        $filename = 'disposal_summary_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new DisposalSummaryExport($filters), $filename);
    }

    /**
     * Export Movement Log to Excel
     */
    public function exportMovementLogToExcel($filters = [])
    {
        $filename = 'movement_log_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new MovementLogExport($filters), $filename);
    }

    /**
     * Get report filters options
     */
    public function getFilterOptions()
    {
        return [
            'categories' => AssetCategory::orderBy('name')->get(),
            'funds' => \App\Models\Fund::orderBy('name')->get(),
            'projects' => \App\Models\Project::orderBy('name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'assets' => Asset::select('id', 'code', 'name')->orderBy('code')->get(),
            'disposal_types' => ['sale', 'scrap', 'donation', 'trade_in', 'other'],
            'movement_types' => ['transfer', 'relocation', 'custodian_change', 'maintenance', 'other'],
            'statuses' => [
                'assets' => ['active', 'inactive', 'disposed'],
                'disposals' => ['draft', 'posted', 'reversed'],
                'movements' => ['draft', 'approved', 'completed', 'cancelled'],
                'depreciation_runs' => ['draft', 'posted', 'reversed'],
            ]
        ];
    }
}
