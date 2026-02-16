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
            $docDate = isset($data['planned_delivery_date']) ? $data['planned_delivery_date'] : now()->toDateString();

            $customer = $salesOrder->businessPartner;
            $customerAddress = $salesOrder->delivery_address ?? ($customer?->default_shipping_address);
            $customerContact = $salesOrder->delivery_contact_person ?? ($customer?->primary_contact_name);
            $customerPhone = $salesOrder->delivery_phone ?? ($customer?->primary_contact_phone);

            $doNumber = $this->documentNumberingService->generateNumber('delivery_order', $docDate, [
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

            $linesCreated = 0;
            $formLines = $data['lines'] ?? null;

            if (!empty($formLines)) {
                $solById = $salesOrder->lines->keyBy('id');
                foreach ($formLines as $lineData) {
                    $solId = (int) ($lineData['sales_order_line_id'] ?? 0);
                    $qty = (float) ($lineData['qty'] ?? 0);
                    if ($solId <= 0 || $qty <= 0) {
                        continue;
                    }
                    $salesOrderLine = $solById->get($solId);
                    if (!$salesOrderLine || !$salesOrderLine->inventory_item_id) {
                        continue;
                    }
                    $doLine = $this->createDeliveryOrderLine($do, $salesOrderLine, $data, $qty);
                    if ($doLine !== null) {
                        $linesCreated++;
                    }
                }
            } else {
                foreach ($salesOrder->lines as $salesOrderLine) {
                    if (!$salesOrderLine->inventory_item_id) {
                        continue;
                    }
                    $doLine = $this->createDeliveryOrderLine($do, $salesOrderLine, $data);
                    if ($doLine !== null) {
                        $linesCreated++;
                    }
                }
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

    public function createDeliveryOrderLine($deliveryOrder, $salesOrderLine, $data = [], ?float $requestedQty = null)
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
        $pendingQty = max(0, (float) $salesOrderLine->qty - $alreadyAllocated);
        if ($pendingQty <= 0) {
            Log::info('DeliveryService: Skipping line with no remaining qty', ['sales_order_line_id' => $salesOrderLine->id]);
            return null;
        }

        $orderedQty = $requestedQty !== null
            ? min((float) $requestedQty, $pendingQty)
            : $pendingQty;
        if ($orderedQty <= 0) {
            return null;
        }

        Log::info('DeliveryService: Creating delivery order line', [
            'ordered_qty' => $orderedQty,
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
                'ordered_qty' => $orderedQty,
                'reserved_qty' => 0,
                'picked_qty' => 0,
                'delivered_qty' => 0,
                'unit_price' => $salesOrderLine->unit_price,
                'amount' => $orderedQty * $salesOrderLine->unit_price,
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

    public function getDeliveredQtyForSalesOrderLineExcludingDo(int $salesOrderLineId, ?int $excludeDeliveryOrderId): float
    {
        $query = DeliveryOrderLine::where('sales_order_line_id', $salesOrderLineId)
            ->whereHas('deliveryOrder', fn($q) => $q->where('status', '!=', 'cancelled'));
        if ($excludeDeliveryOrderId !== null) {
            $query->where('delivery_order_id', '!=', $excludeDeliveryOrderId);
        }
        return (float) $query->sum('delivered_qty');
    }

    private function getAllocatedQtyForSalesOrderLine(int $salesOrderLineId): float
    {
        $lines = DeliveryOrderLine::where('sales_order_line_id', $salesOrderLineId)
            ->whereHas('deliveryOrder', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->get();
        return (float) $lines->sum(fn($l) => max((float) $l->picked_qty, (float) $l->delivered_qty));
    }

    private function getAllocatedQtyForSalesOrderLineExcludingDo(int $salesOrderLineId, ?int $excludeDeliveryOrderId): float
    {
        $query = DeliveryOrderLine::where('sales_order_line_id', $salesOrderLineId)
            ->whereHas('deliveryOrder', fn($q) => $q->where('status', '!=', 'cancelled'));
        if ($excludeDeliveryOrderId !== null) {
            $query->where('delivery_order_id', '!=', $excludeDeliveryOrderId);
        }
        $lines = $query->get();
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
            "Picked/Delivered from DO {$do->do_number} - {$line->item_name}",
            $do->warehouse_id
        );
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
            $deliveryOrder = DeliveryOrder::with('lines.inventoryItem')->findOrFail($deliveryOrderId);

            $deliveryOrder->update([
                'approval_status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'status' => 'in_transit',
                'notes' => $comments ? $deliveryOrder->notes . "\nApproval: " . $comments : $deliveryOrder->notes
            ]);

            foreach ($deliveryOrder->lines as $line) {
                $line->update(['picked_qty' => $line->ordered_qty, 'status' => 'picked']);
            }

            $this->reduceStockOnApproval($deliveryOrder);

            try {
                $this->deliveryJournalService->createInventoryReservation($deliveryOrder);
            } catch (\Exception $e) {
                Log::error('Failed to create inventory reservation journal entry: ' . $e->getMessage());
            }

            return $deliveryOrder;
        });
    }

    private function reduceStockOnApproval(DeliveryOrder $deliveryOrder): void
    {
        foreach ($deliveryOrder->lines as $line) {
            if (!$line->inventory_item_id || !$line->inventoryItem) {
                continue;
            }

            $alreadyReduced = (int) abs(
                InventoryTransaction::where('reference_type', 'delivery_order_line')
                    ->where('reference_id', $line->id)
                    ->where('transaction_type', 'sale')
                    ->sum('quantity')
            );

            $shouldReduce = (int) round($line->ordered_qty);
            $delta = $shouldReduce - $alreadyReduced;

            if ($delta <= 0) {
                continue;
            }

            $unitCost = $this->inventoryService->calculateUnitCost($line->inventoryItem);

            $this->inventoryService->processSaleTransaction(
                $line->inventory_item_id,
                $delta,
                (float) $unitCost,
                'delivery_order_line',
                $line->id,
                "Approved DO {$deliveryOrder->do_number} - {$line->item_name} (on the way)",
                $deliveryOrder->warehouse_id
            );
        }
    }

    public function markAsDelivered($deliveryOrderId, $deliveredAt, $deliveredBy)
    {
        return DB::transaction(function () use ($deliveryOrderId, $deliveredAt, $deliveredBy) {
            $deliveryOrder = DeliveryOrder::with('lines.inventoryItem')->findOrFail($deliveryOrderId);

            if (!in_array($deliveryOrder->status, ['in_transit', 'ready'])) {
                throw new \Exception('Delivery Order must be in transit or ready status to mark as delivered.');
            }

            if ($deliveryOrder->approval_status !== 'approved') {
                throw new \Exception('Delivery Order must be approved to mark as delivered.');
            }

            $deliveredAtCarbon = \Carbon\Carbon::parse($deliveredAt);

            foreach ($deliveryOrder->lines as $line) {
                $line->update([
                    'delivered_qty' => $line->ordered_qty,
                    'status' => 'delivered',
                ]);
            }

            $deliveryOrder->update([
                'status' => 'delivered',
                'delivered_at' => $deliveredAtCarbon,
                'delivered_by' => $deliveredBy,
                'actual_delivery_date' => $deliveredAtCarbon->toDateString(),
            ]);

            foreach ($deliveryOrder->lines as $line) {
                if ($line->sales_order_line_id) {
                    $this->syncSalesOrderLineFromDeliveries($line->sales_order_line_id);
                }
            }

            try {
                $this->deliveryJournalService->createRevenueRecognition($deliveryOrder);
                $deliveryOrder->update(['status' => 'completed']);
            } catch (\Exception $e) {
                Log::error('Failed to create revenue recognition journal entry: ' . $e->getMessage());
                throw $e;
            }

            return $deliveryOrder->fresh();
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

    public function getRemainingLinesForSalesOrder(int $salesOrderId, ?int $excludeDeliveryOrderId = null): array
    {
        $salesOrder = SalesOrder::with(['lines.inventoryItem'])->findOrFail($salesOrderId);
        $result = [];
        foreach ($salesOrder->lines as $line) {
            if (!$line->inventory_item_id) {
                continue;
            }
            $orderedQty = (float) $line->qty;
            $allocated = $excludeDeliveryOrderId !== null
                ? $this->getAllocatedQtyForSalesOrderLineExcludingDo($line->id, $excludeDeliveryOrderId)
                : $this->getAllocatedQtyForSalesOrderLine($line->id);
            $deliveredByOthers = $this->getDeliveredQtyForSalesOrderLineExcludingDo($line->id, $excludeDeliveryOrderId);
            $remainQty = max(0, $orderedQty - $deliveredByOthers);
            $maxQty = max(0, $orderedQty - $allocated);
            if ($maxQty <= 0 && $excludeDeliveryOrderId === null) {
                continue;
            }
            if ($excludeDeliveryOrderId !== null && $remainQty <= 0) {
                continue;
            }
            $result[] = [
                'sales_order_line_id' => $line->id,
                'item_code' => $line->inventoryItem->code ?? $line->item_code ?? 'N/A',
                'item_name' => $line->inventoryItem->name ?? $line->item_name ?? ($line->description ?? 'N/A'),
                'ordered_qty' => $orderedQty,
                'remaining_qty' => $remainQty,
                'max_qty' => $maxQty,
            ];
        }
        return $result;
    }

    public function getRemainQtyByLineForDeliveryOrder(DeliveryOrder $deliveryOrder): array
    {
        $result = [];
        foreach ($deliveryOrder->lines as $line) {
            if ($line->sales_order_line_id) {
                $sol = SalesOrderLine::find($line->sales_order_line_id);
                $orderedQty = $sol ? (float) $sol->qty : 0;
                $deliveredByOthers = $this->getDeliveredQtyForSalesOrderLineExcludingDo($line->sales_order_line_id, $deliveryOrder->id);
                $result[$line->sales_order_line_id] = max(0, $orderedQty - $deliveredByOthers);
            }
        }
        return $result;
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
