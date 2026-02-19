<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\Warehouse;
use App\Services\InventoryDashboardDataService;
use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    public function __construct(
        private readonly InventoryDashboardDataService $inventoryDashboardDataService
    ) {
        $this->middleware(['auth', 'permission:inventory.view']);
    }

    public function index(Request $request)
    {
        $filters = [
            'warehouse_id' => $request->integer('warehouse_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
        ];

        $dashboardData = $this->inventoryDashboardDataService->getInventoryDashboardData(
            $filters,
            $request->boolean('refresh')
        );

        $warehousesList = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $categoriesList = ProductCategory::orderBy('name')->get(['id', 'name']);

        return view('inventory.dashboard', [
            'dashboardData' => $dashboardData,
            'warehousesList' => $warehousesList,
            'categoriesList' => $categoriesList,
            'filters' => $filters,
        ]);
    }
}
