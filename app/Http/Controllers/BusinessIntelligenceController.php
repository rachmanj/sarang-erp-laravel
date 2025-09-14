<?php

namespace App\Http\Controllers;

use App\Models\BusinessIntelligence;
use App\Services\BusinessIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class BusinessIntelligenceController extends Controller
{
    protected $businessIntelligenceService;

    public function __construct(BusinessIntelligenceService $businessIntelligenceService)
    {
        $this->businessIntelligenceService = $businessIntelligenceService;
    }

    /**
     * Display business intelligence dashboard
     */
    public function index()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Get latest analytics report
        $latestReport = BusinessIntelligence::where('report_type', 'trading_analytics')
            ->latest('report_date')
            ->first();

        // Get recent reports
        $recentReports = BusinessIntelligence::where('report_type', 'trading_analytics')
            ->orderBy('report_date', 'desc')
            ->limit(5)
            ->get();

        return view('business-intelligence.index', compact('latestReport', 'recentReports'));
    }
}
