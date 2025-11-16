<?php

namespace App\Http\Controllers;

use App\Services\PurchaseDashboardDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseDashboardController extends Controller
{
    public function __construct(
        private readonly PurchaseDashboardDataService $purchaseDashboardDataService
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $filters = [
            'supplier_id' => $request->integer('supplier_id') ?: null,
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'aging_bucket' => $request->input('aging_bucket') ?: null,
        ];

        $dashboardData = $this->purchaseDashboardDataService->getPurchaseDashboardData(
            $filters,
            $request->boolean('refresh')
        );

        $suppliersList = DB::table('business_partners')
            ->where('partner_type', 'supplier')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('purchase.dashboard', [
            'dashboardData' => $dashboardData,
            'suppliersList' => $suppliersList,
            'filters' => $filters,
        ]);
    }
}
