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
use App\Services\UnitConversionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PurchaseService
{
    protected $inventoryService;
    protected $documentNumberingService;
    protected $unitConversionService;

    public function __construct(InventoryService $inventoryService, DocumentNumberingService $documentNumberingService, UnitConversionService $unitConversionService)
    {
        $this->inventoryService = $inventoryService;
        $this->documentNumberingService = $documentNumberingService;
        $this->unitConversionService = $unitConversionService;
    }

    public function createPurchaseOrder($data)
    {
        return DB::transaction(function () use ($data) {
            Log::info('Starting DB transaction to create Purchase Order');
            try {
                $po = PurchaseOrder::create([
                    'order_no' => $data['order_no'],
                    'reference_no' => $data['reference_no'] ?? null,
                    'date' => $data['date'],
                    'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                    'business_partner_id' => $data['business_partner_id'],
                    'warehouse_id' => $data['warehouse_id'],
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
                Log::info('Purchase Order header created with ID: ' . ($po->id ?? 'null'));
            } catch (\Exception $e) {
                Log::error('Error creating Purchase Order header: ' . $e->getMessage());
                Log::error($e->getTraceAsString());
                throw $e;
            }

            $totalAmount = 0;
            $totalFreightCost = 0;
            $totalHandlingCost = 0;

            foreach ($data['lines'] as $index => $lineData) {
                try {
                    Log::info("Processing line {$index} with data:", $lineData);

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
                        Log::info("Line {$index} is inventory item with ID: {$inventoryItemId}, using account ID: {$accountId}");
                    } else {
                        $accountId = $lineData['item_id'];
                        Log::info("Line {$index} is service with account ID: {$accountId}");
                    }

                    // Process unit conversion if order_unit_id is provided
                    $baseQuantity = $lineData['qty'];
                    $conversionFactor = 1;

                    if (isset($lineData['order_unit_id']) && $lineData['order_unit_id'] && $inventoryItemId) {
                        Log::info("Processing unit conversion for line {$index}");
                        $processedLine = $this->unitConversionService->processOrderLine($lineData, $inventoryItemId);
                        $baseQuantity = $processedLine['base_quantity'] ?? $lineData['qty'];
                        $conversionFactor = $processedLine['unit_conversion_factor'] ?? 1;
                        Log::info("Unit conversion result: baseQty={$baseQuantity}, factor={$conversionFactor}");
                    }

                    Log::info("Creating purchase order line for PO ID: {$po->id}");
                    $line = PurchaseOrderLine::create([
                        'order_id' => $po->id,
                        'account_id' => $accountId,
                        'inventory_item_id' => $inventoryItemId,
                        'item_code' => null,
                        'item_name' => null,
                        'unit_of_measure' => null,
                        'order_unit_id' => $lineData['order_unit_id'] ?? null,
                        'description' => $lineData['description'] ?? null,
                        'qty' => $lineData['qty'],
                        'base_quantity' => $baseQuantity,
                        'unit_conversion_factor' => $conversionFactor,
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
                    Log::info("Purchase order line created with ID: " . ($line->id ?? 'null'));
                } catch (\Exception $e) {
                    Log::error("Error creating purchase order line {$index}: " . $e->getMessage());
                    Log::error($e->getTraceAsString());
                    throw $e;
                }
            }

            // Update totals
            try {
                Log::info("Updating Purchase Order totals: amount={$totalAmount}, freight={$totalFreightCost}, handling={$totalHandlingCost}");
                $po->update([
                    'total_amount' => $totalAmount,
                    'freight_cost' => $totalFreightCost,
                    'handling_cost' => $totalHandlingCost,
                ]);
                Log::info("Purchase Order totals updated successfully");
            } catch (\Exception $e) {
                Log::error("Error updating Purchase Order totals: " . $e->getMessage());
                Log::error($e->getTraceAsString());
                throw $e;
            }

            // Create approval workflow
            try {
                Log::info("Creating approval workflow for Purchase Order ID: {$po->id}");
                $this->createApprovalWorkflow($po);
                Log::info("Approval workflow created successfully");
            } catch (\Exception $e) {
                Log::error("Error creating approval workflow: " . $e->getMessage());
                Log::error($e->getTraceAsString());
                throw $e;
            }

            Log::info("Purchase Order creation completed successfully, returning PO with ID: {$po->id}");
            return $po;
        });
    }

    public function updatePurchaseOrder($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $po = PurchaseOrder::findOrFail($id);

            // Only allow updating draft purchase orders
            if ($po->status !== 'draft') {
                throw new \Exception('Only draft purchase orders can be updated.');
            }

            // Update the purchase order
            $po->update([
                'order_no' => $data['order_no'],
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
            ]);

            // Delete existing lines
            $po->lines()->delete();

            // Create new lines
            $totalAmount = 0;
            foreach ($data['lines'] as $lineData) {
                // Process unit conversion if order_unit_id is provided
                if (isset($lineData['order_unit_id']) && $lineData['order_unit_id']) {
                    $processedLine = $this->unitConversionService->processOrderLine($lineData, $lineData['item_id']);
                } else {
                    $processedLine = $lineData;
                }

                $line = PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $processedLine['item_id'],
                    'account_id' => $processedLine['account_id'] ?? null,
                    'description' => $processedLine['description'],
                    'qty' => $processedLine['qty'],
                    'unit_price' => $processedLine['unit_price'],
                    'order_unit_id' => $processedLine['order_unit_id'] ?? null,
                    'base_quantity' => $processedLine['base_quantity'] ?? $processedLine['qty'],
                    'unit_conversion_factor' => $processedLine['unit_conversion_factor'] ?? 1,
                    'vat_rate' => $processedLine['vat_rate'],
                    'wtax_rate' => $processedLine['wtax_rate'],
                    'notes' => $processedLine['notes'] ?? null,
                ]);

                $totalAmount += $line->amount;
            }

            // Update total amount
            $po->update(['total_amount' => $totalAmount]);

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
                    'status' => 'ordered',
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
