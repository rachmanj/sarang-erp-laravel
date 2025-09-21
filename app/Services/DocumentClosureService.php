<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\Accounting\SalesReceiptAllocation;
use App\Models\ErpParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DocumentClosureService
{
    /**
     * Close a Purchase Order when Goods Receipt is created
     */
    public function closePurchaseOrder($poId, $grpoId, $userId = null)
    {
        $po = PurchaseOrder::findOrFail($poId);
        $grpo = GoodsReceipt::findOrFail($grpoId);

        // Check if PO can be closed (quantity-based closure)
        if ($this->canClosePurchaseOrder($poId, $grpoId)) {
            $po->update([
                'closure_status' => 'closed',
                'closed_by_document_type' => 'goods_receipt',
                'closed_by_document_id' => $grpoId,
                'closed_at' => now(),
                'closed_by_user_id' => $userId ?? Auth::id()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Close a Goods Receipt when Purchase Invoice is created
     */
    public function closeGoodsReceipt($grpoId, $piId, $userId = null)
    {
        $grpo = GoodsReceipt::findOrFail($grpoId);
        $pi = PurchaseInvoice::findOrFail($piId);

        // Check if GRPO can be closed (quantity-based closure)
        if ($this->canCloseGoodsReceipt($grpoId, $piId)) {
            $grpo->update([
                'closure_status' => 'closed',
                'closed_by_document_type' => 'purchase_invoice',
                'closed_by_document_id' => $piId,
                'closed_at' => now(),
                'closed_by_user_id' => $userId ?? Auth::id()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Close a Purchase Invoice when Purchase Payment is created
     */
    public function closePurchaseInvoice($piId, $paymentId, $userId = null)
    {
        $pi = PurchaseInvoice::findOrFail($piId);
        $payment = PurchasePayment::findOrFail($paymentId);

        // Check if PI can be closed (amount-based closure)
        if ($this->canClosePurchaseInvoice($piId, $paymentId)) {
            $pi->update([
                'closure_status' => 'closed',
                'closed_by_document_type' => 'purchase_payment',
                'closed_by_document_id' => $paymentId,
                'closed_at' => now(),
                'closed_by_user_id' => $userId ?? Auth::id()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Close a Sales Order when Delivery Order is created
     */
    public function closeSalesOrder($soId, $doId, $userId = null)
    {
        $so = SalesOrder::findOrFail($soId);
        $do = DeliveryOrder::findOrFail($doId);

        // Check if SO can be closed (quantity-based closure)
        if ($this->canCloseSalesOrder($soId, $doId)) {
            $so->update([
                'closure_status' => 'closed',
                'closed_by_document_type' => 'delivery_order',
                'closed_by_document_id' => $doId,
                'closed_at' => now(),
                'closed_by_user_id' => $userId ?? Auth::id()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Close Sales Invoices when Sales Receipt is created
     */
    public function closeSalesInvoiceByReceipt($receiptId, $userId = null)
    {
        $receipt = SalesReceipt::findOrFail($receiptId);

        // Get all posted sales invoices for this customer that are not fully paid
        $invoices = SalesInvoice::where('business_partner_id', $receipt->business_partner_id)
            ->where('status', 'posted')
            ->get();

        foreach ($invoices as $invoice) {
            // Calculate remaining amount for this invoice
            $allocatedAmount = SalesReceiptAllocation::where('invoice_id', $invoice->id)->sum('amount');
            $remainingAmount = $invoice->total_amount - $allocatedAmount;

            if ($remainingAmount <= 0) {
                // Invoice is fully paid, close it
                $invoice->update([
                    'closure_status' => 'closed',
                    'closed_by_document_type' => 'sales_receipt',
                    'closed_by_document_id' => $receiptId,
                    'closed_at' => now(),
                    'closed_by_user_id' => $userId ?? Auth::id()
                ]);
            }
        }

        return true;
    }

    /**
     * Close a Delivery Order when Sales Invoice is created
     */
    public function closeDeliveryOrder($doId, $siId, $userId = null)
    {
        $do = DeliveryOrder::findOrFail($doId);
        $si = SalesInvoice::findOrFail($siId);

        // Check if DO can be closed (quantity-based closure)
        if ($this->canCloseDeliveryOrder($doId, $siId)) {
            $do->update([
                'closure_status' => 'closed',
                'closed_by_document_type' => 'sales_invoice',
                'closed_by_document_id' => $siId,
                'closed_at' => now(),
                'closed_by_user_id' => $userId ?? Auth::id()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Close a Sales Invoice when Sales Receipt is created
     */
    public function closeSalesInvoice($siId, $receiptId, $userId = null)
    {
        $si = SalesInvoice::findOrFail($siId);
        $receipt = SalesReceipt::findOrFail($receiptId);

        // Check if SI can be closed (amount-based closure)
        if ($this->canCloseSalesInvoice($siId, $receiptId)) {
            $si->update([
                'closure_status' => 'closed',
                'closed_by_document_type' => 'sales_receipt',
                'closed_by_document_id' => $receiptId,
                'closed_at' => now(),
                'closed_by_user_id' => $userId ?? Auth::id()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Close Purchase Invoices by Payment
     * 
     * Checks all invoices associated with a payment and closes them if fully paid
     */
    public function closePurchaseInvoiceByPayment($paymentId, $userId = null)
    {
        $payment = PurchasePayment::findOrFail($paymentId);
        $allocations = DB::table('purchase_payment_allocations')
            ->where('payment_id', $paymentId)
            ->get();

        $closedCount = 0;

        foreach ($allocations as $allocation) {
            // Check if this invoice is fully paid
            $invoice = PurchaseInvoice::findOrFail($allocation->invoice_id);
            $totalPaid = DB::table('purchase_payment_allocations')
                ->where('invoice_id', $invoice->id)
                ->sum('amount');

            // If total paid amount equals or exceeds invoice amount, close it
            if ($totalPaid >= $invoice->total_amount) {
                $invoice->update([
                    'closure_status' => 'closed',
                    'closed_by_document_type' => 'purchase_payment',
                    'closed_by_document_id' => $paymentId,
                    'closed_at' => now(),
                    'closed_by_user_id' => $userId ?? Auth::id()
                ]);
                $closedCount++;
            }
        }

        return $closedCount;
    }

    /**
     * Check if Purchase Order can be closed by Goods Receipt
     */
    public function canClosePurchaseOrder($poId, $grpoId)
    {
        $po = PurchaseOrder::with('lines')->findOrFail($poId);
        $grpo = GoodsReceipt::with('lines')->findOrFail($grpoId);

        // Get total quantities
        $poTotalQty = $po->lines->sum('quantity');
        $grpoTotalQty = $grpo->lines->sum('quantity');

        // Check if GRPO quantity >= PO quantity
        return $grpoTotalQty >= $poTotalQty;
    }

    /**
     * Check if Goods Receipt can be closed by Purchase Invoice
     */
    public function canCloseGoodsReceipt($grpoId, $piId)
    {
        $grpo = GoodsReceipt::with('lines')->findOrFail($grpoId);
        $pi = PurchaseInvoice::with('lines')->findOrFail($piId);

        // Get total quantities
        $grpoTotalQty = $grpo->lines->sum('quantity');
        $piTotalQty = $pi->lines->sum('quantity');

        // Check if PI quantity >= GRPO quantity
        return $piTotalQty >= $grpoTotalQty;
    }

    /**
     * Check if Purchase Invoice can be closed by Purchase Payment
     */
    public function canClosePurchaseInvoice($piId, $paymentId)
    {
        $pi = PurchaseInvoice::findOrFail($piId);
        $payment = PurchasePayment::findOrFail($paymentId);

        // Get total amounts
        $piTotalAmount = $pi->total_amount;
        $paymentTotalAmount = $payment->total_amount;

        // Check if Payment amount >= PI amount
        return $paymentTotalAmount >= $piTotalAmount;
    }

    /**
     * Check if Sales Order can be closed by Delivery Order
     */
    public function canCloseSalesOrder($soId, $doId)
    {
        $so = SalesOrder::with('lines')->findOrFail($soId);
        $do = DeliveryOrder::with('lines')->findOrFail($doId);

        // Get total quantities
        $soTotalQty = $so->lines->sum('quantity');
        $doTotalQty = $do->lines->sum('quantity');

        // Check if DO quantity >= SO quantity
        return $doTotalQty >= $soTotalQty;
    }

    /**
     * Check if Delivery Order can be closed by Sales Invoice
     */
    public function canCloseDeliveryOrder($doId, $siId)
    {
        $do = DeliveryOrder::with('lines')->findOrFail($doId);
        $si = SalesInvoice::with('lines')->findOrFail($siId);

        // Get total quantities
        $doTotalQty = $do->lines->sum('quantity');
        $siTotalQty = $si->lines->sum('quantity');

        // Check if SI quantity >= DO quantity
        return $siTotalQty >= $doTotalQty;
    }

    /**
     * Check if Sales Invoice can be closed by Sales Receipt
     */
    public function canCloseSalesInvoice($siId, $receiptId)
    {
        $si = SalesInvoice::findOrFail($siId);
        $receipt = SalesReceipt::findOrFail($receiptId);

        // Get total amounts
        $siTotalAmount = $si->total_amount;
        $receiptTotalAmount = $receipt->total_amount;

        // Check if Receipt amount >= SI amount
        return $receiptTotalAmount >= $siTotalAmount;
    }

    /**
     * Get document closure status
     */
    public function getDocumentStatus($documentType, $documentId)
    {
        $model = $this->getModelByType($documentType);
        $document = $model::findOrFail($documentId);

        return [
            'status' => $document->closure_status,
            'closed_by_document_type' => $document->closed_by_document_type,
            'closed_by_document_id' => $document->closed_by_document_id,
            'closed_at' => $document->closed_at,
            'closed_by_user_id' => $document->closed_by_user_id,
        ];
    }

    /**
     * Get closure history for a document
     */
    public function getClosureHistory($documentType, $documentId)
    {
        $model = $this->getModelByType($documentType);
        $document = $model::findOrFail($documentId);

        $history = [];

        if ($document->closure_status === 'closed') {
            $history[] = [
                'action' => 'closed',
                'document_type' => $document->closed_by_document_type,
                'document_id' => $document->closed_by_document_id,
                'closed_at' => $document->closed_at,
                'closed_by_user_id' => $document->closed_by_user_id,
            ];
        }

        return $history;
    }

    /**
     * Manual closure with permission check
     */
    public function manualClosure($documentType, $documentId, $userId = null)
    {
        $model = $this->getModelByType($documentType);
        $document = $model::findOrFail($documentId);

        // Check if user has permission to manually close documents
        if (!Auth::user()->can('close-documents')) {
            throw new \Exception('Insufficient permissions to close documents');
        }

        $document->update([
            'closure_status' => 'closed',
            'closed_by_document_type' => 'manual',
            'closed_by_document_id' => null,
            'closed_at' => now(),
            'closed_by_user_id' => $userId ?? Auth::id()
        ]);

        return true;
    }

    /**
     * Manual reversal with permission check
     */
    public function manualReversal($documentType, $documentId, $userId = null)
    {
        $model = $this->getModelByType($documentType);
        $document = $model::findOrFail($documentId);

        // Check if user has permission to reverse document closure
        if (!Auth::user()->can('reverse-document-closure')) {
            throw new \Exception('Insufficient permissions to reverse document closure');
        }

        $document->update([
            'closure_status' => 'open',
            'closed_by_document_type' => null,
            'closed_by_document_id' => null,
            'closed_at' => null,
            'closed_by_user_id' => null
        ]);

        return true;
    }

    /**
     * Get ERP parameter value
     */
    public function getParameter($category, $key, $default = null)
    {
        $parameter = ErpParameter::where('category', $category)
            ->where('parameter_key', $key)
            ->where('is_active', true)
            ->first();

        if (!$parameter) {
            return $default;
        }

        // Convert based on data type
        switch ($parameter->data_type) {
            case 'integer':
                return (int) $parameter->parameter_value;
            case 'boolean':
                return (bool) $parameter->parameter_value;
            case 'json':
                return json_decode($parameter->parameter_value, true);
            default:
                return $parameter->parameter_value;
        }
    }

    /**
     * Get model class by document type
     */
    private function getModelByType($documentType)
    {
        $models = [
            'purchase_order' => PurchaseOrder::class,
            'goods_receipt' => GoodsReceipt::class,
            'purchase_invoice' => PurchaseInvoice::class,
            'purchase_payment' => PurchasePayment::class,
            'sales_order' => SalesOrder::class,
            'delivery_order' => DeliveryOrder::class,
            'sales_invoice' => SalesInvoice::class,
            'sales_receipt' => SalesReceipt::class,
        ];

        if (!isset($models[$documentType])) {
            throw new \Exception("Unknown document type: {$documentType}");
        }

        return $models[$documentType];
    }
}
