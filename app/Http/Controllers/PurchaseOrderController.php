<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderApproval;
use App\Models\InventoryItem;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Services\PurchaseService;
use App\Services\GRPOCopyService;
use App\Services\PurchaseInvoiceCopyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    protected $purchaseService;
    protected $grpoCopyService;
    protected $purchaseInvoiceCopyService;

    public function __construct(
        PurchaseService $purchaseService,
        GRPOCopyService $grpoCopyService,
        PurchaseInvoiceCopyService $purchaseInvoiceCopyService
    ) {
        $this->purchaseService = $purchaseService;
        $this->grpoCopyService = $grpoCopyService;
        $this->purchaseInvoiceCopyService = $purchaseInvoiceCopyService;
    }

    public function index()
    {
        return view('purchase_orders.index');
    }

    public function create()
    {
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();

        return view('purchase_orders.create', compact('vendors', 'accounts', 'taxCodes', 'inventoryItems'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'expected_delivery_date' => ['nullable', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'delivery_method' => ['nullable', 'string', 'max:100'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'handling_cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'order_type' => ['required', 'in:item,service'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.wtax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $po = $this->purchaseService->createPurchaseOrder($data);
            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', 'Purchase Order created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating purchase order: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $order = PurchaseOrder::with(['lines', 'businessPartner', 'approvals.user', 'approvedBy', 'createdBy'])
            ->findOrFail($id);
        return view('purchase_orders.show', compact('order'));
    }


    public function createInvoice(int $id)
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'business_partner_id' => $order->business_partner_id,
            'description' => 'From PO ' . ($order->order_no ?: ('#' . $order->id)),
            'lines' => $order->lines->map(function ($l) {
                return [
                    'account_id' => (int)$l->account_id,
                    'description' => $l->description,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes') + ['prefill' => $prefill, 'purchase_order_id' => $order->id]);
    }

    public function createAssets(int $id)
    {
        $this->authorize('create', Asset::class);

        $order = PurchaseOrder::with(['lines.account', 'businessPartner'])->findOrFail($id);
        $assetCategories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $funds = DB::table('funds')->orderBy('name')->get();
        $projects = DB::table('projects')->orderBy('name')->get();
        $departments = DB::table('departments')->orderBy('name')->get();

        // Filter lines that could be assets (typically inventory or equipment accounts)
        $assetLines = $order->lines->filter(function ($line) {
            $accountCode = $line->account->code ?? '';
            // Check if account code suggests it's an asset (typically starts with 1.1.x for fixed assets)
            return str_starts_with($accountCode, '1.1.') ||
                str_contains(strtolower($line->description ?? ''), 'equipment') ||
                str_contains(strtolower($line->description ?? ''), 'computer') ||
                str_contains(strtolower($line->description ?? ''), 'furniture') ||
                str_contains(strtolower($line->description ?? ''), 'vehicle');
        });

        return view('purchase_orders.create-assets', compact(
            'order',
            'assetCategories',
            'funds',
            'projects',
            'departments',
            'assetLines'
        ));
    }

    public function storeAssets(Request $request, int $id)
    {
        $this->authorize('create', Asset::class);

        $order = PurchaseOrder::with(['lines.account', 'businessPartner'])->findOrFail($id);

        $request->validate([
            'assets' => 'required|array|min:1',
            'assets.*.line_id' => 'required|exists:purchase_order_lines,id',
            'assets.*.code' => 'required|string|max:50|unique:assets,code',
            'assets.*.name' => 'required|string|max:255',
            'assets.*.description' => 'nullable|string|max:1000',
            'assets.*.serial_number' => 'nullable|string|max:100',
            'assets.*.category_id' => 'required|exists:asset_categories,id',
            'assets.*.acquisition_cost' => 'required|numeric|min:0',
            'assets.*.salvage_value' => 'nullable|numeric|min:0',
            'assets.*.method' => 'required|in:straight_line,declining_balance,double_declining_balance',
            'assets.*.life_months' => 'required|integer|min:1|max:600',
            'assets.*.placed_in_service_date' => 'required|date',
            'assets.*.fund_id' => 'nullable|exists:funds,id',
            'assets.*.project_id' => 'nullable|exists:projects,id',
            'assets.*.department_id' => 'nullable|exists:departments,id',
        ]);

        return DB::transaction(function () use ($request, $order) {
            $createdAssets = [];

            foreach ($request->get('assets') as $assetData) {
                $line = $order->lines->find($assetData['line_id']);
                if (!$line) {
                    continue;
                }

                $asset = Asset::create([
                    'code' => $assetData['code'],
                    'name' => $assetData['name'],
                    'description' => $assetData['description'],
                    'serial_number' => $assetData['serial_number'],
                    'category_id' => $assetData['category_id'],
                    'acquisition_cost' => $assetData['acquisition_cost'],
                    'salvage_value' => $assetData['salvage_value'] ?? 0,
                    'method' => $assetData['method'],
                    'life_months' => $assetData['life_months'],
                    'placed_in_service_date' => $assetData['placed_in_service_date'],
                    'fund_id' => $assetData['fund_id'],
                    'project_id' => $assetData['project_id'],
                    'department_id' => $assetData['department_id'],
                    'business_partner_id' => $order->business_partner_id,
                    'purchase_invoice_id' => null, // Will be set when invoice is created
                    'status' => 'active',
                    'current_book_value' => $assetData['acquisition_cost'],
                    'accumulated_depreciation' => 0,
                ]);

                $createdAssets[] = $asset;
            }

            return redirect()->route('purchase-orders.show', $order->id)
                ->with('success', 'Assets created successfully from Purchase Order');
        });
    }

    public function getAssetCategories()
    {
        $this->authorize('view', AssetCategory::class);

        return response()->json(
            AssetCategory::where('is_active', true)
                ->select('id', 'code', 'name', 'method_default', 'life_months_default', 'salvage_value_default')
                ->orderBy('name')
                ->get()
        );
    }

    public function approve(int $id, Request $request)
    {
        $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->purchaseService->approvePurchaseOrder($id, Auth::id(), $request->comments);
            return back()->with('success', 'Purchase Order approved successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error approving purchase order: ' . $e->getMessage());
        }
    }

    public function reject(int $id, Request $request)
    {
        $request->validate([
            'comments' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->purchaseService->rejectPurchaseOrder($id, Auth::id(), $request->comments);
            return back()->with('success', 'Purchase Order rejected');
        } catch (\Exception $e) {
            return back()->with('error', 'Error rejecting purchase order: ' . $e->getMessage());
        }
    }

    public function receive(int $id)
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);
        return view('purchase_orders.receive', compact('order'));
    }

    public function processReceive(int $id, Request $request)
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'integer', 'exists:purchase_order_lines,id'],
            'lines.*.received_qty' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->purchaseService->receivePurchaseOrder($id, $data);
            return redirect()->route('purchase-orders.show', $id)
                ->with('success', 'Purchase Order received successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error receiving purchase order: ' . $e->getMessage());
        }
    }

    public function close(int $id)
    {
        try {
            $this->purchaseService->closePurchaseOrder($id);
            return back()->with('success', 'Purchase Order closed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error closing purchase order: ' . $e->getMessage());
        }
    }

    public function compareSuppliers(Request $request)
    {
        $request->validate([
            'item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
        ]);

        try {
            $suppliers = $this->purchaseService->compareSuppliers($request->item_id, $request->quantity);
            return response()->json($suppliers);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getInventoryItems()
    {
        return response()->json(
            InventoryItem::active()
                ->select('id', 'code', 'name', 'unit_of_measure', 'purchase_price')
                ->orderBy('name')
                ->get()
        );
    }

    public function getItemDetails(int $id)
    {
        $item = InventoryItem::findOrFail($id);

        return response()->json([
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'unit_of_measure' => $item->unit_of_measure,
            'purchase_price' => $item->purchase_price,
            'current_stock' => $item->current_stock,
            'min_stock_level' => $item->min_stock_level,
            'reorder_point' => $item->reorder_point,
        ]);
    }

    /**
     * Copy Purchase Order to GRPO (for Item POs)
     */
    public function copyToGRPO(Request $request, $id)
    {
        $po = PurchaseOrder::with('lines.inventoryItem')->findOrFail($id);

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
     * Copy Service Purchase Order to Purchase Invoice
     */
    public function copyToPurchaseInvoice($id)
    {
        $po = PurchaseOrder::with('lines.inventoryItem')->findOrFail($id);

        if (!$this->purchaseInvoiceCopyService->canCopyToPurchaseInvoice($po)) {
            return back()->with('error', 'Purchase Order cannot be copied to Purchase Invoice. Only approved Service Purchase Orders are allowed.');
        }

        try {
            $invoice = $this->purchaseInvoiceCopyService->copyFromServicePurchaseOrder($po);

            return redirect()->route('purchase-invoices.show', $invoice->id)
                ->with('success', 'Purchase Invoice created from Service Purchase Order successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating Purchase Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show copy to GRPO form
     */
    public function showCopyToGRPO($id)
    {
        $po = PurchaseOrder::with(['lines.inventoryItem', 'businessPartner'])->findOrFail($id);

        if (!$this->grpoCopyService->canCopyToGRPO($po)) {
            return back()->with('error', 'Purchase Order cannot be copied to GRPO. Only approved Item Purchase Orders are allowed.');
        }

        $availableLines = $this->grpoCopyService->getAvailableLines($po);

        return view('purchase_orders.copy_to_grpo', compact('po', 'availableLines'));
    }

    /**
     * Show copy to Purchase Invoice form
     */
    public function showCopyToPurchaseInvoice($id)
    {
        $po = PurchaseOrder::with(['lines.inventoryItem', 'businessPartner'])->findOrFail($id);

        if (!$this->purchaseInvoiceCopyService->canCopyToPurchaseInvoice($po)) {
            return back()->with('error', 'Purchase Order cannot be copied to Purchase Invoice. Only approved Service Purchase Orders are allowed.');
        }

        $poSummary = $this->purchaseInvoiceCopyService->getPurchaseOrderSummary($po);

        return view('purchase_orders.copy_to_purchase_invoice', compact('po', 'poSummary'));
    }

    /**
     * Get available lines for copying (AJAX)
     */
    public function getAvailableLines($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if (!$this->grpoCopyService->canCopyToGRPO($po)) {
            return response()->json(['error' => 'Purchase Order cannot be copied to GRPO'], 400);
        }

        $availableLines = $this->grpoCopyService->getAvailableLines($po);

        return response()->json(['lines' => $availableLines]);
    }
}
