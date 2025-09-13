<?php

namespace App\Services\DataQuality;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Fund;
use App\Models\Project;
use App\Models\Department;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AssetDataQualityService
{
    public function getDuplicateAssets()
    {
        // Find assets with duplicate names
        $duplicateNames = Asset::select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        // Find assets with duplicate serial numbers
        $duplicateSerials = Asset::select('serial_number', DB::raw('COUNT(*) as count'))
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->groupBy('serial_number')
            ->having('count', '>', 1)
            ->get();

        // Find assets with duplicate codes
        $duplicateCodes = Asset::select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->having('count', '>', 1)
            ->get();

        return [
            'duplicate_names' => $duplicateNames,
            'duplicate_serials' => $duplicateSerials,
            'duplicate_codes' => $duplicateCodes
        ];
    }

    public function getIncompleteAssets()
    {
        $incompleteAssets = Asset::where(function ($query) {
            $query->whereNull('description')
                ->orWhere('description', '')
                ->orWhereNull('serial_number')
                ->orWhere('serial_number', '')
                ->orWhereNull('vendor_id')
                ->orWhereNull('fund_id')
                ->orWhereNull('project_id')
                ->orWhereNull('department_id')
                ->orWhereNull('placed_in_service_date');
        })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

        return $incompleteAssets;
    }

    public function getDataQualityReport()
    {
        $totalAssets = Asset::count();

        // Duplicate analysis
        $duplicates = $this->getDuplicateAssets();
        $duplicateNameCount = $duplicates['duplicate_names']->sum('count') - $duplicates['duplicate_names']->count();
        $duplicateSerialCount = $duplicates['duplicate_serials']->sum('count') - $duplicates['duplicate_serials']->count();
        $duplicateCodeCount = $duplicates['duplicate_codes']->sum('count') - $duplicates['duplicate_codes']->count();

        // Incomplete data analysis
        $incompleteAssets = $this->getIncompleteAssets();
        $incompleteCount = $incompleteAssets->count();

        // Missing relationships analysis
        $missingVendor = Asset::whereNull('vendor_id')->count();
        $missingFund = Asset::whereNull('fund_id')->count();
        $missingProject = Asset::whereNull('project_id')->count();
        $missingDepartment = Asset::whereNull('department_id')->count();

        // Missing critical fields
        $missingDescription = Asset::where(function ($query) {
            $query->whereNull('description')->orWhere('description', '');
        })->count();

        $missingSerialNumber = Asset::where(function ($query) {
            $query->whereNull('serial_number')->orWhere('serial_number', '');
        })->count();

        $missingServiceDate = Asset::whereNull('placed_in_service_date')->count();

        // Data consistency checks
        $negativeValues = Asset::where('acquisition_cost', '<', 0)
            ->orWhere('salvage_value', '<', 0)
            ->orWhere('current_book_value', '<', 0)
            ->orWhere('accumulated_depreciation', '<', 0)
            ->count();

        $invalidLifeMonths = Asset::where('life_months', '<=', 0)
            ->orWhere('life_months', '>', 600)
            ->count();

        $futureServiceDates = Asset::where('placed_in_service_date', '>', now())->count();

        // Orphaned records
        $orphanedCategories = Asset::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('asset_categories')
                ->whereColumn('asset_categories.id', 'assets.category_id');
        })->count();

        $orphanedFunds = Asset::whereNotNull('fund_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('funds')
                    ->whereColumn('funds.id', 'assets.fund_id');
            })->count();

        $orphanedProjects = Asset::whereNotNull('project_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('projects')
                    ->whereColumn('projects.id', 'assets.project_id');
            })->count();

        $orphanedDepartments = Asset::whereNotNull('department_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('departments')
                    ->whereColumn('departments.id', 'assets.department_id');
            })->count();

        $orphanedVendors = Asset::whereNotNull('vendor_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vendors')
                    ->whereColumn('vendors.id', 'assets.vendor_id');
            })->count();

        return [
            'summary' => [
                'total_assets' => $totalAssets,
                'duplicate_issues' => $duplicateNameCount + $duplicateSerialCount + $duplicateCodeCount,
                'incomplete_issues' => $incompleteCount,
                'consistency_issues' => $negativeValues + $invalidLifeMonths + $futureServiceDates,
                'orphaned_issues' => $orphanedCategories + $orphanedFunds + $orphanedProjects + $orphanedDepartments + $orphanedVendors,
                'total_issues' => $duplicateNameCount + $duplicateSerialCount + $duplicateCodeCount + $incompleteCount + $negativeValues + $invalidLifeMonths + $futureServiceDates + $orphanedCategories + $orphanedFunds + $orphanedProjects + $orphanedDepartments + $orphanedVendors
            ],
            'duplicates' => [
                'duplicate_names' => $duplicateNameCount,
                'duplicate_serials' => $duplicateSerialCount,
                'duplicate_codes' => $duplicateCodeCount,
                'duplicate_details' => $duplicates
            ],
            'incomplete_data' => [
                'missing_description' => $missingDescription,
                'missing_serial_number' => $missingSerialNumber,
                'missing_service_date' => $missingServiceDate,
                'missing_vendor' => $missingVendor,
                'missing_fund' => $missingFund,
                'missing_project' => $missingProject,
                'missing_department' => $missingDepartment,
                'incomplete_assets' => $incompleteAssets
            ],
            'consistency_issues' => [
                'negative_values' => $negativeValues,
                'invalid_life_months' => $invalidLifeMonths,
                'future_service_dates' => $futureServiceDates
            ],
            'orphaned_records' => [
                'orphaned_categories' => $orphanedCategories,
                'orphaned_funds' => $orphanedFunds,
                'orphaned_projects' => $orphanedProjects,
                'orphaned_departments' => $orphanedDepartments,
                'orphaned_vendors' => $orphanedVendors
            ]
        ];
    }

    public function getDuplicateDetails($type, $value)
    {
        switch ($type) {
            case 'name':
                return Asset::where('name', $value)->with(['category', 'vendor', 'fund', 'project', 'department'])->get();
            case 'serial':
                return Asset::where('serial_number', $value)->with(['category', 'vendor', 'fund', 'project', 'department'])->get();
            case 'code':
                return Asset::where('code', $value)->with(['category', 'vendor', 'fund', 'project', 'department'])->get();
            default:
                return collect();
        }
    }

    public function getAssetsByIssue($issueType, $issueValue = null)
    {
        switch ($issueType) {
            case 'missing_description':
                return Asset::where(function ($query) {
                    $query->whereNull('description')->orWhere('description', '');
                })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'missing_serial_number':
                return Asset::where(function ($query) {
                    $query->whereNull('serial_number')->orWhere('serial_number', '');
                })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'missing_service_date':
                return Asset::whereNull('placed_in_service_date')->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'missing_vendor':
                return Asset::whereNull('vendor_id')->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'missing_fund':
                return Asset::whereNull('fund_id')->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'missing_project':
                return Asset::whereNull('project_id')->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'missing_department':
                return Asset::whereNull('department_id')->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'negative_values':
                return Asset::where('acquisition_cost', '<', 0)
                    ->orWhere('salvage_value', '<', 0)
                    ->orWhere('current_book_value', '<', 0)
                    ->orWhere('accumulated_depreciation', '<', 0)
                    ->with(['category', 'vendor', 'fund', 'project', 'department'])
                    ->get();

            case 'invalid_life_months':
                return Asset::where('life_months', '<=', 0)
                    ->orWhere('life_months', '>', 600)
                    ->with(['category', 'vendor', 'fund', 'project', 'department'])
                    ->get();

            case 'future_service_dates':
                return Asset::where('placed_in_service_date', '>', now())->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'orphaned_categories':
                return Asset::whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('asset_categories')
                        ->whereColumn('asset_categories.id', 'assets.category_id');
                })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'orphaned_funds':
                return Asset::whereNotNull('fund_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('funds')
                            ->whereColumn('funds.id', 'assets.fund_id');
                    })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'orphaned_projects':
                return Asset::whereNotNull('project_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('projects')
                            ->whereColumn('projects.id', 'assets.project_id');
                    })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'orphaned_departments':
                return Asset::whereNotNull('department_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('departments')
                            ->whereColumn('departments.id', 'assets.department_id');
                    })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            case 'orphaned_vendors':
                return Asset::whereNotNull('vendor_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('vendors')
                            ->whereColumn('vendors.id', 'assets.vendor_id');
                    })->with(['category', 'vendor', 'fund', 'project', 'department'])->get();

            default:
                return collect();
        }
    }

    public function getDataQualityScore()
    {
        $report = $this->getDataQualityReport();
        $totalAssets = $report['summary']['total_assets'];
        $totalIssues = $report['summary']['total_issues'];

        if ($totalAssets == 0) {
            return 100;
        }

        $score = max(0, 100 - (($totalIssues / $totalAssets) * 100));
        return round($score, 2);
    }

    public function exportDataQualityReport($format = 'csv')
    {
        $report = $this->getDataQualityReport();

        if ($format === 'csv') {
            return $this->exportToCsv($report);
        }

        return $report;
    }

    protected function exportToCsv($report)
    {
        $csv = "Data Quality Report\n";
        $csv .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        $csv .= "SUMMARY\n";
        $csv .= "Total Assets," . $report['summary']['total_assets'] . "\n";
        $csv .= "Duplicate Issues," . $report['summary']['duplicate_issues'] . "\n";
        $csv .= "Incomplete Issues," . $report['summary']['incomplete_issues'] . "\n";
        $csv .= "Consistency Issues," . $report['summary']['consistency_issues'] . "\n";
        $csv .= "Orphaned Issues," . $report['summary']['orphaned_issues'] . "\n";
        $csv .= "Total Issues," . $report['summary']['total_issues'] . "\n\n";

        $csv .= "DUPLICATE ANALYSIS\n";
        $csv .= "Duplicate Names," . $report['duplicates']['duplicate_names'] . "\n";
        $csv .= "Duplicate Serial Numbers," . $report['duplicates']['duplicate_serials'] . "\n";
        $csv .= "Duplicate Codes," . $report['duplicates']['duplicate_codes'] . "\n\n";

        $csv .= "INCOMPLETE DATA ANALYSIS\n";
        $csv .= "Missing Description," . $report['incomplete_data']['missing_description'] . "\n";
        $csv .= "Missing Serial Number," . $report['incomplete_data']['missing_serial_number'] . "\n";
        $csv .= "Missing Service Date," . $report['incomplete_data']['missing_service_date'] . "\n";
        $csv .= "Missing Vendor," . $report['incomplete_data']['missing_vendor'] . "\n";
        $csv .= "Missing Fund," . $report['incomplete_data']['missing_fund'] . "\n";
        $csv .= "Missing Project," . $report['incomplete_data']['missing_project'] . "\n";
        $csv .= "Missing Department," . $report['incomplete_data']['missing_department'] . "\n\n";

        $csv .= "CONSISTENCY ISSUES\n";
        $csv .= "Negative Values," . $report['consistency_issues']['negative_values'] . "\n";
        $csv .= "Invalid Life Months," . $report['consistency_issues']['invalid_life_months'] . "\n";
        $csv .= "Future Service Dates," . $report['consistency_issues']['future_service_dates'] . "\n\n";

        $csv .= "ORPHANED RECORDS\n";
        $csv .= "Orphaned Categories," . $report['orphaned_records']['orphaned_categories'] . "\n";
        $csv .= "Orphaned Funds," . $report['orphaned_records']['orphaned_funds'] . "\n";
        $csv .= "Orphaned Projects," . $report['orphaned_records']['orphaned_projects'] . "\n";
        $csv .= "Orphaned Departments," . $report['orphaned_records']['orphaned_departments'] . "\n";
        $csv .= "Orphaned Vendors," . $report['orphaned_records']['orphaned_vendors'] . "\n";

        return $csv;
    }
}
