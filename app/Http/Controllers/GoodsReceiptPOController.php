<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Models\PurchaseOrder;
use App\Services\DocumentNumberingService;
use App\Services\GRPOCopyService;
use App\Services\DocumentClosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptPOController extends Controller
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService,
        private GRPOCopyService $grpoCopyService,
        private DocumentClosureService $documentClosureService
    ) {}

    public function index()
    {
        return view('goods_receipt_pos.index');
    }

    public function create()
    {
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $categories = DB::table('product_categories')->orderBy('name')->get();
        // Don't load POs initially - will be loaded via AJAX based on vendor selection
        return view('goods_receipt_pos.create', compact('vendors', 'accounts', 'taxCodes', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($data) {
            $grpo = GoodsReceiptPO::create([
                'grn_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);
            $grpoNo = $this->documentNumberingService->generateNumber('goods_receipt', $data['date']);
            $grpo->update(['grn_no' => $grpoNo]);
            foreach ($data['lines'] as $l) {
                GoodsReceiptPOLine::create([
                    'grpo_id' => $grpo->id,
                    'item_id' => $l['item_id'],
                    'account_id' => 0, // Set a default account_id to avoid the error
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                ]);
            }
            return redirect()->route('goods-receipt-pos.show', $grpo->id)->with('success', 'Goods Receipt PO created');
        });
    }

    public function show(int $id)
    {
        $grpo = GoodsReceiptPO::with('lines')->findOrFail($id);
        return view('goods_receipt_pos.show', compact('grpo'));
    }

    public function receive(int $id)
    {
        $grpo = GoodsReceiptPO::findOrFail($id);
        if ($grpo->status === 'received') {
            return back()->with('success', 'Already received');
        }
        $grpo->update(['status' => 'received']);
        return back()->with('success', 'Goods Receipt PO marked as received');
    }

    public function createInvoice(int $id)
    {
        $grpo = GoodsReceiptPO::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('name')->get();
        $departments = DB::table('departments')->orderBy('name')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'business_partner_id' => $grpo->business_partner_id,
            'description' => 'From GRPO ' . ($grpo->grn_no ?: ('#' . $grpo->id)),
            'lines' => $grpo->lines->map(function ($l) {
                return [
                    'account_id' => (int)$l->account_id,
                    'description' => $l->description,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes', 'projects', 'departments') + ['prefill' => $prefill, 'goods_receipt_po_id' => $grpo->id]);
    }

    /**
     * Create GRPO from Purchase Order
     */
    public function createFromPO($poId)
    {
        $po = PurchaseOrder::with(['lines.inventoryItem', 'vendor'])->findOrFail($poId);

        if (!$this->grpoCopyService->canCopyToGRPO($po)) {
            return back()->with('error', 'Purchase Order cannot be copied to GRPO. Only approved Item Purchase Orders are allowed.');
        }

        $availableLines = $this->grpoCopyService->getAvailableLines($po);

        return view('goods_receipt_pos.create_from_po', compact('po', 'availableLines'));
    }

    /**
     * Store GRPO copied from Purchase Order
     */
    public function storeFromPO(Request $request, $poId)
    {
        $po = PurchaseOrder::findOrFail($poId);

        if (!$this->grpoCopyService->canCopyToGRPO($po)) {
            return back()->with('error', 'Purchase Order cannot be copied to GRPO. Only approved Item Purchase Orders are allowed.');
        }

        $selectedLines = $request->input('selected_lines', null);

        try {
            $grpo = $this->grpoCopyService->copyFromPurchaseOrder($po, $selectedLines);

            return redirect()->route('goods-receipt-pos.show', $grpo->id)
                ->with('success', 'GRPO created from Purchase Order successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating GRPO: ' . $e->getMessage());
        }
    }

    /**
     * Get Purchase Orders available for GRPO creation
     */
    public function getAvailablePOs(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'lines.inventoryItem'])
            ->where('order_type', 'item')
            ->where('status', 'approved');

        // Filter by vendor if specified
        if ($request->has('business_partner_id')) {
            $query->where('business_partner_id', $request->business_partner_id);
        }

        // Filter by date range if specified
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $pos = $query->get()->map(function ($po) {
            return [
                'id' => $po->id,
                'order_no' => $po->order_no,
                'date' => $po->date,
                'business_partner_id' => $po->business_partner_id,
                'vendor_name' => $po->vendor->name ?? '',
                'total_amount' => $po->total_amount,
                'lines_count' => $po->lines->count(),
                'can_copy_to_grpo' => $this->grpoCopyService->canCopyToGRPO($po),
            ];
        });

        return response()->json(['purchase_orders' => $pos]);
    }

    /**
     * Get Purchase Orders for specific vendor with remaining quantities
     */
    public function getVendorPOs(Request $request)
    {
        $vendorId = $request->input('business_partner_id');

        if (!$vendorId) {
            return response()->json(['purchase_orders' => []]);
        }

        $pos = PurchaseOrder::with(['lines'])
            ->where('business_partner_id', $vendorId)
            ->whereIn('status', ['approved', 'ordered'])
            ->where('order_type', 'item')
            ->get()
            ->filter(function ($po) {
                // Only include POs that have remaining quantities to be received
                return $po->lines->where('pending_qty', '>', 0)->count() > 0;
            })
            ->map(function ($po) {
                return [
                    'id' => $po->id,
                    'order_no' => $po->order_no,
                    'date' => $po->date->format('Y-m-d'),
                    'total_amount' => $po->total_amount,
                    'remaining_lines_count' => $po->lines->where('pending_qty', '>', 0)->count(),
                ];
            });

        return response()->json(['purchase_orders' => $pos]);
    }

    /**
     * Get remaining lines from Purchase Order for copying
     */
    public function getRemainingPOLines(Request $request)
    {
        $poId = $request->input('purchase_order_id');

        if (!$poId) {
            return response()->json(['lines' => []]);
        }

        $po = PurchaseOrder::with(['lines.inventoryItem'])->findOrFail($poId);

        $remainingLines = $po->lines
            ->where('pending_qty', '>', 0)
            ->map(function ($line) {
                $itemCode = $line->item_code ?: ($line->inventoryItem->code ?? '');
                $itemName = $line->item_name ?: ($line->inventoryItem->name ?? '');

                return [
                    'id' => $line->id,
                    'item_id' => $line->inventory_item_id,
                    'item_display' => $itemCode && $itemName ? "{$itemCode} - {$itemName}" : '',
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'description' => $line->description,
                    'qty' => $line->pending_qty, // Use remaining quantity
                    'unit_price' => $line->unit_price,
                ];
            });

        return response()->json(['lines' => $remainingLines]);
    }
}
