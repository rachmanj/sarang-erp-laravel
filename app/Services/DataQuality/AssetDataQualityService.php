<?php

namespace App\Services\DataQuality;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class AssetDataQualityService
{
    public function getDuplicateAssets()
    {
        $duplicateNames = Asset::select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        $duplicateSerials = Asset::select('serial_number', DB::raw('COUNT(*) as count'))
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->groupBy('serial_number')
            ->having('count', '>', 1)
            ->get();

        $duplicateCodes = Asset::select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->having('count', '>', 1)
            ->get();

        return [
            'duplicate_names' => $duplicateNames,
            'duplicate_serials' => $duplicateSerials,
            'duplicate_codes' => $duplicateCodes,
        ];
    }

    public function getIncompleteAssets()
    {
        return Asset::where(function ($query) {
            $query->whereNull('description')
                ->orWhere('description', '')
                ->orWhereNull('serial_number')
                ->orWhere('serial_number', '')
                ->orWhereNull('business_partner_id')
                ->orWhereNull('project_id')
                ->orWhereNull('department_id')
                ->orWhereNull('placed_in_service_date');
        })->with(['category', 'vendor', 'project', 'department'])->get();
    }

    public function getDataQualityReport()
    {
        $totalAssets = Asset::count();

        $duplicates = $this->getDuplicateAssets();
        $duplicateNameCount = $duplicates['duplicate_names']->sum('count') - $duplicates['duplicate_names']->count();
        $duplicateSerialCount = $duplicates['duplicate_serials']->sum('count') - $duplicates['duplicate_serials']->count();
        $duplicateCodeCount = $duplicates['duplicate_codes']->sum('count') - $duplicates['duplicate_codes']->count();

        $incompleteAssets = $this->getIncompleteAssets();
        $incompleteCount = $incompleteAssets->count();

        $missingVendor = Asset::whereNull('business_partner_id')->count();
        $missingProject = Asset::whereNull('project_id')->count();
        $missingDepartment = Asset::whereNull('department_id')->count();

        $missingDescription = Asset::where(function ($query) {
            $query->whereNull('description')->orWhere('description', '');
        })->count();

        $missingSerialNumber = Asset::where(function ($query) {
            $query->whereNull('serial_number')->orWhere('serial_number', '');
        })->count();

        $missingServiceDate = Asset::whereNull('placed_in_service_date')->count();

        $negativeValues = Asset::where('acquisition_cost', '<', 0)
            ->orWhere('salvage_value', '<', 0)
            ->orWhere('current_book_value', '<', 0)
            ->orWhere('accumulated_depreciation', '<', 0)
            ->count();

        $invalidLifeMonths = Asset::where('life_months', '<=', 0)
            ->orWhere('life_months', '>', 600)
            ->count();

        $futureServiceDates = Asset::where('placed_in_service_date', '>', now())->count();

        $orphanedCategories = Asset::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('asset_categories')
                ->whereColumn('asset_categories.id', 'assets.category_id');
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

        $orphanedVendors = Asset::whereNotNull('business_partner_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('business_partners')
                    ->whereColumn('business_partners.id', 'assets.business_partner_id');
            })->count();

        return [
            'summary' => [
                'total_assets' => $totalAssets,
                'duplicate_issues' => $duplicateNameCount + $duplicateSerialCount + $duplicateCodeCount,
                'incomplete_issues' => $incompleteCount,
                'consistency_issues' => $negativeValues + $invalidLifeMonths + $futureServiceDates,
                'orphaned_issues' => $orphanedCategories + $orphanedProjects + $orphanedDepartments + $orphanedVendors,
                'total_issues' => $duplicateNameCount + $duplicateSerialCount + $duplicateCodeCount + $incompleteCount + $negativeValues + $invalidLifeMonths + $futureServiceDates + $orphanedCategories + $orphanedProjects + $orphanedDepartments + $orphanedVendors,
            ],
            'duplicates' => [
                'duplicate_names' => $duplicateNameCount,
                'duplicate_serials' => $duplicateSerialCount,
                'duplicate_codes' => $duplicateCodeCount,
                'duplicate_details' => $duplicates,
            ],
            'incomplete_data' => [
                'missing_description' => $missingDescription,
                'missing_serial_number' => $missingSerialNumber,
                'missing_service_date' => $missingServiceDate,
                'missing_vendor' => $missingVendor,
                'missing_project' => $missingProject,
                'missing_department' => $missingDepartment,
                'incomplete_assets' => $incompleteAssets,
            ],
            'consistency_issues' => [
                'negative_values' => $negativeValues,
                'invalid_life_months' => $invalidLifeMonths,
                'future_service_dates' => $futureServiceDates,
            ],
            'orphaned_records' => [
                'orphaned_categories' => $orphanedCategories,
                'orphaned_projects' => $orphanedProjects,
                'orphaned_departments' => $orphanedDepartments,
                'orphaned_vendors' => $orphanedVendors,
            ],
        ];
    }

    public function getDuplicateDetails($type, $value)
    {
        return match ($type) {
            'name' => Asset::where('name', $value)->with(['category', 'vendor', 'project', 'department'])->get(),
            'serial' => Asset::where('serial_number', $value)->with(['category', 'vendor', 'project', 'department'])->get(),
            'code' => Asset::where('code', $value)->with(['category', 'vendor', 'project', 'department'])->get(),
            default => collect(),
        };
    }

    public function getAssetsByIssue($issueType, $issueValue = null)
    {
        $with = ['category', 'vendor', 'project', 'department'];

        return match ($issueType) {
            'missing_description' => Asset::where(function ($query) {
                $query->whereNull('description')->orWhere('description', '');
            })->with($with)->get(),
            'missing_serial_number' => Asset::where(function ($query) {
                $query->whereNull('serial_number')->orWhere('serial_number', '');
            })->with($with)->get(),
            'missing_service_date' => Asset::whereNull('placed_in_service_date')->with($with)->get(),
            'missing_vendor' => Asset::whereNull('business_partner_id')->with($with)->get(),
            'missing_project' => Asset::whereNull('project_id')->with($with)->get(),
            'missing_department' => Asset::whereNull('department_id')->with($with)->get(),
            'negative_values' => Asset::where('acquisition_cost', '<', 0)
                ->orWhere('salvage_value', '<', 0)
                ->orWhere('current_book_value', '<', 0)
                ->orWhere('accumulated_depreciation', '<', 0)
                ->with($with)
                ->get(),
            'invalid_life_months' => Asset::where('life_months', '<=', 0)
                ->orWhere('life_months', '>', 600)
                ->with($with)
                ->get(),
            'future_service_dates' => Asset::where('placed_in_service_date', '>', now())->with($with)->get(),
            'orphaned_categories' => Asset::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('asset_categories')
                    ->whereColumn('asset_categories.id', 'assets.category_id');
            })->with($with)->get(),
            'orphaned_projects' => Asset::whereNotNull('project_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('projects')
                        ->whereColumn('projects.id', 'assets.project_id');
                })->with($with)->get(),
            'orphaned_departments' => Asset::whereNotNull('department_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('departments')
                        ->whereColumn('departments.id', 'assets.department_id');
                })->with($with)->get(),
            'orphaned_vendors' => Asset::whereNotNull('business_partner_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('business_partners')
                        ->whereColumn('business_partners.id', 'assets.business_partner_id');
                })->with($with)->get(),
            default => collect(),
        };
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
        $csv .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n\n";

        $csv .= "SUMMARY\n";
        $csv .= 'Total Assets,'.$report['summary']['total_assets']."\n";
        $csv .= 'Duplicate Issues,'.$report['summary']['duplicate_issues']."\n";
        $csv .= 'Incomplete Issues,'.$report['summary']['incomplete_issues']."\n";
        $csv .= 'Consistency Issues,'.$report['summary']['consistency_issues']."\n";
        $csv .= 'Orphaned Issues,'.$report['summary']['orphaned_issues']."\n";
        $csv .= 'Total Issues,'.$report['summary']['total_issues']."\n\n";

        $csv .= "DUPLICATE ANALYSIS\n";
        $csv .= 'Duplicate Names,'.$report['duplicates']['duplicate_names']."\n";
        $csv .= 'Duplicate Serial Numbers,'.$report['duplicates']['duplicate_serials']."\n";
        $csv .= 'Duplicate Codes,'.$report['duplicates']['duplicate_codes']."\n\n";

        $csv .= "INCOMPLETE DATA ANALYSIS\n";
        $csv .= 'Missing Description,'.$report['incomplete_data']['missing_description']."\n";
        $csv .= 'Missing Serial Number,'.$report['incomplete_data']['missing_serial_number']."\n";
        $csv .= 'Missing Service Date,'.$report['incomplete_data']['missing_service_date']."\n";
        $csv .= 'Missing Vendor,'.$report['incomplete_data']['missing_vendor']."\n";
        $csv .= 'Missing Project,'.$report['incomplete_data']['missing_project']."\n";
        $csv .= 'Missing Department,'.$report['incomplete_data']['missing_department']."\n\n";

        $csv .= "CONSISTENCY ISSUES\n";
        $csv .= 'Negative Values,'.$report['consistency_issues']['negative_values']."\n";
        $csv .= 'Invalid Life Months,'.$report['consistency_issues']['invalid_life_months']."\n";
        $csv .= 'Future Service Dates,'.$report['consistency_issues']['future_service_dates']."\n\n";

        $csv .= "ORPHANED RECORDS\n";
        $csv .= 'Orphaned Categories,'.$report['orphaned_records']['orphaned_categories']."\n";
        $csv .= 'Orphaned Projects,'.$report['orphaned_records']['orphaned_projects']."\n";
        $csv .= 'Orphaned Departments,'.$report['orphaned_records']['orphaned_departments']."\n";
        $csv .= 'Orphaned Vendors,'.$report['orphaned_records']['orphaned_vendors']."\n";

        return $csv;
    }
}
