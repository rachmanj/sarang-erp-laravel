<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\Accounting\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceService
{
    /**
     * Get inventory account for an inventory item
     * Uses product category account mapping with inheritance support
     */
    public function getAccountForItem(InventoryItem $item): ?Account
    {
        if (!$item->category) {
            throw new \Exception("Item '{$item->name}' has no product category assigned. Please assign a category first.");
        }

        $account = $item->getAccountByType('inventory');
        
        if (!$account) {
            throw new \Exception("No inventory account configured for item '{$item->name}' (Category: {$item->category->name}). Please configure account mapping in Product Category.");
        }
        
        return $account;
    }

    /**
     * Get inventory account ID for an inventory item
     */
    public function getAccountIdForItem(InventoryItem $item): int
    {
        $account = $this->getAccountForItem($item);
        return $account->id;
    }

    /**
     * Create inventory transaction for direct purchase
     */
    public function createInventoryTransaction(
        PurchaseInvoiceLine $line,
        PurchaseInvoice $invoice
    ): ?InventoryTransaction {
        if (!$line->inventory_item_id) {
            return null; // Service items or account-only lines don't create inventory transactions
        }

        $item = $line->inventoryItem;
        if (!$item) {
            return null;
        }

        // Use warehouse from line, or default warehouse from item
        $warehouseId = $line->warehouse_id ?? $item->default_warehouse_id;

        return app(InventoryService::class)->processPurchaseTransaction(
            itemId: $line->inventory_item_id,
            quantity: $line->qty,
            unitCost: $line->unit_price,
            referenceType: 'purchase_invoice',
            referenceId: $invoice->id,
            notes: "Direct purchase from " . ($invoice->businessPartner->name ?? 'Unknown'),
            warehouseId: $warehouseId
        );
    }

    /**
     * Auto-select account for invoice line based on inventory item
     * Falls back to provided account_id if item is not set
     */
    public function resolveAccountForLine(array $lineData): int
    {
        // If account_id is provided, use it (for accounting users or service items)
        if (!empty($lineData['account_id'])) {
            return (int) $lineData['account_id'];
        }

        // If inventory_item_id is provided, auto-select account from item category
        if (!empty($lineData['inventory_item_id'])) {
            $item = InventoryItem::find($lineData['inventory_item_id']);
            if ($item) {
                return $this->getAccountIdForItem($item);
            }
        }

        throw new \Exception('Either account_id or inventory_item_id must be provided for invoice line.');
    }

    /**
     * Validate that warehouse is provided for inventory items
     */
    public function validateWarehouseForItem(int $inventoryItemId, ?int $warehouseId): void
    {
        if (!$warehouseId) {
            $item = InventoryItem::find($inventoryItemId);
            if ($item && !$item->default_warehouse_id) {
                throw new \Exception("Warehouse is required for item '{$item->name}'. Please select a warehouse or set default warehouse for the item.");
            }
        }
    }
}

