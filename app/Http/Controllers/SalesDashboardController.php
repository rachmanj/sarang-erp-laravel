<?php

namespace App\Http\Controllers;

use App\Services\SalesDashboardDataService;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    public function __construct(
        private readonly SalesDashboardDataService $salesDashboardDataService
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $dashboardData = $this->salesDashboardDataService->getSalesDashboardData(
            $request->boolean('refresh')
        );

        return view('sales.dashboard', compact('dashboardData'));
    }
}

