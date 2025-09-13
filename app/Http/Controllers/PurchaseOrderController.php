<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('purchase_orders.index');
    }

    public function create()
    {
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        return view('purchase_orders.create', compact('vendors', 'accounts', 'taxCodes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
        ]);

        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'order_no' => null,
                'date' => $data['date'],
                'vendor_id' => $data['vendor_id'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
            ]);
            $ym = date('Ym', strtotime($data['date']));
            $po->update(['order_no' => sprintf('PO-%s-%06d', $ym, $po->id)]);
            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float)$l['qty'] * (float)$l['unit_price'];
                $total += $amount;
                PurchaseOrderLine::create([
                    'order_id' => $po->id,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                    'unit_price' => (float)$l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                ]);
            }
            $po->update(['total_amount' => $total]);
            return redirect()->route('purchase-orders.show', $po->id)->with('success', 'Purchase Order created');
        });
    }

    public function show(int $id)
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);
        return view('purchase_orders.show', compact('order'));
    }

    public function approve(int $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status !== 'draft') {
            return back()->with('success', 'Already approved');
        }
        $order->update(['status' => 'approved']);
        return back()->with('success', 'Purchase Order approved');
    }

    public function close(int $id)
    {
        $order = PurchaseOrder::findOrFail($id);
        if ($order->status === 'closed') {
            return back()->with('success', 'Already closed');
        }
        $order->update(['status' => 'closed']);
        return back()->with('success', 'Purchase Order closed');
    }

    public function createInvoice(int $id)
    {
        $order = PurchaseOrder::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'vendor_id' => $order->vendor_id,
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

        $order = PurchaseOrder::with(['lines.account', 'vendor'])->findOrFail($id);
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

        $order = PurchaseOrder::with(['lines.account', 'vendor'])->findOrFail($id);

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
                    'vendor_id' => $order->vendor_id,
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
}
