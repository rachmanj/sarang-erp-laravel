<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\DeliveryTracking;
use App\Models\InventoryTransaction;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\InventoryItem;
use App\Models\BusinessPartner;
use App\Services\DocumentNumberingService;
use App\Services\DeliveryJournalService;
use App\Services\CompanyEntityService;
use App\Services\DocumentRelationshipService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    protected $documentNumberingService;
    protected $deliveryJournalService;
    protected $companyEntityService;
    protected $inventoryService;

    public function __construct(
        DocumentNumberingService $documentNumberingService,
        DeliveryJournalService $deliveryJournalService,
        CompanyEntityService $companyEntityService,
        InventoryService $inventoryService
    ) {
        $this->documentNumberingService = $documentNumberingService;
        $this->deliveryJournalService = $deliveryJournalService;
        $this->companyEntityService = $companyEntityService;
        $this->inventoryService = $inventoryService;
    }

    public function createDeliveryOrderFromSalesOrder($salesOrderId, $data = [])
    {
        return DB::transaction(function () use ($salesOrderId, $data) {
            Log::info('DeliveryService: Starting createDeliveryOrderFromSalesOrder', [
                'sales_order_id' => $salesOrderId,
                'data' => $data
            ]);

            $salesOrder = SalesOrder::with(['lines', 'customer', 'businessPartner'])->findOrFail($salesOrderId);

            Log::info('DeliveryService: Sales Order loaded', [
                'sales_order_id' => $salesOrder->id,
                'status' => $salesOrder->status,
                'approval_status' => $salesOrder->approval_status,
                'order_type' => $salesOrder->order_type,
                'lines_count' => $salesOrder->lines->count()
            ]);

            // Check if sales order can be converted to delivery order
            if (!$this->canCreateDeliveryOrder($salesOrder)) {
                Log::error('DeliveryService: Sales Order cannot be converted to Delivery Order', [
                    'sales_order_id' => $salesOrder->id,
                    'status' => $salesOrder->status,
                    'approval_status' => $salesOrder->approval_status,
                    'order_type' => $salesOrder->order_type
                ]);
                throw new \Exception('Sales Order cannot be converted to Delivery Order');
            }

            $entityId = $salesOrder->company_entity_id ?? $this->companyEntityService->getDefaultEntity()->id;

            $customer = $salesOrder->businessPartner;
            $customerAddress = $salesOrder->delivery_address ?? ($customer?->default_shipping_address);
            $customerContact = $salesOrder->delivery_contact_person ?? ($customer?->primary_contact_name);
            $customerPhone = $salesOrder->delivery_phone ?? ($customer?->primary_contact_phone);

            $doNumber = $this->documentNumberingService->generateNumber('delivery_order', now()->toDateString(), [
                'company_entity_id' => $entityId,
            ]);

            // Create delivery order
            Log::info('DeliveryService: Creating Delivery Order', [
                'sales_order_id' => $salesOrder->id,
                'business_partner_id' => $salesOrder->business_partner_id,
                'delivery_address' => $data['delivery_address'] ?? $customerAddress,
                'planned_delivery_date' => $data['planned_delivery_date'] ?? $salesOrder->expected_delivery_date ?? now()->addDays(3),
                'delivery_method' => $data['delivery_method'] ?? 'own_fleet',
                'created_by' => Auth::id(),
                'do_number' => $doNumber
            ]);

            try {
                $do = DeliveryOrder::create([
                    'do_number' => $doNumber,
                    'sales_order_id' => $salesOrder->id,
                    'business_partner_id' => $salesOrder->business_partner_id,
                    'company_entity_id' => $entityId,
                    'warehouse_id' => $data['warehouse_id'] ?? $salesOrder->warehouse_id,
                    'delivery_address' => $data['delivery_address'] ?? $customerAddress,
                    'delivery_contact_person' => $data['delivery_contact_person'] ?? $customerContact,
                    'delivery_phone' => $data['delivery_phone'] ?? $customerPhone,
                    'planned_delivery_date' => $data['planned_delivery_date'] ?? $salesOrder->expected_delivery_date ?? now()->addDays(3),
                    'delivery_method' => $data['delivery_method'] ?? 'own_fleet',
                    'delivery_instructions' => $data['delivery_instructions'] ?? null,
                    'logistics_cost' => $data['logistics_cost'] ?? 0,
                    'status' => 'draft',
                    'approval_status' => 'pending',
                    'created_by' => Auth::id(),
                    'notes' => $data['notes'] ?? null,
                ]);

                Log::info('DeliveryService: Delivery Order created', ['delivery_order_id' => $do->id, 'do_number' => $doNumber]);
            } catch (\Exception $e) {
                Log::error('DeliveryService: Failed to create Delivery Order', [
                    'error' => $e->getMessage(),
                    'sales_order_id' => $salesOrder->id,
                    'do_number' => $doNumber
                ]);
                throw $e;
            }

            // Create delivery order lines from sales order lines
            Log::info('DeliveryService: Creating delivery order lines', ['lines_count' => $salesOrder->lines->count()]);

            $linesCreated = 0;
            foreach ($salesOrder->lines as $salesOrderLine) {
                if (!$salesOrderLine->inventory_item_id) {
                    Log::info('DeliveryService: Skipping line without inventory_item_id', ['line_id' => $salesOrderLine->id]);
                    continue;
                }

                Log::info('DeliveryService: Creating delivery order line', [
                    'sales_order_line_id' => $salesOrderLine->id,
                    'inventory_item_id' => $salesOrderLine->inventory_item_id
                ]);

                $doLine = $this->createDeliveryOrderLine($do, $salesOrderLine, $data);
                if ($doLine === null) {
                    continue;
                }
                $linesCreated++;
            }

            if ($linesCreated === 0) {
                throw new \Exception('Sales Order has no remaining quantity to deliver');
            }

            // Create delivery tracking record
            Log::info('DeliveryService: Creating delivery tracking record', ['delivery_order_id' => $do->id]);
            $this->createDeliveryTracking($do);
            Log::info('DeliveryService: Delivery tracking record created', ['delivery_order_id' => $do->id]);

            // Update sales order status
            Log::info('DeliveryService: Updating sales order status to processing', ['sales_order_id' => $salesOrder->id]);
            $salesOrder->update(['status' => 'processing']);
            Log::info('DeliveryService: Sales order status updated', ['sales_order_id' => $salesOrder->id]);

            app(DocumentRelationshipService::class)->createBaseRelationship(
                $salesOrder,
                $do,
                'Delivery Order created from Sales Order'
            );

            Log::info('DeliveryService: Delivery Order creation completed successfully', ['delivery_order_id' => $do->id]);
            return $do;
        });
    }

    public function createDeliveryOrderLine($deliveryOrder, $salesOrderLine, $data = [])
    {
        Log::info('DeliveryService: Starting createDeliveryOrderLine', [
            'delivery_order_id' => $deliveryOrder->id,
            'sales_order_line_id' => $salesOrderLine->id,
            'inventory_item_id' => $salesOrderLine->inventory_item_id
        ]);

        // Get inventory item details
        $inventoryItem = null;
        $inventoryItemId = null;
        if ($salesOrderLine->inventory_item_id) {
            $inventoryItem = InventoryItem::find($salesOrderLine->inventory_item_id);
            if ($inventoryItem) {
                $inventoryItemId = $inventoryItem->id;
                Log::info('DeliveryService: Inventory item found', [
                    'inventory_item_id' => $inventoryItem->id,
                    'item_code' => $inventoryItem->code,
                    'item_name' => $inventoryItem->name
                ]);
            } else {
                Log::warning('DeliveryService: Inventory item not found, setting inventory_item_id to NULL', [
                    'sales_order_line_id' => $salesOrderLine->id,
                    'inventory_item_id' => $salesOrderLine->inventory_item_id,
                    'item_code' => $salesOrderLine->item_code,
                    'item_name' => $salesOrderLine->item_name
                ]);
                $inventoryItemId = null;
            }
        }

        $alreadyAllocated = $this->getAllocatedQtyForSalesOrderLine($salesOrderLine->id);
        $pendingQty = max(0, $salesOrderLine->qty - $alreadyAllocated);
        if ($pendingQty <= 0) {
            Log::info('DeliveryService: Skipping line with no remaining qty', ['sales_order_line_id' => $salesOrderLine->id]);
            return null;
        }

        Log::info('DeliveryService: Creating delivery order line', [
            'pending_qty' => $pendingQty,
            'unit_price' => $salesOrderLine->unit_price,
            'account_id' => $salesOrderLine->account_id,
            'inventory_item_id' => $inventoryItemId
        ]);

        // Create delivery order line
        try {
            $deliveryOrderLine = DeliveryOrderLine::create([
                'delivery_order_id' => $deliveryOrder->id,
                'sales_order_line_id' => $salesOrderLine->id,
                'inventory_item_id' => $inventoryItemId,
                'account_id' => $salesOrderLine->account_id,
                'item_code' => $inventoryItem ? $inventoryItem->code : $salesOrderLine->item_code,
                'item_name' => $inventoryItem ? $inventoryItem->name : $salesOrderLine->item_name,
                'description' => $salesOrderLine->description,
                'ordered_qty' => $pendingQty, // Use pending quantity
                'reserved_qty' => 0,
                'picked_qty' => 0,
                'delivered_qty' => 0,
                'unit_price' => $salesOrderLine->unit_price,
                'amount' => $pendingQty * $salesOrderLine->unit_price, // Recalculate based on pending qty
                'tax_code_id' => $salesOrderLine->tax_code_id,
                'warehouse_location' => $data['warehouse_location'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            Log::info('DeliveryService: Delivery order line created', ['delivery_order_line_id' => $deliveryOrderLine->id]);
        } catch (\Exception $e) {
            Log::error('DeliveryService: Failed to create delivery order line', [
                'error' => $e->getMessage(),
                'delivery_order_id' => $deliveryOrder->id,
                'sales_order_line_id' => $salesOrderLine->id
            ]);
            throw $e;
        }

        // Reserve inventory if it's an inventory item
        if ($inventoryItemId) {
            $this->reserveInventory($deliveryOrderLine);
        }

        return $deliveryOrderLine;
    }

    public function createDeliveryTracking($deliveryOrder)
    {
        try {
            Log::info('DeliveryService: Creating delivery tracking record', ['delivery_order_id' => $deliveryOrder->id]);
            $tracking = DeliveryTracking::create([
                'delivery_order_id' => $deliveryOrder->id,
                'delivery_attempts' => 0,
            ]);
            Log::info('DeliveryService: Delivery tracking record created', ['tracking_id' => $tracking->id]);
            return $tracking;
        } catch (\Exception $e) {
            Log::error('DeliveryService: Failed to create delivery tracking record', [
                'error' => $e->getMessage(),
                'delivery_order_id' => $deliveryOrder->id
            ]);
            throw $e;
        }
    }

    public function reserveInventory($deliveryOrderLine)
    {
        Log::info('DeliveryService: Starting reserveInventory', [
            'delivery_order_line_id' => $deliveryOrderLine->id,
            'inventory_item_id' => $deliveryOrderLine->inventory_item_id
        ]);

        if (!$deliveryOrderLine->inventory_item_id) {
            Log::info('DeliveryService: No inventory_item_id, skipping reservation');
            return;
        }

        $inventoryItem = InventoryItem::find($deliveryOrderLine->inventory_item_id);

        if (!$inventoryItem) {
            Log::error('DeliveryService: Inventory item not found', [
                'inventory_item_id' => $deliveryOrderLine->inventory_item_id
            ]);
            throw new \Exception('Inventory item not found');
        }

        Log::info('DeliveryService: Inventory item found for reservation', [
            'inventory_item_id' => $inventoryItem->id,
            'item_code' => $inventoryItem->code
        ]);

        // Check if sufficient stock is available (skip for now since current_stock column doesn't exist)
        // if ($inventoryItem->current_stock < $deliveryOrderLine->ordered_qty) {
        //     throw new \Exception("Insufficient stock for {$inventoryItem->name}. Available: {$inventoryItem->current_stock}, Required: {$deliveryOrderLine->ordered_qty}");
        // }

        // Reserve the inventory
        Log::info('DeliveryService: Reserving inventory', [
            'delivery_order_line_id' => $deliveryOrderLine->id,
            'ordered_qty' => $deliveryOrderLine->ordered_qty
        ]);

        $deliveryOrderLine->update([
            'reserved_qty' => $deliveryOrderLine->ordered_qty
        ]);

        Log::info('DeliveryService: Inventory reserved successfully', [
            'delivery_order_line_id' => $deliveryOrderLine->id,
            'reserved_qty' => $deliveryOrderLine->ordered_qty
        ]);

        // Update inventory item (if you have reserved stock tracking)
        // $inventoryItem->decrement('current_stock', $deliveryOrderLine->ordered_qty);
        // $inventoryItem->increment('reserved_stock', $deliveryOrderLine->ordered_qty);
    }

    public function updatePickingStatus($deliveryOrderLineId, $pickedQty)
    {
        return DB::transaction(function () use ($deliveryOrderLineId, $pickedQty) {
            $deliveryOrderLine = DeliveryOrderLine::with('inventoryItem')->findOrFail($deliveryOrderLineId);

            if (!$deliveryOrderLine->canPickQuantity($pickedQty)) {
                throw new \Exception('Invalid picking quantity');
            }

            $deliveryOrderLine->updatePickedQuantity($pickedQty);

            $this->ensureInventoryReduction($deliveryOrderLine);

            $this->updateDeliveryOrderStatus($deliveryOrderLine->deliveryOrder);

            return $deliveryOrderLine->fresh();
        });
    }

    public function updateDeliveryStatus($deliveryOrderLineId, $deliveredQty)
    {
        return DB::transaction(function () use ($deliveryOrderLineId, $deliveredQty) {
            $deliveryOrderLine = DeliveryOrderLine::with('inventoryItem')->findOrFail($deliveryOrderLineId);

            if (!$deliveryOrderLine->canDeliverQuantity($deliveredQty)) {
                throw new \Exception('Invalid delivery quantity');
            }

            $deliveryOrderLine->updateDeliveredQuantity($deliveredQty);

            $this->ensureInventoryReduction($deliveryOrderLine);

            if ($deliveryOrderLine->sales_order_line_id) {
                $this->syncSalesOrderLineFromDeliveries($deliveryOrderLine->sales_order_line_id);
            }

            $this->updateDeliveryOrderStatus($deliveryOrderLine->deliveryOrder);

            return $deliveryOrderLine->fresh();
        });
    }

    private function getDeliveredQtyForSalesOrderLine(int $salesOrderLineId): float
    {
        return (float) DeliveryOrderLine::where('sales_order_line_id', $salesOrderLineId)
            ->whereHas('deliveryOrder', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('delivered_qty');
    }

    private function getAllocatedQtyForSalesOrderLine(int $salesOrderLineId): float
    {
        $lines = DeliveryOrderLine::where('sales_order_line_id', $salesOrderLineId)
            ->whereHas('deliveryOrder', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->get();
        return (float) $lines->sum(fn($l) => max((float) $l->picked_qty, (float) $l->delivered_qty));
    }

    private function syncSalesOrderLineFromDeliveries(int $salesOrderLineId): void
    {
        $line = SalesOrderLine::find($salesOrderLineId);
        if (!$line) {
            return;
        }
        $delivered = $this->getDeliveredQtyForSalesOrderLine($salesOrderLineId);
        $line->updateDeliveredQuantity($delivered);
    }

    private function ensureInventoryReduction(DeliveryOrderLine $line): void
    {
        if (!$line->inventory_item_id || !$line->inventoryItem) {
            return;
        }

        $alreadyReduced = (int) abs(
            InventoryTransaction::where('reference_type', 'delivery_order_line')
                ->where('reference_id', $line->id)
                ->where('transaction_type', 'sale')
                ->sum('quantity')
        );

        $shouldReduce = (int) round(max($line->picked_qty, $line->delivered_qty));
        $delta = $shouldReduce - $alreadyReduced;

        if ($delta <= 0) {
            return;
        }

        $unitCost = $this->inventoryService->calculateUnitCost($line->inventoryItem);
        $do = $line->deliveryOrder;

        $this->inventoryService->processSaleTransaction(
            $line->inventory_item_id,
            $delta,
            (float) $unitCost,
            'delivery_order_line',
            $line->id,
            "Picked/Delivered from DO {$do->do_number} - {$line->item_name}"
        );
    }

    /**
     * Complete delivery and create revenue recognition journal entry
     */
    public function completeDelivery($deliveryOrderId, $actualDeliveryDate = null)
    {
        return DB::transaction(function () use ($deliveryOrderId, $actualDeliveryDate) {
            $deliveryOrder = DeliveryOrder::findOrFail($deliveryOrderId);

            if ($deliveryOrder->status !== 'delivered') {
                throw new \Exception('Delivery Order must be in delivered status to complete.');
            }

            // Update actual delivery date if provided
            if ($actualDeliveryDate) {
                $deliveryOrder->update(['actual_delivery_date' => $actualDeliveryDate]);
            }

            // Create revenue recognition journal entry
            try {
                $this->deliveryJournalService->createRevenueRecognition($deliveryOrder);

                // Update status to completed
                $deliveryOrder->update(['status' => 'completed']);
            } catch (\Exception $e) {
                Log::error('Failed to create revenue recognition journal entry: ' . $e->getMessage());
                throw $e;
            }

            return $deliveryOrder;
        });
    }

    public function updateDeliveryOrderStatus($deliveryOrder)
    {
        $lines = $deliveryOrder->lines;

        if ($lines->every(fn($line) => $line->status === 'delivered')) {
            $deliveryOrder->updateStatus('delivered');
        } elseif ($lines->some(fn($line) => $line->status === 'delivered')) {
            $deliveryOrder->updateStatus('partial_delivered');
        } elseif ($lines->every(fn($line) => $line->status === 'picked')) {
            $deliveryOrder->updateStatus('ready');
        } elseif ($lines->some(fn($line) => $line->status === 'picked')) {
            $deliveryOrder->updateStatus('picking');
        }
    }

    public function approveDeliveryOrder($deliveryOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($deliveryOrderId, $userId, $comments) {
            $deliveryOrder = DeliveryOrder::findOrFail($deliveryOrderId);

            $deliveryOrder->update([
                'approval_status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'status' => 'picking', // Move to picking stage after approval
                'notes' => $comments ? $deliveryOrder->notes . "\nApproval: " . $comments : $deliveryOrder->notes
            ]);

            // Create inventory reservation journal entry
            try {
                $this->deliveryJournalService->createInventoryReservation($deliveryOrder);
            } catch (\Exception $e) {
                // Log the error but don't fail the approval
                Log::error('Failed to create inventory reservation journal entry: ' . $e->getMessage());
            }

            return $deliveryOrder;
        });
    }

    public function rejectDeliveryOrder($deliveryOrderId, $userId, $comments = null)
    {
        $deliveryOrder = DeliveryOrder::findOrFail($deliveryOrderId);

        $deliveryOrder->update([
            'approval_status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'notes' => $comments ? $deliveryOrder->notes . "\nRejection: " . $comments : $deliveryOrder->notes
        ]);

        return $deliveryOrder;
    }

    public function cancelDeliveryOrder($deliveryOrderId, $reason = null)
    {
        return DB::transaction(function () use ($deliveryOrderId, $reason) {
            $deliveryOrder = DeliveryOrder::with('lines.inventoryItem')->findOrFail($deliveryOrderId);

            if (!$deliveryOrder->canBeCancelled()) {
                throw new \Exception('Delivery Order cannot be cancelled in current status');
            }

            foreach ($deliveryOrder->lines as $line) {
                if ($line->reserved_qty > 0) {
                    $this->releaseInventory($line);
                }
                if ($line->picked_qty > 0 && $line->inventory_item_id && $line->inventoryItem) {
                    $qtyToRestore = (int) round($line->picked_qty);
                    if ($qtyToRestore > 0) {
                        $unitCost = $this->inventoryService->calculateUnitCost($line->inventoryItem);
                        $this->inventoryService->processAdjustmentTransaction(
                            $line->inventory_item_id,
                            $qtyToRestore,
                            (float) $unitCost,
                            "DO {$deliveryOrder->do_number} cancelled - stock returned for {$line->item_name}"
                        );
                    }
                }
            }

            $deliveryOrder->update([
                'status' => 'cancelled',
                'notes' => $reason ? $deliveryOrder->notes . "\nCancellation: " . $reason : $deliveryOrder->notes
            ]);

            foreach ($deliveryOrder->lines as $line) {
                if ($line->sales_order_line_id) {
                    $this->syncSalesOrderLineFromDeliveries($line->sales_order_line_id);
                }
            }

            return $deliveryOrder;
        });
    }

    public function releaseInventory($deliveryOrderLine)
    {
        if (!$deliveryOrderLine->inventory_item_id) {
            return;
        }

        $inventoryItem = InventoryItem::find($deliveryOrderLine->inventory_item_id);

        if ($inventoryItem && $deliveryOrderLine->reserved_qty > 0) {
            // Release the reserved inventory
            // $inventoryItem->increment('current_stock', $deliveryOrderLine->reserved_qty);
            // $inventoryItem->decrement('reserved_stock', $deliveryOrderLine->reserved_qty);

            $deliveryOrderLine->update(['reserved_qty' => 0]);
        }
    }

    public function canCreateDeliveryOrder($salesOrder)
    {
        if ($salesOrder->approval_status !== 'approved' || $salesOrder->order_type !== 'item') {
            return false;
        }
        return in_array($salesOrder->status, ['confirmed', 'processing']);
    }

    public function getDeliveryPerformanceMetrics($dateFrom = null, $dateTo = null)
    {
        $query = DeliveryTracking::with('deliveryOrder');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $trackings = $query->get();

        return [
            'total_deliveries' => $trackings->count(),
            'on_time_deliveries' => $trackings->where('delivery_efficiency', 'on_time')->count(),
            'delayed_deliveries' => $trackings->whereIn('delivery_efficiency', ['slightly_delayed', 'significantly_delayed'])->count(),
            'average_delivery_time' => $trackings->avg('total_delivery_time'),
            'average_satisfaction_score' => $trackings->avg('customer_satisfaction_score'),
            'total_logistics_cost' => $trackings->sum('total_logistics_cost'),
            'average_cost_per_km' => $trackings->avg('cost_per_km'),
        ];
    }
}
