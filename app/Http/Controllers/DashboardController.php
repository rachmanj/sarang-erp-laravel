<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDataService $dashboardDataService
    ) {
    }

    public function index(Request $request)
    {
        $dashboardData = $this->dashboardDataService->getDashboardData(
            $request->boolean('refresh')
        );

        return view('dashboard', compact('dashboardData'));
    }
}

