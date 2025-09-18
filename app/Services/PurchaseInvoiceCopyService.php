<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Services\DocumentNumberingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceCopyService
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService
    ) {}

    /**
     * Copy Service Purchase Order directly to Purchase Invoice
     */
    public function copyFromServicePurchaseOrder(PurchaseOrder $po): PurchaseInvoice
    {
        // Validate PO type
        if ($po->order_type !== 'service') {
            throw new \Exception('Only Service Purchase Orders can be copied to Purchase Invoice');
        }

        // Validate PO status
        if ($po->status !== 'approved') {
            throw new \Exception('Purchase Order must be approved before copying to Purchase Invoice');
        }

        return DB::transaction(function () use ($po) {
            // Create Purchase Invoice with copied data
            $invoice = PurchaseInvoice::create([
                'invoice_no' => null, // Will be generated
                'date' => now()->toDateString(),
                'vendor_id' => $po->vendor_id,
                'description' => 'From Service PO: ' . $po->order_no,
                'status' => 'draft',
                'total_amount' => $po->total_amount,
                'freight_cost' => $po->freight_cost ?? 0,
                'handling_cost' => $po->handling_cost ?? 0,
                'insurance_cost' => $po->insurance_cost ?? 0,
                'total_cost' => $po->total_cost ?? $po->total_amount,
                'terms_conditions' => $po->terms_conditions,
                'payment_terms' => $po->payment_terms,
                'delivery_method' => $po->delivery_method,
            ]);

            // Generate invoice number
            $invoiceNo = $this->documentNumberingService->generateNumber('purchase_invoice', $invoice->date);
            $invoice->update(['invoice_no' => $invoiceNo]);

            // Copy all lines
            $totalAmount = 0;
            foreach ($po->lines as $line) {
                // Validate line item type
                if ($line->inventoryItem && $line->inventoryItem->item_type !== 'service') {
                    throw new \Exception('Only service-type inventory items can be copied to Purchase Invoice');
                }

                $lineAmount = $line->qty * $line->unit_price;
                $totalAmount += $lineAmount;

                PurchaseInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'account_id' => $line->account_id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'item_code' => $line->item_code,
                    'item_name' => $line->item_name,
                    'unit_of_measure' => $line->unit_of_measure,
                    'description' => $line->description,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'amount' => $lineAmount,
                    'freight_cost' => $line->freight_cost ?? 0,
                    'handling_cost' => $line->handling_cost ?? 0,
                    'total_cost' => $lineAmount + ($line->freight_cost ?? 0) + ($line->handling_cost ?? 0),
                    'tax_code_id' => $line->tax_code_id,
                    'notes' => $line->notes,
                ]);
            }

            // Update invoice total amount
            $invoice->update(['total_amount' => $totalAmount]);

            return $invoice;
        });
    }

    /**
     * Validate if Purchase Order can be copied to Purchase Invoice
     */
    public function canCopyToPurchaseInvoice(PurchaseOrder $po): bool
    {
        return $po->order_type === 'service' &&
            $po->status === 'approved' &&
            $po->lines()->whereHas('inventoryItem', function ($query) {
                $query->where('item_type', 'service');
            })->exists();
    }

    /**
     * Get Purchase Order summary for invoice creation
     */
    public function getPurchaseOrderSummary(PurchaseOrder $po): array
    {
        if ($po->order_type !== 'service') {
            return [];
        }

        return [
            'id' => $po->id,
            'order_no' => $po->order_no,
            'date' => $po->date,
            'vendor_id' => $po->vendor_id,
            'vendor_name' => $po->vendor->name ?? '',
            'total_amount' => $po->total_amount,
            'freight_cost' => $po->freight_cost ?? 0,
            'handling_cost' => $po->handling_cost ?? 0,
            'insurance_cost' => $po->insurance_cost ?? 0,
            'total_cost' => $po->total_cost ?? $po->total_amount,
            'terms_conditions' => $po->terms_conditions,
            'payment_terms' => $po->payment_terms,
            'delivery_method' => $po->delivery_method,
            'lines_count' => $po->lines()->count(),
        ];
    }
}
