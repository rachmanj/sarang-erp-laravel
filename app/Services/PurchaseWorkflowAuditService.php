<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderApproval;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;

class PurchaseWorkflowAuditService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function logStatusChange(PurchaseOrder $po, string $oldStatus, string $newStatus, ?string $reason = null)
    {
        $description = "Purchase Order #{$po->order_no} status changed from '{$oldStatus}' to '{$newStatus}'";
        if ($reason) {
            $description .= ". Reason: {$reason}";
        }

        $this->auditLogService->log(
            'status_changed',
            'purchase_order',
            $po->id,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $description
        );
    }

    public function logApproval(PurchaseOrderApproval $approval, string $action, ?string $comments = null)
    {
        $po = $approval->purchaseOrder;
        $user = $approval->user;
        $level = $approval->approval_level;

        $description = "Purchase Order #{$po->order_no} - Level {$level} {$action} by {$user->name}";
        if ($comments) {
            $description .= ". Comments: {$comments}";
        }

        $this->auditLogService->log(
            $action === 'approved' ? 'approved' : 'rejected',
            'purchase_order',
            $po->id,
            ['approval_status' => 'pending'],
            ['approval_status' => $action === 'approved' ? 'approved' : 'rejected'],
            $description
        );
    }

    public function logAmountChange(PurchaseOrder $po, array $oldAmounts, array $newAmounts)
    {
        $changes = [];
        foreach ($newAmounts as $key => $newValue) {
            $oldValue = $oldAmounts[$key] ?? 0;
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'difference' => $newValue - $oldValue,
                ];
            }
        }

        if (empty($changes)) {
            return;
        }

        $changeSummary = [];
        foreach ($changes as $key => $change) {
            $changeSummary[] = "{$key}: " . number_format($change['old'], 2) . " → " . number_format($change['new'], 2);
        }

        $description = "Purchase Order #{$po->order_no} amounts changed: " . implode(', ', $changeSummary);

        $this->auditLogService->log(
            'amount_changed',
            'purchase_order',
            $po->id,
            $oldAmounts,
            $newAmounts,
            $description
        );
    }

    public function logLineItemChange(PurchaseOrder $po, PurchaseOrderLine $line, string $action, ?array $oldData = null, ?array $newData = null)
    {
        $itemName = $line->inventoryItem->name ?? ($line->item_name ?? "Item #{$line->inventory_item_id}");
        
        switch ($action) {
            case 'added':
                $description = "Purchase Order #{$po->order_no} - Line item added: {$itemName} (Qty: {$line->qty}, Price: " . number_format($line->unit_price, 2) . ")";
                break;
            case 'updated':
                $changes = [];
                if ($oldData && $newData) {
                    foreach ($newData as $key => $newValue) {
                        $oldValue = $oldData[$key] ?? null;
                        if ($oldValue != $newValue) {
                            $changes[] = "{$key}: {$oldValue} → {$newValue}";
                        }
                    }
                }
                $changeStr = !empty($changes) ? " (" . implode(', ', $changes) . ")" : "";
                $description = "Purchase Order #{$po->order_no} - Line item updated: {$itemName}{$changeStr}";
                break;
            case 'removed':
                $description = "Purchase Order #{$po->order_no} - Line item removed: {$itemName}";
                break;
            default:
                $description = "Purchase Order #{$po->order_no} - Line item {$action}: {$itemName}";
        }

        $this->auditLogService->log(
            'line_item_' . $action,
            'purchase_order',
            $po->id,
            $oldData,
            $newData,
            $description
        );
    }

    public function logGRPOCreation(PurchaseOrder $po, $grpoId)
    {
        $description = "Purchase Order #{$po->order_no} - Goods Receipt PO created (GRPO ID: {$grpoId})";

        $this->auditLogService->log(
            'grpo_created',
            'purchase_order',
            $po->id,
            null,
            ['grpo_id' => $grpoId],
            $description
        );
    }

    public function logPurchaseInvoiceCreation(PurchaseOrder $po, $invoiceId)
    {
        $description = "Purchase Order #{$po->order_no} - Purchase Invoice created (Invoice ID: {$invoiceId})";

        $this->auditLogService->log(
            'purchase_invoice_created',
            'purchase_order',
            $po->id,
            null,
            ['invoice_id' => $invoiceId],
            $description
        );
    }

    public function logPurchasePaymentCreation(PurchaseOrder $po, $paymentId)
    {
        $description = "Purchase Order #{$po->order_no} - Purchase Payment created (Payment ID: {$paymentId})";

        $this->auditLogService->log(
            'purchase_payment_created',
            'purchase_order',
            $po->id,
            null,
            ['payment_id' => $paymentId],
            $description
        );
    }
}

