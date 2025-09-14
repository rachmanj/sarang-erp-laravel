<?php

namespace App\Http\Controllers;

use App\Models\CostHistory;
use App\Models\ProductCostSummary;
use App\Models\MarginAnalysis;
use App\Models\InventoryItem;
use App\Services\COGSService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class COGSController extends Controller
{
    protected $cogsService;

    public function __construct(COGSService $cogsService)
    {
        $this->cogsService = $cogsService;
    }

    /**
     * Display COGS dashboard
     */
    public function index()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Get current month COGS summary
        $currentCOGS = ProductCostSummary::whereBetween('period_start', [
            $currentMonth,
            Carbon::now()->endOfMonth()
        ])->sum('total_cost');

        // Get last month COGS summary
        $lastCOGS = ProductCostSummary::whereBetween('period_start', [
            $lastMonth,
            Carbon::now()->subMonth()->endOfMonth()
        ])->sum('total_cost');

        // Calculate percentage change
        $percentageChange = $lastCOGS > 0 ?
            (($currentCOGS - $lastCOGS) / $lastCOGS) * 100 : 0;

        // Get top 10 products by cost
        $topProductsByCost = ProductCostSummary::with('inventoryItem')
            ->whereBetween('period_start', [$currentMonth, Carbon::now()->endOfMonth()])
            ->orderBy('total_cost', 'desc')
            ->limit(10)
            ->get();

        // Get unallocated costs
        $unallocatedCosts = CostHistory::unallocated()
            ->whereBetween('transaction_date', [$currentMonth, Carbon::now()->endOfMonth()])
            ->sum('total_cost');

        // Get cost optimization opportunities
        $optimizationOpportunities = $this->cogsService->identifyCostOptimizationOpportunities(
            $currentMonth,
            Carbon::now()->endOfMonth()
        );

        return view('cogs.index', compact(
            'currentCOGS',
            'lastCOGS',
            'percentageChange',
            'topProductsByCost',
            'unallocatedCosts',
            'optimizationOpportunities'
        ));
    }
}
