# Phase 3: Comprehensive Module Integration - Detailed Action Plan

**Priority**: MEDIUM  
**Estimated Effort**: 5-8 days  
**Dependencies**: Phase 1 (UI), Phase 2 (Automatic Logging)

---

## Overview

Phase 3 focuses on comprehensive integration of audit logging across all ERP modules with workflow-specific logging, business partner activity tracking, and fixed asset lifecycle management. This phase builds on the automatic logging from Phase 2 by adding specialized logging for business processes, approvals, and complex workflows.

---

## Detailed Task Breakdown

### Task 3.1: Document Workflow Logging - Purchase Workflow

**Objective**: Track complete Purchase Order lifecycle (PO → GRPO → PI → PP) with status changes, approvals, amount modifications, and line item changes.

#### 3.1.1 Purchase Order Workflow Events

**Workflow States**:
- `draft` → `pending_approval` → `approved` → `ordered` → `received` → `closed`
- Approval workflow: Multi-level approvals with approval/rejection actions
- Amount changes: Total amount, freight, handling, insurance modifications
- Line item changes: Quantity, price, item additions/removals

#### 3.1.2 Create Purchase Workflow Audit Service

**File**: `app/Services/PurchaseWorkflowAuditService.php`

**Purpose**: Centralized service for purchase workflow-specific audit logging.

```php
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

    /**
     * Log purchase order status change.
     */
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

    /**
     * Log purchase order approval action.
     */
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

    /**
     * Log purchase order amount change.
     */
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

    /**
     * Log purchase order line item changes.
     */
    public function logLineItemChange(PurchaseOrder $po, PurchaseOrderLine $line, string $action, ?array $oldData = null, ?array $newData = null)
    {
        $itemName = $line->inventoryItem->name ?? "Item #{$line->item_id}";
        
        switch ($action) {
            case 'added':
                $description = "Purchase Order #{$po->order_no} - Line item added: {$itemName} (Qty: {$line->quantity}, Price: " . number_format($line->unit_price, 2) . ")";
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

    /**
     * Log GRPO creation from Purchase Order.
     */
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

    /**
     * Log Purchase Invoice creation from Purchase Order.
     */
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

    /**
     * Log Purchase Payment creation from Purchase Order.
     */
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
```

#### 3.1.3 Integrate into PurchaseService

**File**: `app/Services/PurchaseService.php`

**Integration Points**:

```php
use App\Services\PurchaseWorkflowAuditService;

class PurchaseService
{
    protected $workflowAuditService;

    public function __construct(PurchaseWorkflowAuditService $workflowAuditService)
    {
        $this->workflowAuditService = $workflowAuditService;
    }

    public function approvePurchaseOrder($purchaseOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($purchaseOrderId, $userId, $comments) {
            $po = PurchaseOrder::findOrFail($purchaseOrderId);
            $oldStatus = $po->status;
            $oldApprovalStatus = $po->approval_status;

            // ... existing approval logic ...

            $po->refresh();
            
            // Log status change
            if ($oldStatus != $po->status) {
                $this->workflowAuditService->logStatusChange($po, $oldStatus, $po->status, "Approved by user {$userId}");
            }

            // Log approval action
            $approval = $po->approvals()->where('user_id', $userId)->latest()->first();
            if ($approval) {
                $this->workflowAuditService->logApproval($approval, 'approved', $comments);
            }

            return $po;
        });
    }

    public function rejectPurchaseOrder($purchaseOrderId, $userId, $comments = null)
    {
        return DB::transaction(function () use ($purchaseOrderId, $userId, $comments) {
            $po = PurchaseOrder::findOrFail($purchaseOrderId);
            $oldStatus = $po->status;
            $oldApprovalStatus = $po->approval_status;

            // ... existing rejection logic ...

            $po->refresh();

            // Log status change
            if ($oldStatus != $po->status) {
                $this->workflowAuditService->logStatusChange($po, $oldStatus, $po->status, "Rejected by user {$userId}");
            }

            // Log rejection action
            $approval = $po->approvals()->where('user_id', $userId)->latest()->first();
            if ($approval) {
                $this->workflowAuditService->logApproval($approval, 'rejected', $comments);
            }

            return $po;
        });
    }

    public function updatePurchaseOrder($purchaseOrderId, $data)
    {
        return DB::transaction(function () use ($purchaseOrderId, $data) {
            $po = PurchaseOrder::findOrFail($purchaseOrderId);
            
            // Track amount changes
            $oldAmounts = [
                'total_amount' => $po->total_amount,
                'freight_cost' => $po->freight_cost,
                'handling_cost' => $po->handling_cost,
                'insurance_cost' => $po->insurance_cost,
            ];

            $po->update($data);
            $po->refresh();

            $newAmounts = [
                'total_amount' => $po->total_amount,
                'freight_cost' => $po->freight_cost,
                'handling_cost' => $po->handling_cost,
                'insurance_cost' => $po->insurance_cost,
            ];

            // Log amount changes
            $this->workflowAuditService->logAmountChange($po, $oldAmounts, $newAmounts);

            return $po;
        });
    }
}
```

#### 3.1.4 Integrate into PurchaseOrderController

**File**: `app/Http/Controllers/PurchaseOrderController.php`

**Integration Points**:

```php
// In store method - log line item additions
foreach ($request->lines as $lineData) {
    $line = $po->lines()->create($lineData);
    $this->purchaseWorkflowAuditService->logLineItemChange($po, $line, 'added');
}

// In update method - log line item changes
$existingLineIds = $po->lines()->pluck('id')->toArray();
$newLineIds = collect($request->lines)->pluck('id')->filter()->toArray();

// Log removed lines
$removedLineIds = array_diff($existingLineIds, $newLineIds);
foreach ($removedLineIds as $lineId) {
    $line = PurchaseOrderLine::find($lineId);
    if ($line) {
        $this->purchaseWorkflowAuditService->logLineItemChange($po, $line, 'removed', $line->toArray());
    }
}

// Log updated/added lines
foreach ($request->lines as $lineData) {
    if (isset($lineData['id'])) {
        $line = PurchaseOrderLine::find($lineData['id']);
        $oldData = $line->toArray();
        $line->update($lineData);
        $this->purchaseWorkflowAuditService->logLineItemChange($po, $line, 'updated', $oldData, $line->fresh()->toArray());
    } else {
        $line = $po->lines()->create($lineData);
        $this->purchaseWorkflowAuditService->logLineItemChange($po, $line, 'added');
    }
}
```

#### 3.1.5 Integrate into GoodsReceiptPOController

**File**: `app/Http/Controllers/GoodsReceiptPOController.php`

**Integration Points**:

```php
// In store method
$grpo = GoodsReceiptPO::create($data);

// Log GRPO creation in Purchase Order audit trail
$po = PurchaseOrder::find($data['purchase_order_id']);
if ($po) {
    app(PurchaseWorkflowAuditService::class)->logGRPOCreation($po, $grpo->id);
}
```

#### 3.1.6 Integrate into PurchaseInvoiceController

**File**: `app/Http/Controllers/PurchaseInvoiceController.php`

**Integration Points**:

```php
// In store method
$invoice = PurchaseInvoice::create($data);

// Log invoice creation in Purchase Order audit trail
if (isset($data['purchase_order_id'])) {
    $po = PurchaseOrder::find($data['purchase_order_id']);
    if ($po) {
        app(PurchaseWorkflowAuditService::class)->logPurchaseInvoiceCreation($po, $invoice->id);
    }
}
```

#### 3.1.7 Integrate into PurchasePaymentController

**File**: `app/Http/Controllers/PurchasePaymentController.php`

**Integration Points**:

```php
// In store method
$payment = PurchasePayment::create($data);

// Log payment creation in Purchase Order audit trail
if (isset($data['purchase_order_id'])) {
    $po = PurchaseOrder::find($data['purchase_order_id']);
    if ($po) {
        app(PurchaseWorkflowAuditService::class)->logPurchasePaymentCreation($po, $payment->id);
    }
}
```

---

### Task 3.2: Document Workflow Logging - Sales Workflow

**Objective**: Track complete Sales Order lifecycle (SO → DO → SI → SR) with status changes, approvals, amount modifications, and line item changes.

#### 3.2.1 Create Sales Workflow Audit Service

**File**: `app/Services/SalesWorkflowAuditService.php`

**Implementation**: Similar to PurchaseWorkflowAuditService but for Sales Orders.

```php
<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesOrderApproval;
use App\Services\AuditLogService;

class SalesWorkflowAuditService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Log sales order status change.
     */
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

    /**
     * Log sales order approval action.
     */
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

    /**
     * Log sales order amount change.
     */
    public function logAmountChange(SalesOrder $so, array $oldAmounts, array $newAmounts)
    {
        // Similar implementation to PurchaseWorkflowAuditService
    }

    /**
     * Log sales order line item changes.
     */
    public function logLineItemChange(SalesOrder $so, SalesOrderLine $line, string $action, ?array $oldData = null, ?array $newData = null)
    {
        // Similar implementation to PurchaseWorkflowAuditService
    }

    /**
     * Log Delivery Order creation from Sales Order.
     */
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

    /**
     * Log Sales Invoice creation from Sales Order.
     */
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

    /**
     * Log Sales Receipt creation from Sales Order.
     */
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
```

#### 3.2.2 Integrate into SalesService

**File**: `app/Services/SalesService.php`

**Integration**: Similar to PurchaseService integration.

#### 3.2.3 Integrate into SalesOrderController

**File**: `app/Http/Controllers/SalesOrderController.php`

**Integration**: Similar to PurchaseOrderController integration.

#### 3.2.4 Integrate into DeliveryOrderController

**File**: `app/Http/Controllers/DeliveryOrderController.php`

**Integration Points**:

```php
// In store method
$do = DeliveryOrder::create($data);

// Log DO creation in Sales Order audit trail
$so = SalesOrder::find($data['sales_order_id']);
if ($so) {
    app(SalesWorkflowAuditService::class)->logDeliveryOrderCreation($so, $do->id);
}
```

#### 3.2.5 Integrate into SalesInvoiceController

**File**: `app/Http/Controllers/SalesInvoiceController.php`

**Integration**: Similar to PurchaseInvoiceController.

#### 3.2.6 Integrate into SalesReceiptController

**File**: `app/Http/Controllers/SalesReceiptController.php`

**Integration**: Similar to PurchasePaymentController.

---

### Task 3.3: Document Workflow Logging - Accounting Workflow

**Objective**: Track Journal lifecycle (Journal → Posting → Reversal) with account changes and amount modifications.

#### 3.3.1 Create Accounting Workflow Audit Service

**File**: `app/Services/AccountingWorkflowAuditService.php`

```php
<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalLine;
use App\Services\AuditLogService;

class AccountingWorkflowAuditService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Log journal posting.
     */
    public function logJournalPosting(Journal $journal, ?string $postedBy = null)
    {
        $description = "Journal #{$journal->journal_no} posted";
        if ($postedBy) {
            $description .= " by {$postedBy}";
        }

        $this->auditLogService->log(
            'posted',
            'journal',
            $journal->id,
            ['status' => 'draft'],
            ['status' => 'posted'],
            $description
        );
    }

    /**
     * Log journal reversal.
     */
    public function logJournalReversal(Journal $journal, Journal $reversalJournal, ?string $reversedBy = null)
    {
        $description = "Journal #{$journal->journal_no} reversed by Journal #{$reversalJournal->journal_no}";
        if ($reversedBy) {
            $description .= " by {$reversedBy}";
        }

        $this->auditLogService->log(
            'reversed',
            'journal',
            $journal->id,
            ['status' => 'posted'],
            ['status' => 'reversed', 'reversal_journal_id' => $reversalJournal->id],
            $description
        );
    }

    /**
     * Log journal line account change.
     */
    public function logJournalLineAccountChange(Journal $journal, JournalLine $line, $oldAccountId, $newAccountId)
    {
        $oldAccount = Account::find($oldAccountId);
        $newAccount = Account::find($newAccountId);

        $description = "Journal #{$journal->journal_no} - Line #{$line->id} account changed from '{$oldAccount->code}' to '{$newAccount->code}'";

        $this->auditLogService->log(
            'account_changed',
            'journal',
            $journal->id,
            ['line_id' => $line->id, 'account_id' => $oldAccountId],
            ['line_id' => $line->id, 'account_id' => $newAccountId],
            $description
        );
    }

    /**
     * Log journal line amount change.
     */
    public function logJournalLineAmountChange(Journal $journal, JournalLine $line, $oldAmount, $newAmount)
    {
        $description = "Journal #{$journal->journal_no} - Line #{$line->id} amount changed from " . number_format($oldAmount, 2) . " to " . number_format($newAmount, 2);

        $this->auditLogService->log(
            'amount_changed',
            'journal',
            $journal->id,
            ['line_id' => $line->id, 'amount' => $oldAmount],
            ['line_id' => $line->id, 'amount' => $newAmount],
            $description
        );
    }
}
```

#### 3.3.2 Integrate into PostingService

**File**: `app/Services/PostingService.php`

**Integration Points**:

```php
use App\Services\AccountingWorkflowAuditService;

class PostingService
{
    protected $workflowAuditService;

    public function __construct(AccountingWorkflowAuditService $workflowAuditService)
    {
        $this->workflowAuditService = $workflowAuditService;
    }

    public function postJournal(Journal $journal)
    {
        // ... existing posting logic ...

        // Log posting
        $this->workflowAuditService->logJournalPosting($journal, auth()->user()->name);

        return $journal;
    }

    public function reverseJournal(Journal $journal)
    {
        // ... existing reversal logic ...

        $reversalJournal = $this->createReversalJournal($journal);

        // Log reversal
        $this->workflowAuditService->logJournalReversal($journal, $reversalJournal, auth()->user()->name);

        return $reversalJournal;
    }
}
```

---

### Task 3.4: Business Partner Activity Logging

**Objective**: Track all interactions with customers/vendors including credit limit modifications, pricing tier changes, and contact information updates.

#### 3.4.1 Create Business Partner Audit Service

**File**: `app/Services/BusinessPartnerAuditService.php`

```php
<?php

namespace App\Services;

use App\Models\BusinessPartner;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPricingTier;
use App\Services\AuditLogService;

class BusinessPartnerAuditService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Log credit limit modification.
     */
    public function logCreditLimitChange(BusinessPartner $partner, CustomerCreditLimit $creditLimit, $oldLimit, $newLimit)
    {
        $description = "Business Partner '{$partner->name}' credit limit changed from " . number_format($oldLimit, 2) . " to " . number_format($newLimit, 2);

        $this->auditLogService->log(
            'credit_limit_changed',
            'business_partner',
            $partner->id,
            ['credit_limit_id' => $creditLimit->id, 'limit' => $oldLimit],
            ['credit_limit_id' => $creditLimit->id, 'limit' => $newLimit],
            $description
        );
    }

    /**
     * Log pricing tier change.
     */
    public function logPricingTierChange(BusinessPartner $partner, CustomerPricingTier $pricingTier, $oldTier, $newTier)
    {
        $description = "Business Partner '{$partner->name}' pricing tier changed from '{$oldTier}' to '{$newTier}'";

        $this->auditLogService->log(
            'pricing_tier_changed',
            'business_partner',
            $partner->id,
            ['pricing_tier_id' => $pricingTier->id, 'tier' => $oldTier],
            ['pricing_tier_id' => $pricingTier->id, 'tier' => $newTier],
            $description
        );
    }

    /**
     * Log contact information update.
     */
    public function logContactUpdate(BusinessPartner $partner, $contactType, $oldData, $newData)
    {
        $description = "Business Partner '{$partner->name}' {$contactType} information updated";

        $this->auditLogService->log(
            'contact_updated',
            'business_partner',
            $partner->id,
            [$contactType => $oldData],
            [$contactType => $newData],
            $description
        );
    }

    /**
     * Log partner type change.
     */
    public function logPartnerTypeChange(BusinessPartner $partner, $oldType, $newType)
    {
        $description = "Business Partner '{$partner->name}' type changed from '{$oldType}' to '{$newType}'";

        $this->auditLogService->log(
            'type_changed',
            'business_partner',
            $partner->id,
            ['partner_type' => $oldType],
            ['partner_type' => $newType],
            $description
        );
    }
}
```

#### 3.4.2 Integrate into BusinessPartnerController

**File**: `app/Http/Controllers/BusinessPartnerController.php`

**Integration Points**:

```php
use App\Services\BusinessPartnerAuditService;

class BusinessPartnerController extends Controller
{
    protected $partnerAuditService;

    public function __construct(BusinessPartnerAuditService $partnerAuditService)
    {
        $this->partnerAuditService = $partnerAuditService;
    }

    public function updateCreditLimit(Request $request, $id)
    {
        $partner = BusinessPartner::findOrFail($id);
        $creditLimit = $partner->creditLimit;
        $oldLimit = $creditLimit->limit;

        $creditLimit->update(['limit' => $request->limit]);

        // Log credit limit change
        $this->partnerAuditService->logCreditLimitChange($partner, $creditLimit, $oldLimit, $request->limit);

        return back()->with('success', 'Credit limit updated');
    }

    public function updatePricingTier(Request $request, $id)
    {
        $partner = BusinessPartner::findOrFail($id);
        $pricingTier = $partner->pricingTier;
        $oldTier = $pricingTier->tier_level;

        $pricingTier->update(['tier_level' => $request->tier_level]);

        // Log pricing tier change
        $this->partnerAuditService->logPricingTierChange($partner, $pricingTier, $oldTier, $request->tier_level);

        return back()->with('success', 'Pricing tier updated');
    }
}
```

---

### Task 3.5: Fixed Asset Activity Logging

**Objective**: Complete asset lifecycle tracking including creation, updates, disposal, depreciation runs, asset movements, and category changes.

#### 3.5.1 Create Fixed Asset Audit Service

**File**: `app/Services/FixedAssetAuditService.php`

```php
<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciationRun;
use App\Models\AssetMovement;
use App\Models\AssetDisposal;
use App\Services\AuditLogService;

class FixedAssetAuditService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Log asset disposal.
     */
    public function logAssetDisposal(Asset $asset, AssetDisposal $disposal)
    {
        $description = "Asset '{$asset->code}' - {$asset->name} disposed. Disposal method: {$disposal->disposal_method}, Gain/Loss: " . number_format($disposal->gain_loss, 2);

        $this->auditLogService->log(
            'disposed',
            'asset',
            $asset->id,
            ['status' => 'active'],
            ['status' => 'disposed', 'disposal_id' => $disposal->id],
            $description
        );
    }

    /**
     * Log depreciation run.
     */
    public function logDepreciationRun(AssetDepreciationRun $run)
    {
        $assetCount = $run->entries()->count();
        $totalDepreciation = $run->entries()->sum('depreciation_amount');

        $description = "Depreciation run #{$run->id} executed. Assets: {$assetCount}, Total Depreciation: " . number_format($totalDepreciation, 2);

        $this->auditLogService->log(
            'depreciation_run',
            'asset_depreciation_run',
            $run->id,
            null,
            ['asset_count' => $assetCount, 'total_depreciation' => $totalDepreciation],
            $description
        );
    }

    /**
     * Log asset movement.
     */
    public function logAssetMovement(Asset $asset, AssetMovement $movement)
    {
        $fromDept = $movement->fromDepartment ? $movement->fromDepartment->name : 'N/A';
        $toDept = $movement->toDepartment ? $movement->toDepartment->name : 'N/A';
        $fromProject = $movement->fromProject ? $movement->fromProject->name : 'N/A';
        $toProject = $movement->toProject ? $movement->toProject->name : 'N/A';

        $description = "Asset '{$asset->code}' moved from Department: {$fromDept}, Project: {$fromProject} to Department: {$toDept}, Project: {$toProject}";

        $this->auditLogService->log(
            'moved',
            'asset',
            $asset->id,
            [
                'department_id' => $movement->from_department_id,
                'project_id' => $movement->from_project_id,
            ],
            [
                'department_id' => $movement->to_department_id,
                'project_id' => $movement->to_project_id,
            ],
            $description
        );
    }

    /**
     * Log asset category change.
     */
    public function logCategoryChange(Asset $asset, $oldCategoryId, $newCategoryId)
    {
        $oldCategory = AssetCategory::find($oldCategoryId);
        $newCategory = AssetCategory::find($newCategoryId);

        $description = "Asset '{$asset->code}' category changed from '{$oldCategory->name}' to '{$newCategory->name}'";

        $this->auditLogService->log(
            'category_changed',
            'asset',
            $asset->id,
            ['category_id' => $oldCategoryId],
            ['category_id' => $newCategoryId],
            $description
        );
    }

    /**
     * Log asset depreciation entry.
     */
    public function logDepreciationEntry(Asset $asset, $depreciationAmount, $runId)
    {
        $description = "Asset '{$asset->code}' depreciation calculated: " . number_format($depreciationAmount, 2) . " (Run ID: {$runId})";

        $this->auditLogService->log(
            'depreciated',
            'asset',
            $asset->id,
            ['accumulated_depreciation' => $asset->accumulated_depreciation - $depreciationAmount],
            ['accumulated_depreciation' => $asset->accumulated_depreciation, 'depreciation_run_id' => $runId],
            $description
        );
    }
}
```

#### 3.5.2 Integrate into AssetController

**File**: `app/Http/Controllers/AssetController.php`

**Integration Points**:

```php
use App\Services\FixedAssetAuditService;

class AssetController extends Controller
{
    protected $assetAuditService;

    public function __construct(FixedAssetAuditService $assetAuditService)
    {
        $this->assetAuditService = $assetAuditService;
    }

    public function dispose(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $disposal = AssetDisposal::create($disposalData);

        // Log disposal
        $this->assetAuditService->logAssetDisposal($asset, $disposal);

        return redirect()->route('assets.show', $asset->id);
    }

    public function move(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $movement = AssetMovement::create($movementData);

        // Log movement
        $this->assetAuditService->logAssetMovement($asset, $movement);

        return redirect()->route('assets.show', $asset->id);
    }

    public function updateCategory(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $oldCategoryId = $asset->category_id;

        $asset->update(['category_id' => $request->category_id]);

        // Log category change
        $this->assetAuditService->logCategoryChange($asset, $oldCategoryId, $request->category_id);

        return back()->with('success', 'Category updated');
    }
}
```

#### 3.5.3 Integrate into DepreciationService

**File**: `app/Services/DepreciationService.php`

**Integration Points**:

```php
use App\Services\FixedAssetAuditService;

class DepreciationService
{
    protected $assetAuditService;

    public function __construct(FixedAssetAuditService $assetAuditService)
    {
        $this->assetAuditService = $assetAuditService;
    }

    public function runDepreciation($period)
    {
        $run = AssetDepreciationRun::create(['period' => $period]);

        // Process depreciation for each asset
        foreach ($assets as $asset) {
            $depreciationAmount = $this->calculateDepreciation($asset);
            
            // Create depreciation entry
            $entry = AssetDepreciationEntry::create([...]);

            // Log depreciation entry
            $this->assetAuditService->logDepreciationEntry($asset, $depreciationAmount, $run->id);
        }

        // Log depreciation run
        $this->assetAuditService->logDepreciationRun($run);

        return $run;
    }
}
```

---

### Task 3.6: Create Event Listeners for Workflow Events

**Objective**: Use Laravel Events for decoupled workflow logging.

#### 3.6.1 Create Events

**File**: `app/Events/PurchaseOrderApproved.php`

```php
<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved
{
    use Dispatchable, SerializesModels;

    public $purchaseOrder;
    public $approvedBy;
    public $comments;

    public function __construct(PurchaseOrder $purchaseOrder, $approvedBy, $comments = null)
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->approvedBy = $approvedBy;
        $this->comments = $comments;
    }
}
```

**File**: `app/Events/SalesOrderApproved.php`

**File**: `app/Events/JournalPosted.php`

**File**: `app/Events/AssetDisposed.php`

#### 3.6.2 Create Event Listeners

**File**: `app/Listeners/LogPurchaseOrderApproval.php`

```php
<?php

namespace App\Listeners;

use App\Events\PurchaseOrderApproved;
use App\Services\PurchaseWorkflowAuditService;

class LogPurchaseOrderApproval
{
    protected $workflowAuditService;

    public function __construct(PurchaseWorkflowAuditService $workflowAuditService)
    {
        $this->workflowAuditService = $workflowAuditService;
    }

    public function handle(PurchaseOrderApproved $event)
    {
        $approval = $event->purchaseOrder->approvals()
            ->where('user_id', $event->approvedBy->id)
            ->latest()
            ->first();

        if ($approval) {
            $this->workflowAuditService->logApproval($approval, 'approved', $event->comments);
        }
    }
}
```

#### 3.6.3 Register Event Listeners

**File**: `app/Providers/EventServiceProvider.php` (or `bootstrap/providers.php` in Laravel 11+)

```php
protected $listen = [
    PurchaseOrderApproved::class => [
        LogPurchaseOrderApproval::class,
    ],
    SalesOrderApproved::class => [
        LogSalesOrderApproval::class,
    ],
    JournalPosted::class => [
        LogJournalPosting::class,
    ],
    AssetDisposed::class => [
        LogAssetDisposal::class,
    ],
];
```

---

### Task 3.7: Update Models with Workflow-Specific Configuration

**Objective**: Configure models to track workflow-specific fields.

#### 3.7.1 Update PurchaseOrder Model

```php
class PurchaseOrder extends Model
{
    // Audit log configuration
    protected $auditLogIgnore = ['updated_at'];
    
    // Track these fields specifically for workflow
    protected $auditLogTrackFields = [
        'status',
        'approval_status',
        'total_amount',
        'freight_cost',
        'handling_cost',
        'insurance_cost',
    ];
}
```

#### 3.7.2 Update SalesOrder Model

Similar configuration to PurchaseOrder.

#### 3.7.3 Update Journal Model

```php
class Journal extends Model
{
    protected $auditLogIgnore = ['updated_at'];
    protected $auditLogTrackFields = ['status', 'journal_date', 'total_debit', 'total_credit'];
}
```

---

## Implementation Checklist

### Day 1-2: Purchase Workflow
- [ ] Create `PurchaseWorkflowAuditService`
- [ ] Integrate into `PurchaseService`
- [ ] Integrate into `PurchaseOrderController`
- [ ] Integrate into `GoodsReceiptPOController`
- [ ] Integrate into `PurchaseInvoiceController`
- [ ] Integrate into `PurchasePaymentController`
- [ ] Test purchase workflow logging

### Day 3-4: Sales Workflow
- [ ] Create `SalesWorkflowAuditService`
- [ ] Integrate into `SalesService`
- [ ] Integrate into `SalesOrderController`
- [ ] Integrate into `DeliveryOrderController`
- [ ] Integrate into `SalesInvoiceController`
- [ ] Integrate into `SalesReceiptController`
- [ ] Test sales workflow logging

### Day 5: Accounting Workflow
- [ ] Create `AccountingWorkflowAuditService`
- [ ] Integrate into `PostingService`
- [ ] Integrate into `JournalController`
- [ ] Test accounting workflow logging

### Day 6: Business Partner Activity
- [ ] Create `BusinessPartnerAuditService`
- [ ] Integrate into `BusinessPartnerController`
- [ ] Test business partner logging

### Day 7: Fixed Asset Activity
- [ ] Create `FixedAssetAuditService`
- [ ] Integrate into `AssetController`
- [ ] Integrate into `DepreciationService`
- [ ] Test fixed asset logging

### Day 8: Events & Testing
- [ ] Create workflow events
- [ ] Create event listeners
- [ ] Register event listeners
- [ ] Update model configurations
- [ ] Comprehensive testing
- [ ] Documentation

---

## Testing Checklist

### Purchase Workflow Testing
- [ ] PO creation creates audit log
- [ ] PO status change creates audit log
- [ ] PO approval creates audit log
- [ ] PO rejection creates audit log
- [ ] PO amount change creates audit log
- [ ] PO line item addition creates audit log
- [ ] PO line item update creates audit log
- [ ] PO line item removal creates audit log
- [ ] GRPO creation logs in PO audit trail
- [ ] Purchase Invoice creation logs in PO audit trail
- [ ] Purchase Payment creation logs in PO audit trail

### Sales Workflow Testing
- [ ] SO creation creates audit log
- [ ] SO status change creates audit log
- [ ] SO approval creates audit log
- [ ] SO rejection creates audit log
- [ ] SO amount change creates audit log
- [ ] SO line item changes create audit log
- [ ] Delivery Order creation logs in SO audit trail
- [ ] Sales Invoice creation logs in SO audit trail
- [ ] Sales Receipt creation logs in SO audit trail

### Accounting Workflow Testing
- [ ] Journal posting creates audit log
- [ ] Journal reversal creates audit log
- [ ] Journal line account change creates audit log
- [ ] Journal line amount change creates audit log

### Business Partner Testing
- [ ] Credit limit change creates audit log
- [ ] Pricing tier change creates audit log
- [ ] Contact information update creates audit log
- [ ] Partner type change creates audit log

### Fixed Asset Testing
- [ ] Asset disposal creates audit log
- [ ] Depreciation run creates audit log
- [ ] Asset movement creates audit log
- [ ] Asset category change creates audit log
- [ ] Depreciation entry creates audit log

---

## Success Criteria

Phase 3 is considered complete when:

1. ✅ Purchase workflow logging is fully integrated
2. ✅ Sales workflow logging is fully integrated
3. ✅ Accounting workflow logging is fully integrated
4. ✅ Business partner activity logging is fully integrated
5. ✅ Fixed asset activity logging is fully integrated
6. ✅ All workflow services are created and functional
7. ✅ All controllers are updated with audit logging
8. ✅ Event listeners are created and registered
9. ✅ All tests pass
10. ✅ Documentation is complete
11. ✅ No performance degradation
12. ✅ No critical bugs or errors

---

## Next Steps After Phase 3

Once Phase 3 is complete, proceed to:
- **Phase 4**: Enhanced features (activity dashboard, advanced filtering, export capabilities)
- **Phase 5**: Performance optimization and archiving

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20  
**Estimated Completion**: 5-8 days

