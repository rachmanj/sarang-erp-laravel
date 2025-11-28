<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesOrderApproval;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;

class SalesWorkflowAuditService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function logStatusChange(SalesOrder $so, string $oldStatus, string $newStatus, ?string $reason = null)
    {
        $description = "Sales Order #{$so->order_no} status changed from '{$oldStatus}' to '{$newStatus}'";
        if ($reason) {
            $description .= ". Reason: {$reason}";
        }

        $this->auditLogService->log(
            'status_changed',
            'sales_order',
            $so->id,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $description
        );
    }

    public function logApproval(SalesOrderApproval $approval, string $action, ?string $comments = null)
    {
        $so = $approval->salesOrder;
        $user = $approval->user;
        $level = $approval->approval_level;

        $description = "Sales Order #{$so->order_no} - Level {$level} {$action} by {$user->name}";
        if ($comments) {
            $description .= ". Comments: {$comments}";
        }

        $this->auditLogService->log(
            $action === 'approved' ? 'approved' : 'rejected',
            'sales_order',
            $so->id,
            ['approval_status' => 'pending'],
            ['approval_status' => $action === 'approved' ? 'approved' : 'rejected'],
            $description
        );
    }

    public function logAmountChange(SalesOrder $so, array $oldAmounts, array $newAmounts)
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

        $description = "Sales Order #{$so->order_no} amounts changed: " . implode(', ', $changeSummary);

        $this->auditLogService->log(
            'amount_changed',
            'sales_order',
            $so->id,
            $oldAmounts,
            $newAmounts,
            $description
        );
    }

    public function logLineItemChange(SalesOrder $so, SalesOrderLine $line, string $action, ?array $oldData = null, ?array $newData = null)
    {
        $itemName = $line->inventoryItem->name ?? ($line->item_name ?? "Item #{$line->inventory_item_id}");
        
        switch ($action) {
            case 'added':
                $description = "Sales Order #{$so->order_no} - Line item added: {$itemName} (Qty: {$line->qty}, Price: " . number_format($line->unit_price, 2) . ")";
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
                $description = "Sales Order #{$so->order_no} - Line item updated: {$itemName}{$changeStr}";
                break;
            case 'removed':
                $description = "Sales Order #{$so->order_no} - Line item removed: {$itemName}";
                break;
            default:
                $description = "Sales Order #{$so->order_no} - Line item {$action}: {$itemName}";
        }

        $this->auditLogService->log(
            'line_item_' . $action,
            'sales_order',
            $so->id,
            $oldData,
            $newData,
            $description
        );
    }

    public function logDeliveryOrderCreation(SalesOrder $so, $doId)
    {
        $description = "Sales Order #{$so->order_no} - Delivery Order created (DO ID: {$doId})";

        $this->auditLogService->log(
            'delivery_order_created',
            'sales_order',
            $so->id,
            null,
            ['delivery_order_id' => $doId],
            $description
        );
    }

    public function logSalesInvoiceCreation(SalesOrder $so, $invoiceId)
    {
        $description = "Sales Order #{$so->order_no} - Sales Invoice created (Invoice ID: {$invoiceId})";

        $this->auditLogService->log(
            'sales_invoice_created',
            'sales_order',
            $so->id,
            null,
            ['invoice_id' => $invoiceId],
            $description
        );
    }

    public function logSalesReceiptCreation(SalesOrder $so, $receiptId)
    {
        $description = "Sales Order #{$so->order_no} - Sales Receipt created (Receipt ID: {$receiptId})";

        $this->auditLogService->log(
            'sales_receipt_created',
            'sales_order',
            $so->id,
            null,
            ['receipt_id' => $receiptId],
            $description
        );
    }
}

