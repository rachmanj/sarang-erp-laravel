<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\DeliveryTracking;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\InventoryItem;
use App\Services\DocumentNumberingService;
use App\Services\DeliveryJournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DeliveryService
{
    protected $documentNumberingService;
    protected $deliveryJournalService;

    public function __construct(
        DocumentNumberingService $documentNumberingService,
        DeliveryJournalService $deliveryJournalService
    ) {
        $this->documentNumberingService = $documentNumberingService;
        $this->deliveryJournalService = $deliveryJournalService;
    }

    public function createDeliveryOrderFromSalesOrder($salesOrderId, $data = [])
    {
        return DB::transaction(function () use ($salesOrderId, $data) {
            $salesOrder = SalesOrder::with(['lines', 'customer'])->findOrFail($salesOrderId);

            // Check if sales order can be converted to delivery order
            if (!$this->canCreateDeliveryOrder($salesOrder)) {
                throw new \Exception('Sales Order cannot be converted to Delivery Order');
            }

            // Create delivery order
            $do = DeliveryOrder::create([
                'do_number' => null, // Will be generated after creation
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'delivery_address' => $data['delivery_address'] ?? $salesOrder->customer->address,
                'delivery_contact_person' => $data['delivery_contact_person'] ?? $salesOrder->customer->contact_person,
                'delivery_phone' => $data['delivery_phone'] ?? $salesOrder->customer->phone,
                'planned_delivery_date' => $data['planned_delivery_date'] ?? $salesOrder->expected_delivery_date,
                'delivery_method' => $data['delivery_method'] ?? 'own_fleet',
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'logistics_cost' => $data['logistics_cost'] ?? 0,
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Generate DO number
            $doNumber = $this->documentNumberingService->generateNumber('delivery_order', $do->created_at->toDateString());
            $do->update(['do_number' => $doNumber]);

            // Create delivery order lines from sales order lines
            foreach ($salesOrder->lines as $salesOrderLine) {
                $this->createDeliveryOrderLine($do, $salesOrderLine, $data);
            }

            // Create delivery tracking record
            $this->createDeliveryTracking($do);

            // Update sales order status
            $salesOrder->update(['status' => 'processing']);

            return $do;
        });
    }

    public function createDeliveryOrderLine($deliveryOrder, $salesOrderLine, $data = [])
    {
        $deliveryOrderLine = DeliveryOrderLine::create([
            'delivery_order_id' => $deliveryOrder->id,
            'sales_order_line_id' => $salesOrderLine->id,
            'inventory_item_id' => $salesOrderLine->inventory_item_id,
            'account_id' => $salesOrderLine->account_id,
            'item_code' => $salesOrderLine->item_code,
            'item_name' => $salesOrderLine->item_name,
            'description' => $salesOrderLine->description,
            'ordered_qty' => $salesOrderLine->qty,
            'reserved_qty' => 0,
            'picked_qty' => 0,
            'delivered_qty' => 0,
            'unit_price' => $salesOrderLine->unit_price,
            'amount' => $salesOrderLine->amount,
            'warehouse_location' => $data['warehouse_location'] ?? null,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        // Reserve inventory if it's an inventory item
        if ($salesOrderLine->inventory_item_id) {
            $this->reserveInventory($deliveryOrderLine);
        }

        return $deliveryOrderLine;
    }

    public function createDeliveryTracking($deliveryOrder)
    {
        return DeliveryTracking::create([
            'delivery_order_id' => $deliveryOrder->id,
            'delivery_attempts' => 0,
        ]);
    }

    public function reserveInventory($deliveryOrderLine)
    {
        if (!$deliveryOrderLine->inventory_item_id) {
            return;
        }

        $inventoryItem = InventoryItem::find($deliveryOrderLine->inventory_item_id);

        if (!$inventoryItem) {
            throw new \Exception('Inventory item not found');
        }

        // Check if sufficient stock is available
        if ($inventoryItem->current_stock < $deliveryOrderLine->ordered_qty) {
            throw new \Exception("Insufficient stock for {$inventoryItem->name}. Available: {$inventoryItem->current_stock}, Required: {$deliveryOrderLine->ordered_qty}");
        }

        // Reserve the inventory
        $deliveryOrderLine->update([
            'reserved_qty' => $deliveryOrderLine->ordered_qty
        ]);

        // Update inventory item (if you have reserved stock tracking)
        // $inventoryItem->decrement('current_stock', $deliveryOrderLine->ordered_qty);
        // $inventoryItem->increment('reserved_stock', $deliveryOrderLine->ordered_qty);
    }

    public function updatePickingStatus($deliveryOrderLineId, $pickedQty)
    {
        $deliveryOrderLine = DeliveryOrderLine::findOrFail($deliveryOrderLineId);

        if (!$deliveryOrderLine->canPickQuantity($pickedQty)) {
            throw new \Exception('Invalid picking quantity');
        }

        $deliveryOrderLine->updatePickedQuantity($pickedQty);

        // Update delivery order status based on line statuses
        $this->updateDeliveryOrderStatus($deliveryOrderLine->deliveryOrder);

        return $deliveryOrderLine;
    }

    public function updateDeliveryStatus($deliveryOrderLineId, $deliveredQty)
    {
        $deliveryOrderLine = DeliveryOrderLine::findOrFail($deliveryOrderLineId);

        if (!$deliveryOrderLine->canDeliverQuantity($deliveredQty)) {
            throw new \Exception('Invalid delivery quantity');
        }

        $deliveryOrderLine->updateDeliveredQuantity($deliveredQty);

        // Update delivery order status based on line statuses
        $this->updateDeliveryOrderStatus($deliveryOrderLine->deliveryOrder);

        return $deliveryOrderLine;
    }

    /**
     * Complete delivery and create revenue recognition journal entry
     */
    public function completeDelivery($deliveryOrderId, $actualDeliveryDate = null)
    {
        return DB::transaction(function () use ($deliveryOrderId, $actualDeliveryDate) {
            $deliveryOrder = DeliveryOrder::findOrFail($deliveryOrderId);

            if ($deliveryOrder->status !== 'delivered') {
                throw new Exception('Delivery Order must be in delivered status to complete.');
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
            } catch (Exception $e) {
                \Log::error('Failed to create revenue recognition journal entry: ' . $e->getMessage());
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
            } catch (Exception $e) {
                // Log the error but don't fail the approval
                \Log::error('Failed to create inventory reservation journal entry: ' . $e->getMessage());
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
        $deliveryOrder = DeliveryOrder::findOrFail($deliveryOrderId);

        if (!$deliveryOrder->canBeCancelled()) {
            throw new \Exception('Delivery Order cannot be cancelled in current status');
        }

        // Release reserved inventory
        foreach ($deliveryOrder->lines as $line) {
            if ($line->reserved_qty > 0) {
                $this->releaseInventory($line);
            }
        }

        $deliveryOrder->update([
            'status' => 'cancelled',
            'notes' => $reason ? $deliveryOrder->notes . "\nCancellation: " . $reason : $deliveryOrder->notes
        ]);

        return $deliveryOrder;
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
        return $salesOrder->approval_status === 'approved' &&
            $salesOrder->status === 'confirmed' &&
            $salesOrder->order_type === 'item';
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
