<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrder;
use App\Services\DocumentNumberingService;
use App\Services\GRPOCopyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService,
        private GRPOCopyService $grpoCopyService
    ) {}

    public function index()
    {
        return view('goods_receipts.index');
    }

    public function create()
    {
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $purchaseOrders = DB::table('purchase_orders')->orderByDesc('id')->limit(50)->get(['id', 'order_no']);
        return view('goods_receipts.create', compact('vendors', 'accounts', 'taxCodes', 'purchaseOrders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $grn = GoodsReceipt::create([
                'grn_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);
            $grnNo = $this->documentNumberingService->generateNumber('goods_receipt', $data['date']);
            $grn->update(['grn_no' => $grnNo]);
            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float)$l['qty'] * (float)$l['unit_price'];
                $total += $amount;
                GoodsReceiptLine::create([
                    'grn_id' => $grn->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                    'unit_price' => (float)$l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                ]);
            }
            $grn->update(['total_amount' => $total]);
            return redirect()->route('goods-receipts.show', $grn->id)->with('success', 'Goods Receipt created');
        });
    }

    public function show(int $id)
    {
        $grn = GoodsReceipt::with('lines')->findOrFail($id);
        return view('goods_receipts.show', compact('grn'));
    }

    public function receive(int $id)
    {
        $grn = GoodsReceipt::findOrFail($id);
        if ($grn->status === 'received') {
            return back()->with('success', 'Already received');
        }
        $grn->update(['status' => 'received']);
        return back()->with('success', 'Goods Receipt marked as received');
    }

    public function createInvoice(int $id)
    {
        $grn = GoodsReceipt::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'business_partner_id' => $grn->business_partner_id,
            'description' => 'From GRN ' . ($grn->grn_no ?: ('#' . $grn->id)),
            'lines' => $grn->lines->map(function ($l) {
                return [
                    'account_id' => (int)$l->account_id,
                    'description' => $l->description,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes') + ['prefill' => $prefill, 'goods_receipt_id' => $grn->id]);
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

        return view('goods_receipts.create_from_po', compact('po', 'availableLines'));
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

            return redirect()->route('goods-receipts.show', $grpo->id)
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
}
