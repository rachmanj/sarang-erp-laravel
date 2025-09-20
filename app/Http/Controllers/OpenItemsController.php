<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenItemsService;
use App\Models\BusinessPartner;
use Illuminate\Support\Facades\Auth;

class OpenItemsController extends Controller
{
    protected $openItemsService;

    public function __construct(OpenItemsService $openItemsService)
    {
        $this->openItemsService = $openItemsService;
        $this->middleware('auth');
        $this->middleware('permission:reports.open-items')->only(['index', 'show', 'export']);
    }

    /**
     * Display the Open Items dashboard
     */
    public function index(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'supplier_id', 'customer_id', 'document_type']);

        $openItems = $this->openItemsService->getAllOpenItems($filters);
        $summary = $this->openItemsService->getOpenItemsSummary($filters);

        // Get filter options
        $suppliers = BusinessPartner::where('partner_type', 'supplier')->orderBy('name')->get();
        $customers = BusinessPartner::where('partner_type', 'customer')->orderBy('name')->get();

        $documentTypes = [
            'purchase_orders' => 'Purchase Orders',
            'goods_receipts' => 'Goods Receipts',
            'purchase_invoices' => 'Purchase Invoices',
            'sales_orders' => 'Sales Orders',
            'delivery_orders' => 'Delivery Orders',
            'sales_invoices' => 'Sales Invoices',
        ];

        return view('reports.open-items.index', compact(
            'openItems',
            'summary',
            'filters',
            'suppliers',
            'customers',
            'documentTypes'
        ));
    }

    /**
     * Show detailed view for a specific document type
     */
    public function show(Request $request, $documentType)
    {
        $filters = $request->only(['date_from', 'date_to', 'supplier_id', 'customer_id']);

        $validTypes = [
            'purchase_orders',
            'goods_receipts',
            'purchase_invoices',
            'sales_orders',
            'delivery_orders',
            'sales_invoices'
        ];

        if (!in_array($documentType, $validTypes)) {
            abort(404, 'Invalid document type');
        }

        $method = 'getOpen' . str_replace('_', '', ucwords($documentType, '_'));
        $documents = $this->openItemsService->$method($filters);

        $summary = $this->openItemsService->getOpenItemsSummary($filters);
        $typeSummary = $summary['by_type'][$documentType] ?? null;

        // Get filter options
        $suppliers = BusinessPartner::where('partner_type', 'supplier')->orderBy('name')->get();
        $customers = BusinessPartner::where('partner_type', 'customer')->orderBy('name')->get();

        $documentTypeLabels = [
            'purchase_orders' => 'Purchase Orders',
            'goods_receipts' => 'Goods Receipts',
            'purchase_invoices' => 'Purchase Invoices',
            'sales_orders' => 'Sales Orders',
            'delivery_orders' => 'Delivery Orders',
            'sales_invoices' => 'Sales Invoices',
        ];

        return view('reports.open-items.show', compact(
            'documents',
            'documentType',
            'documentTypeLabels',
            'typeSummary',
            'filters',
            'suppliers',
            'customers'
        ));
    }

    /**
     * Export open items to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'supplier_id', 'customer_id', 'document_type']);

        $exportData = $this->openItemsService->exportToExcel($filters);

        // For now, return JSON response
        // In a real implementation, this would generate and download an Excel file
        return response()->json([
            'success' => true,
            'message' => 'Export data prepared successfully',
            'data' => $exportData,
            'download_url' => null // Would be the Excel file download URL
        ]);
    }

    /**
     * Get open items data for AJAX requests
     */
    public function getData(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'supplier_id', 'customer_id', 'document_type']);

        if ($request->has('document_type') && $request->document_type) {
            $method = 'getOpen' . str_replace('_', '', ucwords($request->document_type, '_'));
            $documents = $this->openItemsService->$method($filters);

            return response()->json([
                'success' => true,
                'data' => $documents,
                'count' => $documents->count(),
                'overdue_count' => $documents->where('is_overdue', true)->count(),
            ]);
        }

        $openItems = $this->openItemsService->getAllOpenItems($filters);
        $summary = $this->openItemsService->getOpenItemsSummary($filters);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'details' => $openItems,
        ]);
    }

    /**
     * Get summary statistics for dashboard widgets
     */
    public function getSummary(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'supplier_id', 'customer_id']);
        $summary = $this->openItemsService->getOpenItemsSummary($filters);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
