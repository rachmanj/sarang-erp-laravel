<?php

namespace App\Services;

use App\Models\SalesQuotation;
use App\Models\SalesQuotationLine;
use App\Models\SalesQuotationApproval;
use App\Services\DocumentNumberingService;
use App\Services\ApprovalWorkflowService;
use App\Services\CompanyEntityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class QuotationService
{
    protected $documentNumberingService;
    protected $approvalWorkflowService;
    protected $companyEntityService;

    public function __construct(
        DocumentNumberingService $documentNumberingService,
        ApprovalWorkflowService $approvalWorkflowService,
        CompanyEntityService $companyEntityService
    ) {
        $this->documentNumberingService = $documentNumberingService;
        $this->approvalWorkflowService = $approvalWorkflowService;
        $this->companyEntityService = $companyEntityService;
    }

    public function createQuotation($data)
    {
        return DB::transaction(function () use ($data) {
            $entityId = $data['company_entity_id'] ?? $this->companyEntityService->getDefaultEntity()->id;

            $quotationNo = $data['quotation_no'] ?? $this->documentNumberingService->generateNumber(
                'sales_quotation',
                $data['date'],
                ['company_entity_id' => $entityId]
            );

            $quotation = SalesQuotation::create([
                'quotation_no' => $quotationNo,
                'reference_no' => $data['reference_no'] ?? null,
                'date' => $data['date'],
                'valid_until_date' => $data['valid_until_date'],
                'business_partner_id' => $data['business_partner_id'],
                'currency_id' => $data['currency_id'] ?? 1,
                'exchange_rate' => $data['exchange_rate'] ?? 1.000000,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'company_entity_id' => $entityId,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? null,
                'freight_cost' => $data['freight_cost'] ?? 0,
                'handling_cost' => $data['handling_cost'] ?? 0,
                'insurance_cost' => $data['insurance_cost'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_percentage' => $data['discount_percentage'] ?? 0,
                'order_type' => $data['order_type'] ?? 'item',
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            $totalAmount = 0;
            $totalAmountForeign = 0;
            $totalLineDiscountAmount = 0;

            foreach ($data['lines'] as $index => $lineData) {
                $originalAmount = $lineData['qty'] * $lineData['unit_price'];
                $vatAmount = $originalAmount * (($lineData['vat_rate'] ?? 0) / 100);
                $wtaxAmount = $originalAmount * (($lineData['wtax_rate'] ?? 0) / 100);
                $amount = $originalAmount + $vatAmount - $wtaxAmount;

                // Calculate line-level discount (manual)
                $lineDiscountAmount = $lineData['discount_amount'] ?? 0;
                $lineDiscountPercentage = $lineData['discount_percentage'] ?? 0;

                if ($lineDiscountPercentage > 0 && $lineDiscountAmount == 0) {
                    // Calculate discount amount from percentage
                    $lineDiscountAmount = ($amount * $lineDiscountPercentage) / 100;
                } elseif ($lineDiscountAmount > 0 && $lineDiscountPercentage == 0) {
                    // Calculate discount percentage from amount
                    $lineDiscountPercentage = $amount > 0 ? ($lineDiscountAmount / $amount) * 100 : 0;
                }

                $lineNetAmount = $amount - $lineDiscountAmount;
                $totalAmount += $amount; // Sum original amounts (before discounts)
                $totalLineDiscountAmount += $lineDiscountAmount;

                $unitPriceForeign = $lineData['unit_price_foreign'] ?? $lineData['unit_price'];
                $amountForeign = $lineData['qty'] * $unitPriceForeign;
                $totalAmountForeign += $amountForeign;

                $inventoryItemId = null;
                $accountId = null;
                $itemCode = null;
                $itemName = null;
                $unitOfMeasure = null;

                if ($data['order_type'] === 'item' && isset($lineData['item_id'])) {
                    $inventoryItemId = $lineData['item_id'];
                    $inventoryItem = \App\Models\InventoryItem::find($inventoryItemId);
                    if ($inventoryItem) {
                        $itemCode = $inventoryItem->code;
                        $itemName = $inventoryItem->name;
                        $unitOfMeasure = $inventoryItem->unit_of_measure;
                        $accountId = $inventoryItem->category?->sales_account_id;
                    }
                } else {
                    $accountId = $lineData['item_id'] ?? null;
                }

                SalesQuotationLine::create([
                    'quotation_id' => $quotation->id,
                    'account_id' => $accountId,
                    'inventory_item_id' => $inventoryItemId,
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'unit_of_measure' => $unitOfMeasure,
                    'order_unit_id' => $lineData['order_unit_id'] ?? null,
                    'description' => $lineData['description'] ?? null,
                    'qty' => $lineData['qty'],
                    'base_quantity' => $lineData['base_quantity'] ?? $lineData['qty'],
                    'unit_conversion_factor' => $lineData['unit_conversion_factor'] ?? 1.0000,
                    'unit_price' => $lineData['unit_price'],
                    'unit_price_foreign' => $unitPriceForeign,
                    'amount' => $amount,
                    'amount_foreign' => $amountForeign,
                    'freight_cost' => $lineData['freight_cost'] ?? 0,
                    'handling_cost' => $lineData['handling_cost'] ?? 0,
                    'discount_amount' => $lineDiscountAmount,
                    'discount_percentage' => $lineDiscountPercentage,
                    'net_amount' => $lineNetAmount,
                    'tax_code_id' => $lineData['tax_code_id'] ?? null,
                    'vat_rate' => $lineData['vat_rate'] ?? 0,
                    'wtax_rate' => $lineData['wtax_rate'] ?? 0,
                    'notes' => $lineData['notes'] ?? null,
                    'line_order' => $index + 1,
                ]);
            }

            // Calculate header-level discount (manual)
            $headerDiscountAmount = $data['discount_amount'] ?? 0;
            $headerDiscountPercentage = $data['discount_percentage'] ?? 0;

            if ($headerDiscountPercentage > 0 && $headerDiscountAmount == 0) {
                // Calculate discount amount from percentage
                $headerDiscountAmount = ($totalAmount * $headerDiscountPercentage) / 100;
            } elseif ($headerDiscountAmount > 0 && $headerDiscountPercentage == 0) {
                // Calculate discount percentage from amount
                $headerDiscountPercentage = $totalAmount > 0 ? ($headerDiscountAmount / $totalAmount) * 100 : 0;
            }

            // Total discount = line discounts + header discount
            $totalDiscountAmount = $totalLineDiscountAmount + $headerDiscountAmount;

            $quotation->update([
                'total_amount' => $totalAmount,
                'total_amount_foreign' => $totalAmountForeign,
                'discount_amount' => $totalDiscountAmount,
                'discount_percentage' => $headerDiscountPercentage,
                'net_amount' => $totalAmount - $totalDiscountAmount,
            ]);

            // Apply customer pricing tier discounts (may override manual discounts)
            $this->applyCustomerPricingTier($quotation);
            $this->createApprovalWorkflow($quotation);

            return $quotation->fresh(['lines', 'businessPartner', 'companyEntity']);
        });
    }

    public function updateQuotation($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $quotation = SalesQuotation::findOrFail($id);

            if ($quotation->status !== 'draft') {
                throw new Exception('Quotation can only be updated when in draft status');
            }

            $quotation->update([
                'reference_no' => $data['reference_no'] ?? $quotation->reference_no,
                'date' => $data['date'] ?? $quotation->date,
                'valid_until_date' => $data['valid_until_date'] ?? $quotation->valid_until_date,
                'business_partner_id' => $data['business_partner_id'] ?? $quotation->business_partner_id,
                'currency_id' => $data['currency_id'] ?? $quotation->currency_id,
                'exchange_rate' => $data['exchange_rate'] ?? $quotation->exchange_rate,
                'warehouse_id' => $data['warehouse_id'] ?? $quotation->warehouse_id,
                'description' => $data['description'] ?? $quotation->description,
                'notes' => $data['notes'] ?? $quotation->notes,
                'terms_conditions' => $data['terms_conditions'] ?? $quotation->terms_conditions,
                'payment_terms' => $data['payment_terms'] ?? $quotation->payment_terms,
                'delivery_method' => $data['delivery_method'] ?? $quotation->delivery_method,
                'freight_cost' => $data['freight_cost'] ?? $quotation->freight_cost,
                'handling_cost' => $data['handling_cost'] ?? $quotation->handling_cost,
                'insurance_cost' => $data['insurance_cost'] ?? $quotation->insurance_cost,
                'discount_amount' => $data['discount_amount'] ?? $quotation->discount_amount,
                'discount_percentage' => $data['discount_percentage'] ?? $quotation->discount_percentage,
                'order_type' => $data['order_type'] ?? $quotation->order_type,
                'updated_by' => Auth::id(),
            ]);

            if (isset($data['lines'])) {
                $quotation->lines()->delete();

                $totalAmount = 0;
                $totalAmountForeign = 0;
                $totalLineDiscountAmount = 0;

                foreach ($data['lines'] as $index => $lineData) {
                    $originalAmount = $lineData['qty'] * $lineData['unit_price'];
                    $vatAmount = $originalAmount * (($lineData['vat_rate'] ?? 0) / 100);
                    $wtaxAmount = $originalAmount * (($lineData['wtax_rate'] ?? 0) / 100);
                    $amount = $originalAmount + $vatAmount - $wtaxAmount;

                    // Calculate line-level discount (manual)
                    $lineDiscountAmount = $lineData['discount_amount'] ?? 0;
                    $lineDiscountPercentage = $lineData['discount_percentage'] ?? 0;

                    if ($lineDiscountPercentage > 0 && $lineDiscountAmount == 0) {
                        // Calculate discount amount from percentage
                        $lineDiscountAmount = ($amount * $lineDiscountPercentage) / 100;
                    } elseif ($lineDiscountAmount > 0 && $lineDiscountPercentage == 0) {
                        // Calculate discount percentage from amount
                        $lineDiscountPercentage = $amount > 0 ? ($lineDiscountAmount / $amount) * 100 : 0;
                    }

                    $lineNetAmount = $amount - $lineDiscountAmount;
                    $totalAmount += $amount; // Sum original amounts (before discounts)
                    $totalLineDiscountAmount += $lineDiscountAmount;

                    $unitPriceForeign = $lineData['unit_price_foreign'] ?? $lineData['unit_price'];
                    $amountForeign = $lineData['qty'] * $unitPriceForeign;
                    $totalAmountForeign += $amountForeign;

                    $inventoryItemId = null;
                    $accountId = null;
                    $itemCode = null;
                    $itemName = null;
                    $unitOfMeasure = null;

                    if ($data['order_type'] === 'item' && isset($lineData['item_id'])) {
                        $inventoryItemId = $lineData['item_id'];
                        $inventoryItem = \App\Models\InventoryItem::find($inventoryItemId);
                        if ($inventoryItem) {
                            $itemCode = $inventoryItem->code;
                            $itemName = $inventoryItem->name;
                            $unitOfMeasure = $inventoryItem->unit_of_measure;
                            $accountId = $inventoryItem->category?->sales_account_id;
                        }
                    } else {
                        $accountId = $lineData['item_id'] ?? null;
                    }

                    SalesQuotationLine::create([
                        'quotation_id' => $quotation->id,
                        'account_id' => $accountId,
                        'inventory_item_id' => $inventoryItemId,
                        'item_code' => $itemCode,
                        'item_name' => $itemName,
                        'unit_of_measure' => $unitOfMeasure,
                        'order_unit_id' => $lineData['order_unit_id'] ?? null,
                        'description' => $lineData['description'] ?? null,
                        'qty' => $lineData['qty'],
                        'base_quantity' => $lineData['base_quantity'] ?? $lineData['qty'],
                        'unit_conversion_factor' => $lineData['unit_conversion_factor'] ?? 1.0000,
                        'unit_price' => $lineData['unit_price'],
                        'unit_price_foreign' => $unitPriceForeign,
                        'amount' => $amount,
                        'amount_foreign' => $amountForeign,
                        'freight_cost' => $lineData['freight_cost'] ?? 0,
                        'handling_cost' => $lineData['handling_cost'] ?? 0,
                        'discount_amount' => $lineDiscountAmount,
                        'discount_percentage' => $lineDiscountPercentage,
                        'net_amount' => $lineNetAmount,
                        'tax_code_id' => $lineData['tax_code_id'] ?? null,
                        'vat_rate' => $lineData['vat_rate'] ?? 0,
                        'wtax_rate' => $lineData['wtax_rate'] ?? 0,
                        'notes' => $lineData['notes'] ?? null,
                        'line_order' => $index + 1,
                    ]);
                }

                // Calculate header-level discount (manual)
                $headerDiscountAmount = $data['discount_amount'] ?? 0;
                $headerDiscountPercentage = $data['discount_percentage'] ?? 0;

                if ($headerDiscountPercentage > 0 && $headerDiscountAmount == 0) {
                    // Calculate discount amount from percentage
                    $headerDiscountAmount = ($totalAmount * $headerDiscountPercentage) / 100;
                } elseif ($headerDiscountAmount > 0 && $headerDiscountPercentage == 0) {
                    // Calculate discount percentage from amount
                    $headerDiscountPercentage = $totalAmount > 0 ? ($headerDiscountAmount / $totalAmount) * 100 : 0;
                }

                // Total discount = line discounts + header discount
                $totalDiscountAmount = $totalLineDiscountAmount + $headerDiscountAmount;

                $quotation->update([
                    'total_amount' => $totalAmount,
                    'total_amount_foreign' => $totalAmountForeign,
                    'discount_amount' => $totalDiscountAmount,
                    'discount_percentage' => $headerDiscountPercentage,
                    'net_amount' => $totalAmount - $totalDiscountAmount,
                ]);

                // Apply customer pricing tier discounts (may override manual discounts)
                $this->applyCustomerPricingTier($quotation);
            }

            return $quotation->fresh(['lines', 'businessPartner', 'companyEntity']);
        });
    }

    public function sendQuotation($id)
    {
        $quotation = SalesQuotation::findOrFail($id);

        if (!$quotation->canBeSent()) {
            throw new Exception('Quotation cannot be sent in current status');
        }

        $quotation->update([
            'status' => 'sent',
        ]);

        return $quotation;
    }

    public function acceptQuotation($id)
    {
        $quotation = SalesQuotation::findOrFail($id);

        if (!$quotation->canBeAccepted()) {
            throw new Exception('Quotation cannot be accepted in current status');
        }

        $quotation->update([
            'status' => 'accepted',
        ]);

        return $quotation;
    }

    public function rejectQuotation($id, $reason = null)
    {
        $quotation = SalesQuotation::findOrFail($id);

        if (!$quotation->canBeRejected()) {
            throw new Exception('Quotation cannot be rejected in current status');
        }

        $quotation->update([
            'status' => 'rejected',
            'notes' => $quotation->notes . ($reason ? "\n\nRejection Reason: " . $reason : ''),
        ]);

        return $quotation;
    }

    public function expireQuotation($id)
    {
        $quotation = SalesQuotation::findOrFail($id);

        if ($quotation->status === 'converted' || $quotation->status === 'rejected') {
            return $quotation;
        }

        $quotation->update([
            'status' => 'expired',
        ]);

        return $quotation;
    }

    public function applyCustomerPricingTier($quotation)
    {
        $pricingTier = $quotation->getCustomerPricingTier();

        if (!$pricingTier || $pricingTier->discount_percentage == 0) {
            return;
        }

        $discountAmount = ($quotation->total_amount * $pricingTier->discount_percentage) / 100;

        $quotation->update([
            'discount_percentage' => $pricingTier->discount_percentage,
            'discount_amount' => $discountAmount,
            'net_amount' => $quotation->total_amount - $discountAmount,
        ]);

        foreach ($quotation->lines as $line) {
            $lineDiscountAmount = ($line->amount * $pricingTier->discount_percentage) / 100;
            $line->update([
                'discount_percentage' => $pricingTier->discount_percentage,
                'discount_amount' => $lineDiscountAmount,
                'net_amount' => $line->amount - $lineDiscountAmount,
            ]);
        }
    }

    public function createApprovalWorkflow($quotation)
    {
        try {
            // Use the approval workflow service to get approval records
            $approvalRecords = $this->approvalWorkflowService->createWorkflowForDocument(
                'sales_quotation',
                $quotation->id,
                $quotation->net_amount
            );

            // Create the approval records
            foreach ($approvalRecords as $record) {
                \App\Models\SalesQuotationApproval::create([
                    'sales_quotation_id' => $record['document_id'],
                    'user_id' => $record['user_id'],
                    'approval_level' => $record['role_name'],
                    'status' => $record['status'],
                ]);
            }

            Log::info("Approval workflow created successfully for Quotation {$quotation->quotation_no} with " . count($approvalRecords) . " approval records");
        } catch (Exception $e) {
            Log::warning('Failed to create approval workflow for quotation', [
                'quotation_id' => $quotation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkExpiration()
    {
        $expiredQuotations = SalesQuotation::where('valid_until_date', '<', now()->toDateString())
            ->whereNotIn('status', ['converted', 'rejected', 'expired'])
            ->get();

        foreach ($expiredQuotations as $quotation) {
            $this->expireQuotation($quotation->id);
        }

        return $expiredQuotations->count();
    }
}
