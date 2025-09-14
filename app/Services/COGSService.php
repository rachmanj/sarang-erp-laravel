<?php

namespace App\Services;

use App\Models\CostHistory;
use App\Models\CostAllocation;
use App\Models\ProductCostSummary;
use App\Models\MarginAnalysis;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class COGSService
{
    /**
     * Calculate COGS for a specific product and period
     */
    public function calculateProductCOGS($itemId, $startDate, $endDate, $valuationMethod = 'fifo')
    {
        $costSummary = ProductCostSummary::calculateForPeriod($itemId, $startDate, $endDate, $valuationMethod);

        // Save the summary if it doesn't exist
        $existing = ProductCostSummary::where('inventory_item_id', $itemId)
            ->where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->first();

        if (!$existing) {
            $costSummary->save();
        }

        return $costSummary;
    }

    /**
     * Allocate indirect costs to products
     */
    public function allocateIndirectCosts($startDate, $endDate)
    {
        $allocations = CostAllocation::active()->get();
        $unallocatedCosts = CostHistory::unallocated()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        foreach ($unallocatedCosts as $cost) {
            $this->allocateCostToProducts($cost, $allocations);
        }
    }

    /**
     * Allocate a specific cost to products
     */
    protected function allocateCostToProducts($cost, $allocations)
    {
        $relevantAllocations = $allocations->where('cost_category_id', $cost->cost_category_id);

        foreach ($relevantAllocations as $allocation) {
            $products = $this->getProductsForAllocation($allocation, $cost->transaction_date);

            foreach ($products as $product) {
                $allocatedAmount = $allocation->calculateAllocation(
                    $product['base_value'],
                    $products->sum('base_value')
                );

                if ($allocatedAmount > 0) {
                    $this->createCostAllocationRecord($cost, $product['item_id'], $allocatedAmount);
                }
            }
        }
    }

    /**
     * Get products for cost allocation
     */
    protected function getProductsForAllocation($allocation, $date)
    {
        $startDate = Carbon::parse($date)->startOfMonth();
        $endDate = Carbon::parse($date)->endOfMonth();

        return InventoryItem::whereHas('transactions', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        })->get()->map(function ($item) use ($allocation) {
            $baseValue = match ($allocation->allocation_base) {
                'quantity' => $item->current_stock,
                'value' => $item->current_stock * $item->average_cost,
                'weight' => $item->current_stock * ($item->weight ?? 1),
                'volume' => $item->current_stock * ($item->volume ?? 1),
                default => 1
            };

            return [
                'item_id' => $item->id,
                'base_value' => $baseValue
            ];
        });
    }

    /**
     * Create cost allocation record
     */
    protected function createCostAllocationRecord($cost, $itemId, $allocatedAmount)
    {
        // Update the cost history with allocated amount
        $cost->allocated_cost += $allocatedAmount;
        $cost->save();

        // Create allocation record for tracking
        DB::table('cost_allocations')->insert([
            'cost_history_id' => $cost->id,
            'inventory_item_id' => $itemId,
            'allocated_amount' => $allocatedAmount,
            'allocation_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Calculate margin analysis for a product
     */
    public function calculateProductMargin($itemId, $startDate, $endDate)
    {
        return MarginAnalysis::calculateForProduct($itemId, $startDate, $endDate);
    }

    /**
     * Calculate customer profitability
     */
    public function calculateCustomerProfitability($customerId, $startDate, $endDate)
    {
        $salesOrders = SalesOrder::where('customer_id', $customerId)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->with('lines.inventoryItem')
            ->get();

        $totalRevenue = $salesOrders->sum('total_amount');
        $totalCOGS = 0;
        $totalQuantity = 0;

        foreach ($salesOrders as $order) {
            foreach ($order->lines as $line) {
                $costSummary = $this->calculateProductCOGS(
                    $line->inventory_item_id,
                    $startDate,
                    $endDate
                );

                $totalCOGS += $line->quantity * $costSummary->average_unit_cost;
                $totalQuantity += $line->quantity;
            }
        }

        $grossMargin = $totalRevenue - $totalCOGS;
        $grossMarginPercentage = $totalRevenue > 0 ? ($grossMargin / $totalRevenue) * 100 : 0;

        return new MarginAnalysis([
            'analysis_type' => 'customer',
            'customer_id' => $customerId,
            'analysis_date' => $endDate,
            'revenue' => $totalRevenue,
            'cost_of_goods_sold' => $totalCOGS,
            'gross_margin' => $grossMargin,
            'gross_margin_percentage' => $grossMarginPercentage,
            'quantity_sold' => $totalQuantity,
            'average_selling_price' => $totalQuantity > 0 ? $totalRevenue / $totalQuantity : 0,
            'average_cost' => $totalQuantity > 0 ? $totalCOGS / $totalQuantity : 0,
        ]);
    }

    /**
     * Generate COGS report for a period
     */
    public function generateCOGSReport($startDate, $endDate, $groupBy = 'product')
    {
        $query = ProductCostSummary::whereBetween('period_start', [$startDate, $endDate])
            ->with('inventoryItem');

        if ($groupBy === 'category') {
            $query->with('inventoryItem.category');
        }

        $summaries = $query->get();

        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_products' => $summaries->count(),
                'total_cost' => $summaries->sum('total_cost'),
                'total_quantity' => $summaries->sum('total_quantity'),
                'average_cost_per_unit' => $summaries->avg('average_unit_cost'),
            ],
            'breakdown' => [
                'direct_costs' => $summaries->sum('total_purchase_cost'),
                'indirect_costs' => $summaries->sum('total_freight_cost') + $summaries->sum('total_handling_cost'),
                'overhead_costs' => $summaries->sum('total_overhead_cost'),
            ],
            'details' => $summaries->map(function ($summary) {
                return [
                    'product_id' => $summary->inventory_item_id,
                    'product_name' => $summary->inventoryItem->name ?? 'Unknown',
                    'total_cost' => $summary->total_cost,
                    'quantity' => $summary->total_quantity,
                    'average_unit_cost' => $summary->average_unit_cost,
                    'cost_breakdown' => $summary->cost_breakdown,
                ];
            }),
        ];

        return $report;
    }

    /**
     * Get cost trends for a product
     */
    public function getProductCostTrends($itemId, $months = 12)
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $summaries = ProductCostSummary::where('inventory_item_id', $itemId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start')
            ->get();

        return $summaries->map(function ($summary) {
            return [
                'period' => $summary->period_start->format('Y-m'),
                'total_cost' => $summary->total_cost,
                'average_unit_cost' => $summary->average_unit_cost,
                'quantity' => $summary->total_quantity,
                'cost_percentage_change' => $this->calculatePercentageChange($summary),
            ];
        });
    }

    /**
     * Calculate percentage change from previous period
     */
    protected function calculatePercentageChange($currentSummary)
    {
        $previousSummary = ProductCostSummary::where('inventory_item_id', $currentSummary->inventory_item_id)
            ->where('period_end', '<', $currentSummary->period_start)
            ->orderBy('period_end', 'desc')
            ->first();

        if (!$previousSummary) {
            return 0;
        }

        if ($previousSummary->average_unit_cost == 0) {
            return 0;
        }

        return (($currentSummary->average_unit_cost - $previousSummary->average_unit_cost) / $previousSummary->average_unit_cost) * 100;
    }

    /**
     * Identify cost optimization opportunities
     */
    public function identifyCostOptimizationOpportunities($startDate, $endDate)
    {
        $opportunities = [];

        // High-cost products
        $highCostProducts = ProductCostSummary::whereBetween('period_start', [$startDate, $endDate])
            ->where('average_unit_cost', '>', DB::table('product_cost_summaries')->avg('average_unit_cost'))
            ->with('inventoryItem')
            ->get();

        foreach ($highCostProducts as $product) {
            $opportunities[] = [
                'type' => 'high_cost_product',
                'product_id' => $product->inventory_item_id,
                'product_name' => $product->inventoryItem->name ?? 'Unknown',
                'current_cost' => $product->average_unit_cost,
                'recommendation' => 'Review supplier options and negotiate better pricing',
                'potential_savings' => $product->average_unit_cost * 0.1, // Assume 10% savings
            ];
        }

        // Unallocated costs
        $unallocatedCosts = CostHistory::unallocated()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_cost');

        if ($unallocatedCosts > 0) {
            $opportunities[] = [
                'type' => 'unallocated_costs',
                'amount' => $unallocatedCosts,
                'recommendation' => 'Review cost allocation rules and ensure all costs are properly allocated',
                'potential_savings' => $unallocatedCosts * 0.05, // Assume 5% savings from better allocation
            ];
        }

        return $opportunities;
    }
}
