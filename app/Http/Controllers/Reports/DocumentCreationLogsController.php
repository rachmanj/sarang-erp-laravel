<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use App\Services\DocumentCreationLogsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DocumentCreationLogsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:reports.open-items')->only(['index']);
    }

    public function index(Request $request, DocumentCreationLogsService $documentCreationLogsService): View
    {
        $filters = $request->only(['date_from', 'date_to', 'supplier_id', 'customer_id', 'document_type']);

        $logs = $documentCreationLogsService->getMergedLogs($filters);

        $perPage = 50;
        $currentPage = max(1, (int) $request->get('page', 1));
        $items = $logs->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $logs->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $suppliers = BusinessPartner::where('partner_type', 'supplier')->orderBy('name')->get();
        $customers = BusinessPartner::where('partner_type', 'customer')->orderBy('name')->get();
        $documentTypes = $documentCreationLogsService->documentTypeLabels();

        return view('reports.document-creation-logs', compact(
            'paginator',
            'filters',
            'suppliers',
            'customers',
            'documentTypes'
        ));
    }
}
