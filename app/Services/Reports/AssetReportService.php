<?php

namespace App\Services\Reports;

use App\Exports\AssetAgingExport;
use App\Exports\AssetRegisterExport;
use App\Exports\DepreciationRunHistoryExport;
use App\Exports\DepreciationScheduleExport;
use App\Exports\DisposalSummaryExport;
use App\Exports\LowValueAssetsExport;
use App\Exports\MovementLogExport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationEntry;
use App\Models\AssetDepreciationRun;
use App\Models\AssetDisposal;
use App\Models\AssetMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetReportService
{
    public function getAssetRegister(array $filters = [], bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = $this->assetRegisterQuery($filters)->orderBy('assets.code');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function getAssetRegisterTotals(array $filters = []): array
    {
        $row = $this->assetRegisterQuery($filters)
            ->reorder()
            ->select([
                DB::raw('COUNT(assets.id) as count'),
                DB::raw('COALESCE(SUM(assets.acquisition_cost), 0) as acquisition_cost'),
                DB::raw('COALESCE(SUM(assets.accumulated_depreciation), 0) as accumulated_depreciation'),
                DB::raw('COALESCE(SUM(assets.current_book_value), 0) as current_book_value'),
            ])
            ->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'acquisition_cost' => (float) ($row->acquisition_cost ?? 0),
            'accumulated_depreciation' => (float) ($row->accumulated_depreciation ?? 0),
            'current_book_value' => (float) ($row->current_book_value ?? 0),
        ];
    }

    public function getDepreciationSchedule(array $filters = [], bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = $this->depreciationScheduleQuery($filters)
            ->orderBy('asset_depreciation_entries.period', 'desc')
            ->orderBy('assets.code');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function getDepreciationScheduleTotals(array $filters = []): array
    {
        $row = $this->depreciationScheduleQuery($filters)
            ->reorder()
            ->select([
                DB::raw('COUNT(asset_depreciation_entries.id) as count'),
                DB::raw('COALESCE(SUM(asset_depreciation_entries.amount), 0) as amount'),
            ])
            ->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'amount' => (float) ($row->amount ?? 0),
        ];
    }

    public function getDisposalSummary(array $filters = [], bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = $this->disposalSummaryQuery($filters)->orderBy('asset_disposals.disposal_date', 'desc');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function getDisposalSummaryTotals(array $filters = []): array
    {
        $row = $this->disposalSummaryQuery($filters)
            ->reorder()
            ->select([
                DB::raw('COUNT(asset_disposals.id) as count'),
                DB::raw('COALESCE(SUM(asset_disposals.disposal_proceeds), 0) as disposal_proceeds'),
                DB::raw('COALESCE(SUM(asset_disposals.book_value_at_disposal), 0) as book_value_at_disposal'),
                DB::raw('COALESCE(SUM(asset_disposals.gain_loss_amount), 0) as gain_loss_amount'),
            ])
            ->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'disposal_proceeds' => (float) ($row->disposal_proceeds ?? 0),
            'book_value_at_disposal' => (float) ($row->book_value_at_disposal ?? 0),
            'gain_loss_amount' => (float) ($row->gain_loss_amount ?? 0),
        ];
    }

    public function getMovementLog(array $filters = [], bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = $this->movementLogQuery($filters)->orderBy('asset_movements.movement_date', 'desc');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function getAssetSummary(): array
    {
        $summary = [];

        $summary['by_status'] = Asset::query()
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(acquisition_cost) as total_cost'))
            ->groupBy('status')
            ->get();

        $summary['by_category'] = Asset::query()
            ->select('asset_categories.name as category_name', DB::raw('count(*) as count'), DB::raw('sum(assets.acquisition_cost) as total_cost'))
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->get();

        $summary['depreciation'] = [
            'total_depreciation' => (float) Asset::query()->sum('accumulated_depreciation'),
            'total_book_value' => (float) Asset::query()->sum('current_book_value'),
            'depreciable_assets' => Asset::query()->depreciable()->count(),
            'fully_depreciated' => Asset::query()
                ->whereRaw('accumulated_depreciation >= (acquisition_cost - salvage_value)')
                ->whereRaw('(acquisition_cost - salvage_value) > 0')
                ->count(),
        ];

        $summary['recent_disposals'] = AssetDisposal::query()
            ->with(['asset'])
            ->where('disposal_date', '>=', now()->subMonths(6))
            ->orderBy('disposal_date', 'desc')
            ->limit(10)
            ->get();

        $summary['recent_movements'] = AssetMovement::query()
            ->with(['asset'])
            ->where('movement_date', '>=', now()->subMonths(3))
            ->orderBy('movement_date', 'desc')
            ->limit(10)
            ->get();

        return $summary;
    }

    public function getAssetAging(array $filters = [], bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = $this->assetAgingQuery($filters)->orderBy('assets.placed_in_service_date', 'asc');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function getLowValueAssets(float $threshold = 1000000, bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = Asset::query()
            ->with(['category'])
            ->where('acquisition_cost', '<=', $threshold)
            ->where('status', 'active')
            ->orderBy('acquisition_cost', 'asc');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function getDepreciationRunHistory(array $filters = [], bool $paginate = false): Collection|LengthAwarePaginator
    {
        $query = $this->depreciationRunHistoryQuery($filters)->orderBy('asset_depreciation_runs.period', 'desc');

        if ($paginate) {
            return $query->paginate(50)->withQueryString();
        }

        return $query->get();
    }

    public function exportToCsv(Collection $data, string $filename, array $columns): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_values($columns));

            foreach ($data as $row) {
                $line = [];
                foreach (array_keys($columns) as $key) {
                    $line[] = data_get($row, $key, '');
                }
                fputcsv($file, $line);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportAssetRegisterToExcel(array $filters = [])
    {
        $filename = 'asset_register_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new AssetRegisterExport($filters), $filename);
    }

    public function exportDepreciationScheduleToExcel(array $filters = [])
    {
        $filename = 'depreciation_schedule_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new DepreciationScheduleExport($filters), $filename);
    }

    public function exportDisposalSummaryToExcel(array $filters = [])
    {
        $filename = 'disposal_summary_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new DisposalSummaryExport($filters), $filename);
    }

    public function exportMovementLogToExcel(array $filters = [])
    {
        $filename = 'movement_log_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new MovementLogExport($filters), $filename);
    }

    public function exportAssetAgingToExcel(array $filters = [])
    {
        $filename = 'asset_aging_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new AssetAgingExport($filters), $filename);
    }

    public function exportLowValueAssetsToExcel(float $threshold = 1000000)
    {
        $filename = 'low_value_assets_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new LowValueAssetsExport($threshold), $filename);
    }

    public function exportDepreciationRunHistoryToExcel(array $filters = [])
    {
        $filename = 'depreciation_run_history_'.date('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new DepreciationRunHistoryExport($filters), $filename);
    }

    public function getFilterOptions(): array
    {
        return [
            'categories' => AssetCategory::query()->orderBy('name')->get(),
            'projects' => \App\Models\Dimensions\Project::query()->orderBy('name')->get(),
            'departments' => \App\Models\Dimensions\Department::query()->orderBy('name')->get(),
            'assets' => Asset::query()->select('id', 'code', 'name')->orderBy('code')->get(),
            'disposal_types' => ['sale', 'scrap', 'donation', 'trade_in', 'other'],
            'movement_types' => ['transfer', 'relocation', 'custodian_change', 'maintenance', 'other'],
            'statuses' => [
                'assets' => ['active', 'retired', 'disposed'],
                'disposals' => ['draft', 'posted', 'reversed'],
                'movements' => ['draft', 'approved', 'completed', 'cancelled'],
                'depreciation_runs' => ['draft', 'posted', 'reversed'],
                'depreciation_entries' => ['draft', 'posted'],
            ],
        ];
    }

    public function csvColumnsFor(string $reportType): array
    {
        return match ($reportType) {
            'asset_register' => [
                'code' => 'Asset Code',
                'name' => 'Asset Name',
                'category_name' => 'Category',
                'project_name' => 'Project',
                'department_name' => 'Department',
                'vendor_name' => 'Vendor',
                'placed_in_service_date' => 'Placed in Service',
                'acquisition_cost' => 'Acquisition Cost',
                'accumulated_depreciation' => 'Accumulated Depreciation',
                'current_book_value' => 'Book Value',
                'status' => 'Status',
            ],
            'depreciation_schedule' => [
                'period' => 'Period',
                'asset_code' => 'Asset Code',
                'asset_name' => 'Asset Name',
                'category_name' => 'Category',
                'amount' => 'Amount',
                'book' => 'Book',
                'entry_status' => 'Status',
            ],
            'disposal_summary' => [
                'disposal_no' => 'Disposal No',
                'asset_code' => 'Asset Code',
                'asset_name' => 'Asset Name',
                'category_name' => 'Category',
                'disposal_date' => 'Disposal Date',
                'disposal_type' => 'Type',
                'disposal_proceeds' => 'Proceeds',
                'book_value_at_disposal' => 'Book Value',
                'gain_loss_amount' => 'Gain/Loss',
                'status' => 'Status',
            ],
            'movement_log' => [
                'asset_code' => 'Asset Code',
                'asset_name' => 'Asset Name',
                'movement_date' => 'Date',
                'movement_type' => 'Type',
                'from_location' => 'From Location',
                'to_location' => 'To Location',
                'from_custodian' => 'From Custodian',
                'to_custodian' => 'To Custodian',
                'status' => 'Status',
            ],
            'asset_aging' => [
                'code' => 'Asset Code',
                'name' => 'Asset Name',
                'category_name' => 'Category',
                'placed_in_service_date' => 'Placed in Service',
                'years_owned' => 'Years Owned',
                'days_owned' => 'Days Owned',
                'acquisition_cost' => 'Acquisition Cost',
                'current_book_value' => 'Book Value',
            ],
            'low_value_assets' => [
                'code' => 'Asset Code',
                'name' => 'Asset Name',
                'category.name' => 'Category',
                'placed_in_service_date' => 'Placed in Service',
                'acquisition_cost' => 'Acquisition Cost',
                'current_book_value' => 'Book Value',
                'status' => 'Status',
            ],
            'depreciation_run_history' => [
                'period' => 'Period',
                'status' => 'Status',
                'asset_count' => 'Asset Count',
                'entry_count' => 'Entry Count',
                'total_depreciation' => 'Total Depreciation',
                'posted_at' => 'Posted At',
            ],
            default => [],
        };
    }

    protected function assetRegisterQuery(array $filters)
    {
        $query = Asset::query()
            ->select([
                'assets.*',
                'asset_categories.name as category_name',
                'projects.name as project_name',
                'departments.name as department_name',
                'business_partners.name as vendor_name',
            ])
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->leftJoin('projects', 'assets.project_id', '=', 'projects.id')
            ->leftJoin('departments', 'assets.department_id', '=', 'departments.id')
            ->leftJoin('business_partners', 'assets.business_partner_id', '=', 'business_partners.id');

        if (! empty($filters['category_id'])) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        if (! empty($filters['project_id'])) {
            $query->where('assets.project_id', $filters['project_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('assets.department_id', $filters['department_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('assets.status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('assets.placed_in_service_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('assets.placed_in_service_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    protected function depreciationScheduleQuery(array $filters)
    {
        $query = AssetDepreciationEntry::query()
            ->select([
                'asset_depreciation_entries.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name',
                'asset_depreciation_runs.status as run_status',
                DB::raw("CASE WHEN asset_depreciation_entries.journal_id IS NULL THEN 'draft' ELSE 'posted' END as entry_status"),
            ])
            ->join('assets', 'asset_depreciation_entries.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->leftJoin('asset_depreciation_runs', 'asset_depreciation_entries.period', '=', 'asset_depreciation_runs.period');

        if (! empty($filters['asset_id'])) {
            $query->where('asset_depreciation_entries.asset_id', $filters['asset_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        if (! empty($filters['period_from'])) {
            $query->where('asset_depreciation_entries.period', '>=', $filters['period_from']);
        }

        if (! empty($filters['period_to'])) {
            $query->where('asset_depreciation_entries.period', '<=', $filters['period_to']);
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'posted') {
                $query->whereNotNull('asset_depreciation_entries.journal_id');
            } elseif ($filters['status'] === 'draft') {
                $query->whereNull('asset_depreciation_entries.journal_id');
            }
        }

        return $query;
    }

    protected function disposalSummaryQuery(array $filters)
    {
        $query = AssetDisposal::query()
            ->select([
                'asset_disposals.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name',
            ])
            ->join('assets', 'asset_disposals.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        if (! empty($filters['disposal_type'])) {
            $query->where('asset_disposals.disposal_type', $filters['disposal_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('asset_disposals.status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('asset_disposals.disposal_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('asset_disposals.disposal_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        return $query;
    }

    protected function movementLogQuery(array $filters)
    {
        $query = AssetMovement::query()
            ->select([
                'asset_movements.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name',
            ])
            ->join('assets', 'asset_movements.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        if (! empty($filters['movement_type'])) {
            $query->where('asset_movements.movement_type', $filters['movement_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('asset_movements.status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('asset_movements.movement_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('asset_movements.movement_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['asset_id'])) {
            $query->where('asset_movements.asset_id', $filters['asset_id']);
        }

        return $query;
    }

    protected function assetAgingQuery(array $filters = [])
    {
        $query = Asset::query()
            ->select([
                'assets.*',
                'asset_categories.name as category_name',
                DB::raw('DATEDIFF(CURDATE(), assets.placed_in_service_date) as days_owned'),
                DB::raw('ROUND(DATEDIFF(CURDATE(), assets.placed_in_service_date) / 365, 2) as years_owned'),
            ])
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->where('assets.status', 'active')
            ->whereNotNull('assets.placed_in_service_date');

        if (! empty($filters['category_id'])) {
            $query->where('assets.category_id', $filters['category_id']);
        }

        return $query;
    }

    protected function depreciationRunHistoryQuery(array $filters)
    {
        $query = AssetDepreciationRun::query()
            ->select([
                'asset_depreciation_runs.*',
                DB::raw('(SELECT COUNT(*) FROM asset_depreciation_entries WHERE asset_depreciation_entries.period = asset_depreciation_runs.period) as entry_count'),
            ]);

        if (! empty($filters['status'])) {
            $query->where('asset_depreciation_runs.status', $filters['status']);
        }

        if (! empty($filters['period_from'])) {
            $query->where('asset_depreciation_runs.period', '>=', $filters['period_from']);
        }

        if (! empty($filters['period_to'])) {
            $query->where('asset_depreciation_runs.period', '<=', $filters['period_to']);
        }

        return $query;
    }
}
