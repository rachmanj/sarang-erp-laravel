<?php

namespace App\Http\Controllers;

use App\Services\ActivityDashboardDataService;
use Illuminate\Http\Request;

class ActivityDashboardController extends Controller
{
    protected $dashboardDataService;

    public function __construct(ActivityDashboardDataService $dashboardDataService)
    {
        $this->dashboardDataService = $dashboardDataService;
    }

    public function index(Request $request)
    {
        $dashboardData = $this->dashboardDataService->getDashboardData(
            $request->boolean('refresh')
        );

        return view('activity-dashboard.index', compact('dashboardData'));
    }

    public function recentActivity(Request $request)
    {
        $limit = $request->get('limit', 20);
        $since = $request->get('since');

        $dataService = app(ActivityDashboardDataService::class);
        $activity = $dataService->getRecentActivity($limit);

        return response()->json([
            'activity' => $activity,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

