<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Import\SalesInvoiceImportService;
use App\Services\CompanyEntityService;
use App\Exports\SalesInvoiceImportTemplate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SalesInvoiceImportController extends Controller
{
    public function __construct(
        private SalesInvoiceImportService $importService,
        private CompanyEntityService $companyEntityService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.invoices.create')->only(['index', 'template', 'validateImport', 'import']);
    }

    public function index()
    {
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        return view('sales_invoices.import.index', compact('entities', 'defaultEntity'));
    }

    public function template()
    {
        return Excel::download(new SalesInvoiceImportTemplate(), 'sales_invoice_import_template.xlsx');
    }

    public function validateImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $filePath = $file->store('temp/imports');

        try {
            $validation = $this->importService->validateFile(storage_path('app/' . $filePath));

            Storage::delete($filePath);

            return response()->json($validation);
        } catch (\Exception $e) {
            Storage::delete($filePath);

            return response()->json([
                'valid' => false,
                'errors' => ['Validation failed: ' . $e->getMessage()],
                'warnings' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
            ], 400);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'company_entity_id' => 'nullable|integer|exists:company_entities,id',
        ]);

        $file = $request->file('file');
        $filePath = $file->store('temp/imports');
        $options = [
            'company_entity_id' => $request->input('company_entity_id'),
        ];

        try {
            $result = $this->importService->importFromExcel(storage_path('app/' . $filePath), $options);

            Storage::delete($filePath);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sales Invoices imported successfully',
                    'imported_count' => $this->importService->getImportedCount(),
                    'skipped_count' => $this->importService->getSkippedCount(),
                    'errors' => $this->importService->getErrors(),
                    'warnings' => $this->importService->getWarnings(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed',
                    'errors' => $this->importService->getErrors(),
                    'warnings' => $this->importService->getWarnings(),
                ], 400);
            }
        } catch (\Exception $e) {
            Storage::delete($filePath);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
                'warnings' => [],
            ], 500);
        }
    }
}
