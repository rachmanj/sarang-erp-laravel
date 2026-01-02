<?php

namespace App\Services;

use App\Models\SalesQuotation;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesQuotationLine;
use App\Services\DocumentNumberingService;
use App\Services\CompanyEntityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class QuotationConversionService
{
    protected $documentNumberingService;
    protected $companyEntityService;

    public function __construct(
        DocumentNumberingService $documentNumberingService,
        CompanyEntityService $companyEntityService
    ) {
        $this->documentNumberingService = $documentNumberingService;
        $this->companyEntityService = $companyEntityService;
    }

    public function convertQuotationToSalesOrder($quotationId, $options = [])
    {
        return DB::transaction(function () use ($quotationId, $options) {
            $quotation = SalesQuotation::with(['lines', 'businessPartner', 'companyEntity'])->findOrFail($quotationId);

            if (!$quotation->canBeConverted()) {
                throw new Exception('Quotation cannot be converted in current status');
            }

            $entityId = $quotation->company_entity_id ?? $this->companyEntityService->getDefaultEntity()->id;

            $orderNo = $options['order_no'] ?? $this->documentNumberingService->generateNumber(
                'sales_order',
                $options['date'] ?? now()->toDateString(),
                ['company_entity_id' => $entityId]
            );

            $salesOrder = SalesOrder::create([
                'order_no' => $orderNo,
                'reference_no' => $quotation->quotation_no,
                'date' => $options['date'] ?? now()->toDateString(),
                'expected_delivery_date' => $options['expected_delivery_date'] ?? $quotation->valid_until_date,
                'business_partner_id' => $quotation->business_partner_id,
                'company_entity_id' => $entityId,
                'currency_id' => $quotation->currency_id,
                'exchange_rate' => $quotation->exchange_rate,
                'warehouse_id' => $quotation->warehouse_id,
                'description' => $quotation->description,
                'notes' => $quotation->notes . ($quotation->notes ? "\n\n" : '') . "Converted from Quotation: {$quotation->quotation_no}",
                'terms_conditions' => $quotation->terms_conditions,
                'payment_terms' => $quotation->payment_terms,
                'delivery_method' => $quotation->delivery_method,
                'freight_cost' => $quotation->freight_cost,
                'handling_cost' => $quotation->handling_cost,
                'insurance_cost' => $quotation->insurance_cost,
                'discount_amount' => $quotation->discount_amount,
                'discount_percentage' => $quotation->discount_percentage,
                'net_amount' => $quotation->net_amount,
                'total_amount' => $quotation->total_amount,
                'total_amount_foreign' => $quotation->total_amount_foreign,
                'order_type' => $quotation->order_type,
                'status' => 'draft',
                'approval_status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            foreach ($quotation->lines as $quotationLine) {
                SalesOrderLine::create([
                    'order_id' => $salesOrder->id,
                    'account_id' => $quotationLine->account_id,
                    'inventory_item_id' => $quotationLine->inventory_item_id,
                    'item_code' => $quotationLine->item_code,
                    'item_name' => $quotationLine->item_name,
                    'unit_of_measure' => $quotationLine->unit_of_measure,
                    'order_unit_id' => $quotationLine->order_unit_id,
                    'description' => $quotationLine->description,
                    'qty' => $quotationLine->qty,
                    'base_quantity' => $quotationLine->base_quantity,
                    'unit_conversion_factor' => $quotationLine->unit_conversion_factor,
                    'delivered_qty' => 0,
                    'pending_qty' => $quotationLine->qty,
                    'unit_price' => $quotationLine->unit_price,
                    'unit_price_foreign' => $quotationLine->unit_price_foreign,
                    'amount' => $quotationLine->amount,
                    'amount_foreign' => $quotationLine->amount_foreign,
                    'freight_cost' => $quotationLine->freight_cost,
                    'handling_cost' => $quotationLine->handling_cost,
                    'total_cost' => $quotationLine->freight_cost + $quotationLine->handling_cost,
                    'discount_amount' => $quotationLine->discount_amount,
                    'discount_percentage' => $quotationLine->discount_percentage,
                    'net_amount' => $quotationLine->net_amount,
                    'tax_code_id' => $quotationLine->tax_code_id,
                    'vat_rate' => $quotationLine->vat_rate,
                    'wtax_rate' => $quotationLine->wtax_rate,
                    'notes' => $quotationLine->notes,
                    'status' => 'pending',
                ]);
            }

            $quotation->update([
                'status' => 'converted',
                'converted_to_sales_order_id' => $salesOrder->id,
                'converted_at' => now(),
            ]);

            Log::info('Quotation converted to Sales Order', [
                'quotation_id' => $quotation->id,
                'quotation_no' => $quotation->quotation_no,
                'sales_order_id' => $salesOrder->id,
                'sales_order_no' => $salesOrder->order_no,
            ]);

            return $salesOrder->fresh(['lines', 'businessPartner', 'companyEntity']);
        });
    }
}
