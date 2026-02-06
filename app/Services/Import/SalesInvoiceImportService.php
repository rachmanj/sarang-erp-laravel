<?php

namespace App\Services\Import;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\Accounting\Account;
use App\Models\BusinessPartner;
use App\Models\Master\TaxCode;
use App\Services\DocumentNumberingService;
use App\Services\CompanyEntityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SalesInvoiceImportService
{
    protected $errors = [];
    protected $warnings = [];
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $postingDate;

    public function __construct(
        private DocumentNumberingService $documentNumberingService,
        private CompanyEntityService $companyEntityService
    ) {
        $this->postingDate = Carbon::create(2026, 1, 1);
    }

    public function importFromExcel($filePath, $options = [])
    {
        $this->resetCounters();

        try {
            $data = Excel::toArray([], $filePath);

            if (empty($data) || empty($data[0])) {
                throw new \Exception('Excel file is empty or invalid');
            }

            $sheetData = $data[0];
            if (empty($sheetData) || count($sheetData) < 2) {
                throw new \Exception('Excel file must contain at least a header row and one data row');
            }

            // First row is headers, rest are data
            $headers = array_map('strtolower', array_map('trim', $sheetData[0]));
            $rows = [];
            for ($i = 1; $i < count($sheetData); $i++) {
                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = $sheetData[$i][$idx] ?? null;
                }
                $rows[] = $row;
            }

            $validatedData = $this->validateImportData($rows);
            $processedData = $this->processImportData($validatedData, $options);

            return $this->createSalesInvoices($processedData);
        } catch (\Exception $e) {
            $this->errors[] = "Import failed: " . $e->getMessage();
            return false;
        }
    }

    protected function validateImportData($data)
    {
        $validatedData = [];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

            // Normalize column names (handle spaces, case-insensitive)
            $normalizedRow = [];
            foreach ($row as $key => $value) {
                $normalizedKey = strtolower(trim(str_replace(' ', '_', $key)));
                // Convert empty strings to null for optional fields (especially tax_code)
                if ($key === 'Tax Code' || $normalizedKey === 'tax_code') {
                    $normalizedRow[$normalizedKey] = (trim($value ?? '') === '') ? null : trim($value);
                } else {
                    $normalizedRow[$normalizedKey] = ($value === '' || $value === null) ? null : $value;
                }
            }

            // Skip empty rows
            if (empty(array_filter($normalizedRow, function($v) { return $v !== null && $v !== ''; }))) {
                continue;
            }

            // Custom validation for tax_code - only validate exists if provided
            $rules = $this->getValidationRules();
            if (empty($normalizedRow['tax_code'])) {
                // If tax_code is empty, remove exists validation
                $rules['tax_code'] = 'nullable';
            }

            $validator = Validator::make($normalizedRow, $rules, $this->getValidationMessages());

            if ($validator->fails()) {
                $this->errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $this->skippedCount++;
                continue;
            }

            $validatedData[] = array_merge($normalizedRow, ['_row_number' => $rowNumber]);
        }

        return $validatedData;
    }

    protected function getValidationRules()
    {
        return [
            'customer_code' => 'required|string|exists:business_partners,code',
            'document_date' => 'required|date',
            'due_date' => 'required|date',
            'reference_no' => 'nullable|string|max:255',
            'delivery_order_no' => 'nullable|string|max:255',
            'account_code' => 'required|string|exists:accounts,code',
            'description' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'tax_code' => 'nullable|string|exists:tax_codes,code',
        ];
    }

    protected function getValidationMessages()
    {
        return [
            'customer_code.required' => 'Customer code is required',
            'customer_code.exists' => 'Customer code does not exist',
            'document_date.required' => 'Document date is required',
            'document_date.date' => 'Document date must be a valid date',
            'due_date.required' => 'Due date is required',
            'due_date.date' => 'Due date must be a valid date',
            'account_code.required' => 'Account code is required',
            'account_code.exists' => 'Account code does not exist',
            'qty.required' => 'Quantity is required',
            'qty.numeric' => 'Quantity must be a number',
            'qty.min' => 'Quantity must be greater than 0',
            'unit_price.required' => 'Unit price is required',
            'unit_price.numeric' => 'Unit price must be a number',
            'tax_code.exists' => 'Tax code does not exist',
        ];
    }

    protected function processImportData($validatedData, $options)
    {
        $grouped = [];
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        foreach ($validatedData as $row) {
            // Group by customer, document_date, due_date, reference_no, delivery_order_no
            $key = sprintf(
                '%s|%s|%s|%s|%s',
                $row['customer_code'],
                $row['document_date'],
                $row['due_date'],
                $row['reference_no'] ?? '',
                $row['delivery_order_no'] ?? ''
            );

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'customer_code' => $row['customer_code'],
                    'document_date' => $row['document_date'],
                    'due_date' => $row['due_date'],
                    'reference_no' => $row['reference_no'] ?? null,
                    'delivery_order_no' => $row['delivery_order_no'] ?? null,
                    'company_entity_id' => $options['company_entity_id'] ?? $defaultEntity->id,
                    'lines' => [],
                ];
            }

            $grouped[$key]['lines'][] = [
                'account_code' => $row['account_code'],
                'description' => $row['description'] ?? null,
                'qty' => (float) $row['qty'],
                'unit_price' => (float) $row['unit_price'],
                'tax_code' => $row['tax_code'] ?? null,
            ];
        }

        return array_values($grouped);
    }

    protected function createSalesInvoices($processedData)
    {
        return DB::transaction(function () use ($processedData) {
            foreach ($processedData as $invoiceData) {
                try {
                    $customer = BusinessPartner::where('code', $invoiceData['customer_code'])
                        ->where('partner_type', 'customer')
                        ->first();

                    if (!$customer) {
                        $this->errors[] = "Customer with code '{$invoiceData['customer_code']}' not found or is not a customer";
                        $this->skippedCount++;
                        continue;
                    }

                    // Build description with delivery order no if provided
                    $description = null;
                    if (!empty($invoiceData['delivery_order_no'])) {
                        $description = 'DO: ' . $invoiceData['delivery_order_no'];
                    }

                    // Create invoice
                    $invoice = SalesInvoice::create([
                        'invoice_no' => null, // Will be generated
                        'date' => $this->postingDate->format('Y-m-d'), // Always 01-01-2026
                        'due_date' => Carbon::parse($invoiceData['due_date'])->format('Y-m-d'),
                        'business_partner_id' => $customer->id,
                        'company_entity_id' => $invoiceData['company_entity_id'],
                        'reference_no' => $invoiceData['reference_no'],
                        'is_opening_balance' => true, // Always true for imports
                        'description' => $description,
                        'status' => 'draft',
                        'total_amount' => 0,
                    ]);

                    // Generate invoice number
                    $invoiceNo = $this->documentNumberingService->generateNumber('sales_invoice', $this->postingDate, [
                        'company_entity_id' => $invoiceData['company_entity_id'],
                    ]);
                    $invoice->update(['invoice_no' => $invoiceNo]);

                    // Create lines
                    $total = 0;
                    foreach ($invoiceData['lines'] as $lineData) {
                        $account = Account::where('code', $lineData['account_code'])->first();
                        if (!$account) {
                            $this->errors[] = "Account with code '{$lineData['account_code']}' not found for invoice {$invoiceNo}";
                            continue;
                        }

                        $taxCode = null;
                        if (!empty($lineData['tax_code'])) {
                            $taxCode = TaxCode::where('code', $lineData['tax_code'])->first();
                            if (!$taxCode) {
                                $this->warnings[] = "Tax code '{$lineData['tax_code']}' not found for invoice {$invoiceNo}, line will be created without tax";
                            }
                        }

                        $amount = $lineData['qty'] * $lineData['unit_price'];
                        $total += $amount;

                        SalesInvoiceLine::create([
                            'invoice_id' => $invoice->id,
                            'account_id' => $account->id,
                            'description' => $lineData['description'],
                            'qty' => $lineData['qty'],
                            'unit_price' => $lineData['unit_price'],
                            'amount' => $amount,
                            'tax_code_id' => $taxCode ? $taxCode->id : null,
                        ]);
                    }

                    // Calculate terms_days from document_date and due_date
                    $documentDate = Carbon::parse($invoiceData['document_date']);
                    $dueDate = Carbon::parse($invoiceData['due_date']);
                    $termsDays = $documentDate->diffInDays($dueDate);

                    $invoice->update([
                        'total_amount' => $total,
                        'terms_days' => $termsDays > 0 ? $termsDays : null,
                    ]);

                    $this->importedCount++;
                } catch (\Exception $e) {
                    $this->errors[] = "Failed to create invoice for customer '{$invoiceData['customer_code']}': " . $e->getMessage();
                    $this->skippedCount++;
                }
            }

            return true;
        });
    }

    public function validateFile($filePath)
    {
        try {
            $data = Excel::toArray([], $filePath);

            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['Excel file is empty or invalid'],
                    'warnings' => [],
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                ];
            }

            $sheetData = $data[0];
            if (empty($sheetData) || count($sheetData) < 2) {
                return [
                    'valid' => false,
                    'errors' => ['Excel file must contain at least a header row and one data row'],
                    'warnings' => [],
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                ];
            }

            // First row is headers, rest are data
            $headers = array_map('strtolower', array_map('trim', $sheetData[0]));
            $rows = [];
            for ($i = 1; $i < count($sheetData); $i++) {
                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = $sheetData[$i][$idx] ?? null;
                }
                $rows[] = $row;
            }

            $validatedData = $this->validateImportData($rows);

            return [
                'valid' => empty($this->errors),
                'errors' => $this->errors,
                'warnings' => $this->warnings,
                'total_rows' => count($rows),
                'valid_rows' => count($validatedData),
                'invalid_rows' => count($rows) - count($validatedData),
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
                'warnings' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
            ];
        }
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    protected function resetCounters()
    {
        $this->errors = [];
        $this->warnings = [];
        $this->importedCount = 0;
        $this->skippedCount = 0;
    }
}
