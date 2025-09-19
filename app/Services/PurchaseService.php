<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderApproval;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\SupplierPerformance;
use App\Models\Accounting\Account;
use App\Services\InventoryService;
use App\Services\DocumentNumberingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseService
{
    protected $inventoryService;
    protected $documentNumberingService;

    public function __construct(InventoryService $inventoryService, DocumentNumberingService $documentNumberingService)
    {
        $this->inventoryService = $inventoryService;
        $this->documentNumberingService = $documentNumberingService;
    }

    public function createPurchaseOrder($data)
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'order_no' => null,
                'reference_no' => $data['reference_no'] ?? null,
                'date' => $data['date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'business_partner_id' => $data['business_partner_id'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? null,
                'freight_cost' => $data['freight_cost'] ?? 0,
                'handling_cost' => $data['handling_cost'] ?? 0,
                'insurance_cost' => $data['insurance_cost'] ?? 0,
                'order_type' => $data['order_type'] ?? 'item',
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Generate order number
            $orderNo = $this->documentNumberingService->generateNumber('purchase_order', $data['date']);
            $po->update(['order_no' => $orderNo]);

            $totalAmount = 0;
            $totalFreightCost = 0;
            $totalHandlingCost = 0;

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
                    // For inventory items, use a default inventory account
                    // You can modify this to get the account from inventory item if it has one
                    $accountId = $this->getDefaultInventoryAccount();
                } else {
                    $accountId = $lineData['item_id'];
                }

                PurchaseOrderLine::create([
                    'order_id' => $po->id,
                    'account_id' => $accountId,
                    'inventory_item_id' => $inventoryItemId,
                    'item_code' => null,
                    'item_name' => null,
                    'unit_of_measure' => null,
                    'description' => $lineData['description'] ?? null,
                    'qty' => $lineData['qty'],
                    'received_qty' => 0,
                    'pending_qty' => $lineData['qty'],
                    'unit_price' => $lineData['unit_price'],
                    'amount' => $amount,
                    'freight_cost' => 0,
                    'handling_cost' => 0,
                    'tax_code_id' => null,
                    'vat_rate' => $lineData['vat_rate'],
                    'wtax_rate' => $lineData['wtax_rate'],
                    'notes' => $lineData['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Update totals
            $po->update([
                'total_amount' => $totalAmount,
                'freight_cost' => $totalFreightCost,
                'handling_cost' => $totalHandlingCost,
            ]);

            // Create approval workflow
            $this->createApprovalWorkflow($po);

            return $po;
        });
    }

    public function approvePurchaseOrder($purchaseOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($purchaseOrderId, $userId, $comments) {
            $po = PurchaseOrder::findOrFail($purchaseOrderId);

            // Find the approval record
            $approval = $po->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('No pending approval found for this user');
            }

            $approval->approve($comments);

            // Check if all approvals are complete
            $pendingApprovals = $po->approvals()->where('status', 'pending')->count();

            if ($pendingApprovals === 0) {
                $po->update([
                    'approval_status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                ]);
            }

            return $po;
        });
    }

    public function rejectPurchaseOrder($purchaseOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($purchaseOrderId, $userId, $comments) {
            $po = PurchaseOrder::findOrFail($purchaseOrderId);

            $approval = $po->approvals()
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('No pending approval found for this user');
            }

            $approval->reject($comments);

            $po->update(['approval_status' => 'rejected']);

            return $po;
        });
    }

    public function receivePurchaseOrder($purchaseOrderId, $receiptData)
    {
        return DB::transaction(function () use ($purchaseOrderId, $receiptData) {
            $po = PurchaseOrder::with('lines')->findOrFail($purchaseOrderId);

            if (!$po->canBeReceived()) {
                throw new \Exception('Purchase order cannot be received in current status');
            }

            foreach ($receiptData['lines'] as $lineData) {
                $line = $po->lines()->find($lineData['line_id']);

                if (!$line) {
                    continue;
                }

                $receivedQty = $lineData['received_qty'];

                if (!$line->canReceiveQuantity($receivedQty)) {
                    throw new \Exception("Cannot receive {$receivedQty} for line {$line->id}. Pending quantity: {$line->pending_qty}");
                }

                // Update line status
                $line->updateReceivedQuantity($receivedQty);

                // Create inventory transaction if item is linked
                if ($line->inventory_item_id) {
                    $this->inventoryService->processPurchaseTransaction(
                        $line->inventory_item_id,
                        $receivedQty,
                        $line->unit_price,
                        'purchase_order',
                        $po->id,
                        "Received from PO {$po->order_no}"
                    );
                }
            }

            // Update purchase order status
            $allLinesReceived = $po->lines()->where('status', '!=', 'received')->count() === 0;

            if ($allLinesReceived) {
                $po->update([
                    'status' => 'received',
                    'actual_delivery_date' => now()->toDateString(),
                ]);
            } else {
                $po->update(['status' => 'partial']);
            }

            // Update supplier performance metrics
            $this->updateSupplierPerformance($po);

            return $po;
        });
    }

    public function closePurchaseOrder($purchaseOrderId)
    {
        $po = PurchaseOrder::findOrFail($purchaseOrderId);

        if (!$po->canBeClosed()) {
            throw new \Exception('Purchase order cannot be closed in current status');
        }

        $po->update(['status' => 'closed']);

        return $po;
    }

    public function compareSuppliers($itemId, $quantity)
    {
        $item = InventoryItem::findOrFail($itemId);

        // Get recent purchase orders for this item
        $recentOrders = PurchaseOrderLine::where('inventory_item_id', $itemId)
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'draft')
                    ->where('approval_status', 'approved');
            })
            ->with(['order.vendor', 'order.vendor.performance'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $supplierData = [];

        foreach ($recentOrders as $line) {
            $vendorId = $line->order->business_partner_id;
            $vendor = $line->order->vendor;

            if (!isset($supplierData[$vendorId])) {
                $supplierData[$vendorId] = [
                    'vendor' => $vendor,
                    'recent_prices' => [],
                    'avg_price' => 0,
                    'total_orders' => 0,
                    'avg_delivery_days' => 0,
                    'performance_rating' => 0,
                ];
            }

            $supplierData[$vendorId]['recent_prices'][] = $line->unit_price;
            $supplierData[$vendorId]['total_orders']++;
        }

        // Calculate averages and performance metrics
        foreach ($supplierData as $vendorId => &$data) {
            if (!empty($data['recent_prices'])) {
                $data['avg_price'] = array_sum($data['recent_prices']) / count($data['recent_prices']);
            }

            // Get performance data
            $performance = SupplierPerformance::where('business_partner_id', $vendorId)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();

            if ($performance) {
                $data['performance_rating'] = $performance->overall_rating;
                $data['avg_delivery_days'] = $performance->avg_delivery_days;
            }
        }

        // Sort by best value (considering price and performance)
        uasort($supplierData, function ($a, $b) {
            $scoreA = ($a['performance_rating'] * 0.6) - ($a['avg_price'] / 1000 * 0.4);
            $scoreB = ($b['performance_rating'] * 0.6) - ($b['avg_price'] / 1000 * 0.4);
            return $scoreB <=> $scoreA;
        });

        return $supplierData;
    }

    private function createApprovalWorkflow($purchaseOrder)
    {
        // Simple approval workflow - can be enhanced based on business rules
        $approvalLevels = ['manager', 'director']; // Example levels

        foreach ($approvalLevels as $level) {
            PurchaseOrderApproval::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => Auth::id(), // In real implementation, get users by role/level
                'approval_level' => $level,
                'status' => 'pending',
            ]);
        }
    }

    private function updateSupplierPerformance($purchaseOrder)
    {
        $vendorId = $purchaseOrder->business_partner_id;
        $year = now()->year;
        $month = now()->month;

        $orderData = [
            'total_orders' => 1,
            'total_amount' => $purchaseOrder->total_amount,
            'avg_delivery_days' => $purchaseOrder->expected_delivery_date
                ? now()->diffInDays($purchaseOrder->date)
                : 0,
        ];

        SupplierPerformance::updatePerformanceMetrics($vendorId, $year, $month, $orderData);
    }

    /**
     * Validate order type consistency for Purchase Order
     */
    public function validateOrderTypeConsistency(PurchaseOrder $purchaseOrder): bool
    {
        try {
            $purchaseOrder->validateOrderTypeConsistency();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Check if Purchase Order can copy to GRPO
     */
    public function canCopyToGRPO(PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canCopyToGRPO();
    }

    /**
     * Check if Purchase Order can copy to Purchase Invoice
     */
    public function canCopyToPurchaseInvoice(PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canCopyToPurchaseInvoice();
    }

    /**
     * Get default inventory account for purchase order lines
     */
    private function getDefaultInventoryAccount(): ?int
    {
        // Try to find an inventory account (typically starts with 1.3.x)
        $account = Account::where('code', 'like', '1.3%')
            ->where('name', 'like', '%inventory%')
            ->first();

        if (!$account) {
            // Fallback: find any asset account
            $account = Account::where('code', 'like', '1.%')
                ->first();
        }

        return $account ? $account->id : null;
    }
}
