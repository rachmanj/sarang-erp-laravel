<?php

namespace App\Http\Controllers;

use App\Services\SalesDashboardDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    public function __construct(
        private readonly SalesDashboardDataService $salesDashboardDataService
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $filters = [
            'customer_id' => $request->integer('customer_id') ?: null,
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'aging_bucket' => $request->input('aging_bucket') ?: null,
        ];

        $dashboardData = $this->salesDashboardDataService->getSalesDashboardData(
            $filters,
            $request->boolean('refresh')
        );

        $customersList = DB::table('business_partners')
            ->where('partner_type', 'customer')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sales.dashboard', [
            'dashboardData' => $dashboardData,
            'customersList' => $customersList,
            'filters' => $filters,
        ]);
    }
}

