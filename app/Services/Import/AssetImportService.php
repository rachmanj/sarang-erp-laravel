<?php

namespace App\Services\Import;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Fund;
use App\Models\Project;
use App\Models\Department;
use App\Models\Vendor;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AssetImportService
{
    protected $errors = [];
    protected $warnings = [];
    protected $importedCount = 0;
    protected $skippedCount = 0;

    public function importFromCsv($filePath, $options = [])
    {
        $this->resetCounters();

        try {
            $data = $this->readCsvFile($filePath);
            $validatedData = $this->validateImportData($data);
            $processedData = $this->processImportData($validatedData, $options);

            return $this->createAssets($processedData);
        } catch (\Exception $e) {
            $this->errors[] = "Import failed: " . $e->getMessage();
            return false;
        }
    }

    protected function readCsvFile($filePath)
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \Exception("Could not open CSV file");
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \Exception("CSV file is empty or invalid");
        }

        $expectedHeaders = $this->getExpectedHeaders();
        $this->validateHeaders($headers, $expectedHeaders);

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($handle);
        return $data;
    }

    protected function getExpectedHeaders()
    {
        return [
            'code',
            'name',
            'description',
            'serial_number',
            'category_code',
            'acquisition_cost',
            'salvage_value',
            'method',
            'life_months',
            'placed_in_service_date',
            'fund_code',
            'project_code',
            'department_code',
            'vendor_code',
            'purchase_invoice_number'
        ];
    }

    protected function validateHeaders($headers, $expectedHeaders)
    {
        $missingHeaders = array_diff($expectedHeaders, $headers);
        if (!empty($missingHeaders)) {
            throw new \Exception("Missing required headers: " . implode(', ', $missingHeaders));
        }
    }

    protected function validateImportData($data)
    {
        $validatedData = [];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

            $validator = Validator::make($row, $this->getValidationRules(), $this->getValidationMessages());

            if ($validator->fails()) {
                $this->errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $this->skippedCount++;
                continue;
            }

            $validatedData[] = $validator->validated();
        }

        return $validatedData;
    }

    protected function getValidationRules()
    {
        return [
            'code' => 'required|string|max:50|unique:assets,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'serial_number' => 'nullable|string|max:100',
            'category_code' => 'required|string|exists:asset_categories,code',
            'acquisition_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'method' => 'required|in:straight_line,declining_balance,double_declining_balance',
            'life_months' => 'required|integer|min:1|max:600',
            'placed_in_service_date' => 'required|date',
            'fund_code' => 'nullable|string|exists:funds,code',
            'project_code' => 'nullable|string|exists:projects,code',
            'department_code' => 'nullable|string|exists:departments,code',
            'vendor_code' => 'nullable|string|exists:vendors,code',
            'purchase_invoice_number' => 'nullable|string|exists:purchase_invoices,invoice_number'
        ];
    }

    protected function getValidationMessages()
    {
        return [
            'code.required' => 'Asset code is required',
            'code.unique' => 'Asset code already exists',
            'name.required' => 'Asset name is required',
            'category_code.required' => 'Category code is required',
            'category_code.exists' => 'Category code does not exist',
            'acquisition_cost.required' => 'Acquisition cost is required',
            'acquisition_cost.numeric' => 'Acquisition cost must be a number',
            'method.required' => 'Depreciation method is required',
            'method.in' => 'Depreciation method must be one of: straight_line, declining_balance, double_declining_balance',
            'life_months.required' => 'Life in months is required',
            'life_months.integer' => 'Life in months must be an integer',
            'placed_in_service_date.required' => 'Placed in service date is required',
            'placed_in_service_date.date' => 'Placed in service date must be a valid date',
            'fund_code.exists' => 'Fund code does not exist',
            'project_code.exists' => 'Project code does not exist',
            'department_code.exists' => 'Department code does not exist',
            'vendor_code.exists' => 'Vendor code does not exist',
            'purchase_invoice_number.exists' => 'Purchase invoice number does not exist'
        ];
    }

    protected function processImportData($validatedData, $options)
    {
        $processedData = [];

        foreach ($validatedData as $row) {
            $processedRow = $this->enrichRowData($row, $options);
            $processedData[] = $processedRow;
        }

        return $processedData;
    }

    protected function enrichRowData($row, $options)
    {
        // Resolve foreign key relationships
        $category = AssetCategory::where('code', $row['category_code'])->first();
        $fund = $row['fund_code'] ? Fund::where('code', $row['fund_code'])->first() : null;
        $project = $row['project_code'] ? Project::where('code', $row['project_code'])->first() : null;
        $department = $row['department_code'] ? Department::where('code', $row['department_code'])->first() : null;
        $vendor = $row['vendor_code'] ? Vendor::where('code', $row['vendor_code'])->first() : null;
        $purchaseInvoice = $row['purchase_invoice_number'] ? PurchaseInvoice::where('invoice_number', $row['purchase_invoice_number'])->first() : null;

        // Set defaults from category if not provided
        if (!$row['salvage_value'] && $category) {
            $row['salvage_value'] = $category->salvage_value_policy === 'percentage'
                ? $row['acquisition_cost'] * ($category->salvage_value_percentage ?? 0) / 100
                : $category->salvage_value_default ?? 0;
        }

        if (!$row['method'] && $category) {
            $row['method'] = $category->method_default ?? 'straight_line';
        }

        if (!$row['life_months'] && $category) {
            $row['life_months'] = $category->life_months_default ?? 60;
        }

        return [
            'code' => $row['code'],
            'name' => $row['name'],
            'description' => $row['description'],
            'serial_number' => $row['serial_number'],
            'category_id' => $category->id,
            'acquisition_cost' => $row['acquisition_cost'],
            'salvage_value' => $row['salvage_value'],
            'method' => $row['method'],
            'life_months' => $row['life_months'],
            'placed_in_service_date' => Carbon::parse($row['placed_in_service_date']),
            'fund_id' => $fund?->id,
            'project_id' => $project?->id,
            'department_id' => $department?->id,
            'vendor_id' => $vendor?->id,
            'purchase_invoice_id' => $purchaseInvoice?->id,
            'status' => 'active',
            'current_book_value' => $row['acquisition_cost'],
            'accumulated_depreciation' => 0
        ];
    }

    protected function createAssets($processedData)
    {
        DB::beginTransaction();

        try {
            foreach ($processedData as $assetData) {
                Asset::create($assetData);
                $this->importedCount++;
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            $this->errors[] = "Database error: " . $e->getMessage();
            return false;
        }
    }

    public function generateTemplate()
    {
        $headers = $this->getExpectedHeaders();
        $sampleData = $this->getSampleData();

        $csv = implode(',', $headers) . "\n";
        foreach ($sampleData as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    protected function getSampleData()
    {
        return [
            [
                'COMP-001',
                'Dell Laptop',
                'Dell Inspiron 15 3000 Series',
                'DL123456789',
                'COMPUTER',
                '8000000',
                '800000',
                'straight_line',
                '36',
                '2025-01-15',
                'GENERAL',
                'PROJ-001',
                'IT',
                'VENDOR-001',
                'PI-2025-001'
            ],
            [
                'FURN-001',
                'Office Desk',
                'Executive Office Desk',
                '',
                'FURNITURE',
                '2500000',
                '250000',
                'straight_line',
                '60',
                '2025-01-20',
                'GENERAL',
                '',
                'ADMIN',
                'VENDOR-002',
                ''
            ]
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    protected function resetCounters()
    {
        $this->errors = [];
        $this->warnings = [];
        $this->importedCount = 0;
        $this->skippedCount = 0;
    }

    public function validateFile($filePath)
    {
        try {
            $data = $this->readCsvFile($filePath);
            $validatedData = $this->validateImportData($data);

            return [
                'valid' => empty($this->errors),
                'errors' => $this->errors,
                'warnings' => $this->warnings,
                'total_rows' => count($data),
                'valid_rows' => count($validatedData),
                'invalid_rows' => count($data) - count($validatedData)
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
                'warnings' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0
            ];
        }
    }
}
