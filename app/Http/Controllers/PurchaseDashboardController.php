<?php

namespace App\Http\Controllers;

use App\Services\PurchaseDashboardDataService;
use Illuminate\Http\Request;

class PurchaseDashboardController extends Controller
{
    public function __construct(
        private readonly PurchaseDashboardDataService $purchaseDashboardDataService
    ) {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $dashboardData = $this->purchaseDashboardDataService->getPurchaseDashboardData(
            $request->boolean('refresh')
        );

        return view('purchase.dashboard', compact('dashboardData'));
    }
}
