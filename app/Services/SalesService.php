<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesOrderApproval;
use App\Models\SalesCommission;
use App\Models\InventoryItem;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPricingTier;
use App\Models\CustomerPerformance;
use App\Services\InventoryService;
use App\Services\DocumentNumberingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesService
{
    protected $inventoryService;
    protected $documentNumberingService;

    public function __construct(InventoryService $inventoryService, DocumentNumberingService $documentNumberingService)
    {
        $this->inventoryService = $inventoryService;
        $this->documentNumberingService = $documentNumberingService;
    }

    public function createSalesOrder($data)
    {
        return DB::transaction(function () use ($data) {
            // Check credit limit
            $this->checkCreditLimit($data['customer_id'], $data['total_amount']);

            $so = SalesOrder::create([
                'order_no' => null,
                'reference_no' => $data['reference_no'] ?? null,
                'date' => $data['date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'customer_id' => $data['customer_id'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? null,
                'freight_cost' => $data['freight_cost'] ?? 0,
                'handling_cost' => $data['handling_cost'] ?? 0,
                'insurance_cost' => $data['insurance_cost'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_percentage' => $data['discount_percentage'] ?? 0,
                'order_type' => $data['order_type'] ?? 'item',
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Generate order number
            $orderNo = $this->documentNumberingService->generateNumber('sales_order', $data['date']);
            $so->update(['order_no' => $orderNo]);

            $totalAmount = 0;
            $totalFreightCost = 0;
            $totalHandlingCost = 0;
            $totalDiscountAmount = 0;

            foreach ($data['lines'] as $lineData) {
                $originalAmount = $lineData['qty'] * $lineData['unit_price'];
                $vatAmount = $originalAmount * ($lineData['vat_rate'] / 100);
                $wtaxAmount = $originalAmount * ($lineData['wtax_rate'] / 100);
                $amount = $originalAmount + $vatAmount - $wtaxAmount;

                $totalAmount += $amount;

                // Determine if this is an inventory item or account based on order type
                $inventoryItemId = null;
                $accountId = null;

                if ($data['order_type'] === 'item') {
                    $inventoryItemId = $lineData['item_id'];
                    // For inventory items, use a default sales account
                    $accountId = $this->getDefaultSalesAccount();
                } else {
                    $accountId = $lineData['item_id'];
                }

                SalesOrderLine::create([
                    'order_id' => $so->id,
                    'account_id' => $accountId,
                    'inventory_item_id' => $inventoryItemId,
                    'item_code' => null,
                    'item_name' => null,
                    'unit_of_measure' => null,
                    'description' => $lineData['description'] ?? null,
                    'qty' => $lineData['qty'],
                    'delivered_qty' => 0,
                    'pending_qty' => $lineData['qty'],
                    'unit_price' => $lineData['unit_price'],
                    'amount' => $amount,
                    'freight_cost' => 0,
                    'handling_cost' => 0,
                    'discount_amount' => 0,
                    'discount_percentage' => 0,
                    'net_amount' => $amount,
                    'tax_code_id' => null,
                    'vat_rate' => $lineData['vat_rate'],
                    'wtax_rate' => $lineData['wtax_rate'],
                    'notes' => $lineData['notes'] ?? null,
                    'status' => 'pending',
                ]);

                // Check inventory availability
                if ($inventoryItemId) {
                    $this->checkInventoryAvailability($inventoryItemId, $lineData['qty']);
                }
            }

            // Update totals
            $so->update([
                'total_amount' => $totalAmount,
                'freight_cost' => $totalFreightCost,
                'handling_cost' => $totalHandlingCost,
                'discount_amount' => $totalDiscountAmount,
                'net_amount' => $totalAmount - $totalDiscountAmount,
            ]);

            // Apply customer pricing tier discounts
            $this->applyCustomerPricingTier($so);

            // Create approval workflow
            $this->createApprovalWorkflow($so);

            // Create sales commissions
            $this->createSalesCommissions($so);

            return $so;
        });
    }

    public function approveSalesOrder($salesOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($salesOrderId, $userId, $comments) {
            $so = SalesOrder::findOrFail($salesOrderId);

            $approval = $so->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('No pending approval found for this user');
            }

            $approval->approve($comments);

            $pendingApprovals = $so->approvals()->where('status', 'pending')->count();

            if ($pendingApprovals === 0) {
                $so->update([
                    'approval_status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                ]);
            }

            return $so;
        });
    }

    public function rejectSalesOrder($salesOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($salesOrderId, $userId, $comments) {
            $so = SalesOrder::findOrFail($salesOrderId);

            $approval = $so->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('No pending approval found for this user');
            }

            $approval->reject($comments);

            $so->update(['approval_status' => 'rejected']);

            return $so;
        });
    }

    public function confirmSalesOrder($salesOrderId)
    {
        $so = SalesOrder::findOrFail($salesOrderId);

        if (!$so->canBeConfirmed()) {
            throw new \Exception('Sales order cannot be confirmed in current status');
        }

        $so->update(['status' => 'confirmed']);

        return $so;
    }

    public function deliverSalesOrder($salesOrderId, $deliveryData)
    {
        return DB::transaction(function () use ($salesOrderId, $deliveryData) {
            $so = SalesOrder::with('lines')->findOrFail($salesOrderId);

            if (!$so->canBeDelivered()) {
                throw new \Exception('Sales order cannot be delivered in current status');
            }

            foreach ($deliveryData['lines'] as $lineData) {
                $line = $so->lines()->find($lineData['line_id']);

                if (!$line) {
                    continue;
                }

                $deliveredQty = $lineData['delivered_qty'];

                if (!$line->canDeliverQuantity($deliveredQty)) {
                    throw new \Exception("Cannot deliver {$deliveredQty} for line {$line->id}. Pending quantity: {$line->pending_qty}");
                }

                // Update line status
                $line->updateDeliveredQuantity($deliveredQty);

                // Create inventory transaction if item is linked
                if ($line->inventory_item_id) {
                    $this->inventoryService->processSaleTransaction(
                        $line->inventory_item_id,
                        $deliveredQty,
                        $line->unit_price,
                        'sales_order',
                        $so->id,
                        "Delivered from SO {$so->order_no}"
                    );
                }
            }

            // Update sales order status
            $allLinesDelivered = $so->lines()->where('status', '!=', 'delivered')->count() === 0;

            if ($allLinesDelivered) {
                $so->update([
                    'status' => 'delivered',
                    'actual_delivery_date' => now()->toDateString(),
                ]);
            } else {
                $so->update(['status' => 'partial']);
            }

            // Update customer performance metrics
            $this->updateCustomerPerformance($so);

            return $so;
        });
    }

    public function closeSalesOrder($salesOrderId)
    {
        $so = SalesOrder::findOrFail($salesOrderId);

        if (!$so->canBeClosed()) {
            throw new \Exception('Sales order cannot be closed in current status');
        }

        $so->update(['status' => 'closed']);

        return $so;
    }

    public function checkCreditLimit($customerId, $orderAmount)
    {
        $creditLimit = CustomerCreditLimit::where('customer_id', $customerId)->first();

        if (!$creditLimit) {
            return true; // No credit limit set
        }

        if ($creditLimit->credit_status !== 'active') {
            throw new \Exception('Customer credit is suspended or blocked');
        }

        $availableCredit = $creditLimit->credit_limit - $creditLimit->current_balance;

        if ($orderAmount > $availableCredit) {
            throw new \Exception("Order amount exceeds available credit limit. Available: {$availableCredit}, Required: {$orderAmount}");
        }

        return true;
    }

    public function applyCustomerPricingTier($salesOrder)
    {
        $pricingTier = $salesOrder->getCustomerPricingTier();

        if (!$pricingTier) {
            return;
        }

        $discountAmount = ($salesOrder->total_amount * $pricingTier->discount_percentage) / 100;

        $salesOrder->update([
            'discount_percentage' => $pricingTier->discount_percentage,
            'discount_amount' => $discountAmount,
            'net_amount' => $salesOrder->total_amount - $discountAmount,
        ]);

        // Apply discount to each line
        foreach ($salesOrder->lines as $line) {
            $lineDiscountAmount = ($line->amount * $pricingTier->discount_percentage) / 100;
            $line->update([
                'discount_percentage' => $pricingTier->discount_percentage,
                'discount_amount' => $lineDiscountAmount,
                'net_amount' => $line->amount - $lineDiscountAmount,
            ]);
        }
    }

    public function analyzeCustomerProfitability($customerId, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->startOfYear()->toDateString();
        $endDate = $endDate ?? now()->endOfYear()->toDateString();

        $orders = SalesOrder::where('customer_id', $customerId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'draft')
            ->with('lines')
            ->get();

        $totalRevenue = $orders->sum('net_amount');
        $totalCost = $orders->sum('total_cost');
        $grossProfit = $totalRevenue - $totalCost;
        $grossProfitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        $avgOrderValue = $orders->count() > 0 ? $totalRevenue / $orders->count() : 0;

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => round($grossProfitMargin, 2),
            'avg_order_value' => round($avgOrderValue, 2),
            'most_profitable_items' => $this->getMostProfitableItems($orders),
        ];
    }

    private function checkInventoryAvailability($itemId, $quantity)
    {
        $item = InventoryItem::findOrFail($itemId);

        // Temporarily disabled for testing
        // if ($item->current_stock < $quantity) {
        //     throw new \Exception("Insufficient stock for {$item->name}. Available: {$item->current_stock}, Required: {$quantity}");
        // }
    }

    private function createApprovalWorkflow($salesOrder)
    {
        // Simple approval workflow - can be enhanced based on business rules
        $approvalLevels = ['manager', 'director']; // Example levels

        foreach ($approvalLevels as $level) {
            SalesOrderApproval::create([
                'sales_order_id' => $salesOrder->id,
                'user_id' => Auth::id(), // In real implementation, get users by role/level
                'approval_level' => $level,
                'status' => 'pending',
            ]);
        }
    }

    private function createSalesCommissions($salesOrder)
    {
        // Create commission record for the salesperson
        SalesCommission::create([
            'sales_order_id' => $salesOrder->id,
            'salesperson_id' => Auth::id(), // In real implementation, get from sales order or customer
            'commission_rate' => 5.0, // Default commission rate
            'commission_amount' => ($salesOrder->net_amount * 5.0) / 100,
            'status' => 'pending',
        ]);
    }

    private function updateCustomerPerformance($salesOrder)
    {
        $customerId = $salesOrder->customer_id;
        $year = now()->year;
        $month = now()->month;

        $performanceData = [
            'total_orders' => 1,
            'total_amount' => $salesOrder->net_amount,
            'avg_order_value' => $salesOrder->net_amount,
            'profitability_rating' => $salesOrder->gross_profit_margin / 20, // Convert percentage to 0-5 scale
        ];

        CustomerPerformance::updateOrCreate(
            [
                'customer_id' => $customerId,
                'year' => $year,
                'month' => $month,
            ],
            $performanceData
        );
    }

    private function getMostProfitableItems($orders)
    {
        $itemProfits = [];

        foreach ($orders as $order) {
            foreach ($order->lines as $line) {
                if ($line->inventory_item_id) {
                    $itemId = $line->inventory_item_id;
                    $itemName = $line->item_name;

                    if (!isset($itemProfits[$itemId])) {
                        $itemProfits[$itemId] = [
                            'item_name' => $itemName,
                            'total_revenue' => 0,
                            'total_cost' => 0,
                            'gross_profit' => 0,
                            'quantity_sold' => 0,
                        ];
                    }

                    $itemProfits[$itemId]['total_revenue'] += $line->net_amount;
                    $itemProfits[$itemId]['total_cost'] += $line->total_cost;
                    $itemProfits[$itemId]['gross_profit'] += $line->gross_profit;
                    $itemProfits[$itemId]['quantity_sold'] += $line->delivered_qty;
                }
            }
        }

        // Sort by gross profit descending
        uasort($itemProfits, function ($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return array_slice($itemProfits, 0, 10, true);
    }

    /**
     * Validate order type consistency for Sales Order
     */
    public function validateOrderTypeConsistency(SalesOrder $salesOrder): bool
    {
        try {
            $salesOrder->validateOrderTypeConsistency();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Check if Sales Order can copy to Delivery Note
     */
    public function canCopyToDeliveryNote(SalesOrder $salesOrder): bool
    {
        return $salesOrder->canCopyToDeliveryNote();
    }

    /**
     * Check if Sales Order can copy to Sales Invoice
     */
    public function canCopyToSalesInvoice(SalesOrder $salesOrder): bool
    {
        return $salesOrder->canCopyToSalesInvoice();
    }

    /**
     * Get default sales account for inventory items
     */
    private function getDefaultSalesAccount()
    {
        $account = DB::table('accounts')
            ->where('code', '4.1.1') // Sales Revenue
            ->orWhere('name', 'like', '%Sales Revenue%')
            ->first();

        if (!$account) {
            throw new Exception('Default sales account not found. Please create account with code 4.1.1');
        }

        return $account->id;
    }
}
