<?php

namespace App\Http\Controllers;

use App\Services\Import\AssetImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetImportController extends Controller
{
    protected $assetImportService;

    public function __construct(AssetImportService $assetImportService)
    {
        $this->middleware('auth');
        $this->middleware('can:assets.create');
        $this->assetImportService = $assetImportService;
    }

    public function index()
    {
        $this->authorize('view', \App\Models\Asset::class);

        return view('assets.import.index');
    }

    public function template()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $csv = $this->assetImportService->generateTemplate();

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="asset_import_template.csv"');
    }

    public function validateImport(Request $request)
    {
        $this->authorize('create', \App\Models\Asset::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240' // 10MB max
        ]);

        $file = $request->file('file');
        $filePath = $file->store('temp/imports');

        try {
            $validation = $this->assetImportService->validateFile(storage_path('app/' . $filePath));

            // Clean up temp file
            Storage::delete($filePath);

            return response()->json($validation);
        } catch (\Exception $e) {
            // Clean up temp file
            Storage::delete($filePath);

            return response()->json([
                'valid' => false,
                'errors' => ['Validation failed: ' . $e->getMessage()],
                'warnings' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0
            ], 400);
        }
    }

    public function import(Request $request)
    {
        $this->authorize('create', \App\Models\Asset::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'options' => 'nullable|array',
            'options.skip_duplicates' => 'boolean',
            'options.update_existing' => 'boolean',
            'options.default_fund' => 'nullable|string',
            'options.default_project' => 'nullable|string',
            'options.default_department' => 'nullable|string'
        ]);

        $file = $request->file('file');
        $filePath = $file->store('temp/imports');
        $options = $request->get('options', []);

        try {
            $result = $this->assetImportService->importFromCsv(storage_path('app/' . $filePath), $options);

            // Clean up temp file
            Storage::delete($filePath);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Assets imported successfully',
                    'imported_count' => $this->assetImportService->getImportedCount(),
                    'skipped_count' => $this->assetImportService->getSkippedCount(),
                    'errors' => $this->assetImportService->getErrors(),
                    'warnings' => $this->assetImportService->getWarnings()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed',
                    'errors' => $this->assetImportService->getErrors(),
                    'warnings' => $this->assetImportService->getWarnings()
                ], 400);
            }
        } catch (\Exception $e) {
            // Clean up temp file
            Storage::delete($filePath);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
                'warnings' => []
            ], 500);
        }
    }

    public function getReferenceData()
    {
        $this->authorize('view', \App\Models\Asset::class);

        return response()->json([
            'categories' => \App\Models\AssetCategory::select('id', 'code', 'name')->get(),
            'funds' => \App\Models\Fund::select('id', 'code', 'name')->get(),
            'projects' => \App\Models\Project::select('id', 'code', 'name')->get(),
            'departments' => \App\Models\Department::select('id', 'code', 'name')->get(),
            'vendors' => \App\Models\Vendor::select('id', 'code', 'name')->get(),
            'purchase_invoices' => \App\Models\PurchaseInvoice::select('id', 'invoice_number', 'vendor_id')
                ->with('vendor:id,name')
                ->get()
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', \App\Models\Asset::class);

        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id',
            'updates' => 'required|array',
            'updates.fund_id' => 'nullable|exists:funds,id',
            'updates.project_id' => 'nullable|exists:projects,id',
            'updates.department_id' => 'nullable|exists:departments,id',
            'updates.status' => 'nullable|in:active,retired,disposed'
        ]);

        $assetIds = $request->get('asset_ids');
        $updates = $request->get('updates');

        // Remove null values
        $updates = array_filter($updates, function ($value) {
            return $value !== null && $value !== '';
        });

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid updates provided'
            ], 400);
        }

        try {
            $updatedCount = \App\Models\Asset::whereIn('id', $assetIds)->update($updates);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} assets",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
