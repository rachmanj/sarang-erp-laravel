<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\SalesInvoiceGrpoCombination;
use App\Services\DocumentNumberingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesInvoiceService
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService
    ) {}

    /**
     * Create Sales Invoice from multiple GRPOs (must be from same PO)
     */
    public function createFromGoodsReceipts(array $grpoIds, array $data): SalesInvoice
    {
        // Validate all GRPOs are from same PO
        $grpos = GoodsReceipt::whereIn('id', $grpoIds)->get();

        if ($grpos->isEmpty()) {
            throw new \Exception('No valid GRPOs found');
        }

        $poIds = $grpos->pluck('source_po_id')->unique()->filter();

        if ($poIds->count() > 1) {
            throw new \Exception('All GRPOs must be from the same Purchase Order');
        }

        if ($poIds->isEmpty()) {
            throw new \Exception('GRPOs must be copied from Purchase Orders (source_po_id required)');
        }

        // Validate all GRPOs are from same vendor
        $vendorIds = $grpos->pluck('vendor_id')->unique();
        if ($vendorIds->count() > 1) {
            throw new \Exception('All GRPOs must be from the same vendor');
        }

        // Validate all GRPOs have item-type inventory items
        foreach ($grpos as $grpo) {
            foreach ($grpo->lines as $line) {
                if ($line->inventoryItem && $line->inventoryItem->item_type !== 'item') {
                    throw new \Exception('Only item-type inventory items can be included in Sales Invoice from GRPO');
                }
            }
        }

        return DB::transaction(function () use ($grpos, $data, $poIds) {
            // Create sales invoice with combined data
            $salesInvoice = SalesInvoice::create([
                'invoice_no' => null, // Will be generated
                'date' => $data['date'],
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'description' => 'From GRPOs: ' . implode(', ', $grpos->pluck('grn_no')->toArray()),
                'status' => 'draft',
                'total_amount' => 0,
                'posted_at' => null,
            ]);

            // Generate invoice number
            $invoiceNo = $this->documentNumberingService->generateNumber('sales_invoice', $salesInvoice->date);
            $salesInvoice->update(['invoice_no' => $invoiceNo]);

            // Combine all GRPO lines
            $totalAmount = 0;
            foreach ($grpos as $grpo) {
                foreach ($grpo->lines as $line) {
                    $lineAmount = $line->qty * $line->unit_price;
                    $totalAmount += $lineAmount;

                    SalesInvoiceLine::create([
                        'invoice_id' => $salesInvoice->id,
                        'account_id' => $line->account_id,
                        'description' => $line->description,
                        'qty' => $line->qty,
                        'unit_price' => $line->unit_price,
                        'amount' => $lineAmount,
                        'tax_code_id' => $line->tax_code_id,
                        'project_id' => $data['project_id'] ?? null,
                        'fund_id' => $data['fund_id'] ?? null,
                        'dept_id' => $data['dept_id'] ?? null,
                    ]);
                }

                // Create tracking record
                SalesInvoiceGrpoCombination::create([
                    'sales_invoice_id' => $salesInvoice->id,
                    'goods_receipt_id' => $grpo->id,
                ]);
            }

            // Update invoice total amount
            $salesInvoice->update(['total_amount' => $totalAmount]);

            return $salesInvoice;
        });
    }

    /**
     * Get available GRPOs for Sales Invoice creation
     */
    public function getAvailableGRPOs(array $filters = []): array
    {
        $query = GoodsReceipt::with(['vendor', 'lines.inventoryItem'])
            ->where('source_type', 'copy')
            ->where('status', 'received')
            ->whereNotNull('source_po_id');

        // Filter by vendor if specified
        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        // Filter by date range if specified
        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        // Filter by PO if specified
        if (isset($filters['source_po_id'])) {
            $query->where('source_po_id', $filters['source_po_id']);
        }

        return $query->get()
            ->map(function ($grpo) {
                return [
                    'id' => $grpo->id,
                    'grn_no' => $grpo->grn_no,
                    'date' => $grpo->date,
                    'vendor_id' => $grpo->vendor_id,
                    'vendor_name' => $grpo->vendor->name ?? '',
                    'source_po_id' => $grpo->source_po_id,
                    'total_amount' => $grpo->total_amount,
                    'lines_count' => $grpo->lines->count(),
                    'status' => $grpo->status,
                    'can_combine' => $this->canCombineGRPO($grpo),
                ];
            })
            ->toArray();
    }

    /**
     * Get GRPOs grouped by Purchase Order for combination
     */
    public function getGRPOsGroupedByPO(array $filters = []): array
    {
        $grpos = $this->getAvailableGRPOs($filters);

        $grouped = [];
        foreach ($grpos as $grpo) {
            $poId = $grpo['source_po_id'];
            if (!isset($grouped[$poId])) {
                $grouped[$poId] = [
                    'po_id' => $poId,
                    'grpos' => [],
                    'total_amount' => 0,
                    'vendor_name' => $grpo['vendor_name'],
                ];
            }
            $grouped[$poId]['grpos'][] = $grpo;
            $grouped[$poId]['total_amount'] += $grpo['total_amount'];
        }

        return array_values($grouped);
    }

    /**
     * Validate if GRPO can be combined with others
     */
    public function canCombineGRPO(GoodsReceipt $grpo): bool
    {
        return $grpo->source_type === 'copy' &&
            $grpo->status === 'received' &&
            $grpo->source_po_id !== null &&
            $grpo->lines()->whereHas('inventoryItem', function ($query) {
                $query->where('item_type', 'item');
            })->exists();
    }

    /**
     * Get Sales Invoice with GRPO combination details
     */
    public function getSalesInvoiceWithGRPOs(int $salesInvoiceId): array
    {
        $salesInvoice = SalesInvoice::with(['lines', 'customer'])->findOrFail($salesInvoiceId);

        $grpoCombinations = SalesInvoiceGrpoCombination::with(['goodsReceipt.vendor'])
            ->where('sales_invoice_id', $salesInvoiceId)
            ->get();

        return [
            'sales_invoice' => $salesInvoice,
            'grpo_combinations' => $grpoCombinations,
            'source_pos' => $grpoCombinations->pluck('goodsReceipt.source_po_id')->unique(),
            'vendors' => $grpoCombinations->pluck('goodsReceipt.vendor.name')->unique(),
        ];
    }

    /**
     * Validate GRPO combination before creating Sales Invoice
     */
    public function validateGRPOCombination(array $grpoIds): array
    {
        $grpos = GoodsReceipt::whereIn('id', $grpoIds)->get();

        $errors = [];

        if ($grpos->isEmpty()) {
            $errors[] = 'No valid GRPOs found';
            return $errors;
        }

        // Check if all GRPOs are from same PO
        $poIds = $grpos->pluck('source_po_id')->unique()->filter();
        if ($poIds->count() > 1) {
            $errors[] = 'All GRPOs must be from the same Purchase Order';
        }

        // Check if all GRPOs are from same vendor
        $vendorIds = $grpos->pluck('vendor_id')->unique();
        if ($vendorIds->count() > 1) {
            $errors[] = 'All GRPOs must be from the same vendor';
        }

        // Check if all GRPOs have item-type inventory items
        foreach ($grpos as $grpo) {
            foreach ($grpo->lines as $line) {
                if ($line->inventoryItem && $line->inventoryItem->item_type !== 'item') {
                    $errors[] = "GRPO {$grpo->grn_no} contains non-item type inventory items";
                    break;
                }
            }
        }

        return $errors;
    }
}
