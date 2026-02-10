<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesOrderApproval;
use App\Models\InventoryItem;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPricingTier;
use App\Services\SalesService;
use App\Services\SalesInvoiceService;
use App\Services\DocumentClosureService;
use App\Services\DocumentNumberingService;
use App\Services\CompanyEntityService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SalesOrderController extends Controller
{
    protected $salesService;
    protected $salesInvoiceService;
    protected $documentClosureService;
    protected $currencyService;
    protected $exchangeRateService;
    protected $documentNumberingService;
    protected $companyEntityService;

    public function __construct(
        SalesService $salesService,
        SalesInvoiceService $salesInvoiceService,
        DocumentClosureService $documentClosureService,
        CurrencyService $currencyService,
        ExchangeRateService $exchangeRateService,
        DocumentNumberingService $documentNumberingService,
        CompanyEntityService $companyEntityService
    ) {
        $this->salesService = $salesService;
        $this->salesInvoiceService = $salesInvoiceService;
        $this->documentClosureService = $documentClosureService;
        $this->currencyService = $currencyService;
        $this->exchangeRateService = $exchangeRateService;
        $this->documentNumberingService = $documentNumberingService;
        $this->companyEntityService = $companyEntityService;
    }

    public function index()
    {
        return view('sales_orders.index');
    }

    public function data(Request $request)
    {
        $query = SalesOrder::with(['businessPartner'])
            ->select([
                'id',
                'date',
                'order_no',
                'business_partner_id',
                'total_amount',
                'status',
                'created_at'
            ]);

        // Apply filters
        if ($request->filled('from')) {
            $query->where('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('date', '<=', $request->to);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_no', 'like', '%' . $request->q . '%')
                    ->orWhereHas('businessPartner', function ($bp) use ($request) {
                        $bp->where('name', 'like', '%' . $request->q . '%');
                    });
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('customer', function ($row) {
                return $row->businessPartner->name ?? '';
            })
            ->addColumn('actions', function ($row) {
                $actions = '<div class="btn-group">';
                $actions .= '<a href="' . route('sales-orders.show', $row->id) . '" class="btn btn-xs btn-info">View</a>';
                if ($row->status === 'draft') {
                    $actions .= '<a href="' . route('sales-orders.edit', $row->id) . '" class="btn btn-xs btn-warning">Edit</a>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $warehouses = DB::table('warehouses')->where('is_active', 1)->where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $currencies = $this->currencyService->getActiveCurrencies();

        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();
        $soNumber = $this->documentNumberingService->generateNumber('sales_order', now()->format('Y-m-d'), [
            'company_entity_id' => $defaultEntity->id,
        ]);

        return view('sales_orders.create', compact(
            'customers',
            'accounts',
            'taxCodes',
            'inventoryItems',
            'warehouses',
            'currencies',
            'soNumber',
            'defaultEntity',
            'entities'
        ));
    }

    public function getExchangeRate(Request $request)
    {
        $currencyId = $request->input('currency_id');
        $date = $request->input('date', now()->toDateString());

        try {
            $baseCurrency = $this->currencyService->getBaseCurrency();
            if (!$baseCurrency) {
                return response()->json(['error' => 'Base currency not found'], 400);
            }

            if ($currencyId == $baseCurrency->id) {
                return response()->json(['rate' => 1.000000]);
            }

            $exchangeRate = $this->exchangeRateService->getRate($currencyId, $baseCurrency->id, $date);

            if (!$exchangeRate) {
                return response()->json(['error' => 'Exchange rate not found for the selected currency and date'], 400);
            }

            return response()->json(['rate' => $exchangeRate->rate]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error retrieving exchange rate: ' . $e->getMessage()], 500);
        }
    }

    public function getDocumentNumber(Request $request)
    {
        $entityId = $request->input('company_entity_id');
        $date = $request->input('date', now()->toDateString());

        try {
            if (!$entityId) {
                return response()->json(['error' => 'Company entity is required'], 400);
            }

            $documentNumber = $this->documentNumberingService->generateNumber('sales_order', $date, [
                'company_entity_id' => $entityId,
            ]);

            return response()->json(['document_number' => $documentNumber]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating document number: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_no' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'expected_delivery_date' => ['nullable', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'delivery_method' => ['nullable', 'string', 'max:100'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'handling_cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'order_type' => ['required', 'in:item,service'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price_foreign' => ['nullable', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.wtax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            // Calculate total amount from lines
            $totalAmount = 0;
            $totalAmountForeign = 0;
            $exchangeRate = $data['exchange_rate'];

            foreach ($data['lines'] as &$line) {
                $originalAmount = $line['qty'] * $line['unit_price'];
                $vatAmount = $originalAmount * ($line['vat_rate'] / 100);
                $wtaxAmount = $originalAmount * ($line['wtax_rate'] / 100);
                $lineAmount = $originalAmount + $vatAmount - $wtaxAmount;
                $totalAmount += $lineAmount;

                // Calculate foreign amounts
                $unitPriceForeign = $line['unit_price_foreign'] ?? $line['unit_price'];
                $lineAmountForeign = $line['qty'] * $unitPriceForeign;
                $totalAmountForeign += $lineAmountForeign;

                // Add foreign amounts to line data
                $line['amount_foreign'] = $lineAmountForeign;
            }

            $data['total_amount'] = $totalAmount;
            $data['total_amount_foreign'] = $totalAmountForeign;

            $entity = $this->companyEntityService->getEntity($request->input('company_entity_id'));
            $data['company_entity_id'] = $entity->id;

            $so = $this->salesService->createSalesOrder($data);
            return redirect()->route('sales-orders.show', $so->id)
                ->with('success', 'Sales Order created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating sales order: ' . $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        $order = SalesOrder::with(['lines.inventoryItem', 'lines.account', 'lines.taxCode', 'customer', 'companyEntity', 'warehouse', 'currency'])->findOrFail($id);

        if ($order->status !== 'draft') {
            return redirect()->route('sales-orders.show', $id)
                ->with('error', 'Sales Order can only be edited when in draft status');
        }

        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $warehouses = DB::table('warehouses')->where('is_active', 1)->where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $currencies = $this->currencyService->getActiveCurrencies();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getEntity($order->company_entity_id);

        return view('sales_orders.edit', compact(
            'order',
            'customers',
            'accounts',
            'taxCodes',
            'inventoryItems',
            'warehouses',
            'currencies',
            'entities',
            'defaultEntity'
        ));
    }

    public function update(Request $request, int $id)
    {
        $order = SalesOrder::findOrFail($id);

        if ($order->status !== 'draft') {
            return redirect()->route('sales-orders.show', $id)
                ->with('error', 'Sales Order can only be updated when in draft status');
        }

        $data = $request->validate([
            'order_no' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'expected_delivery_date' => ['nullable', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'exchange_rate' => ['required', 'numeric', 'min:0.000001'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'delivery_method' => ['nullable', 'string', 'max:100'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'handling_cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'order_type' => ['required', 'in:item,service'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price_foreign' => ['nullable', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.wtax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $this->salesService->updateSalesOrder($id, $data);
            return redirect()->route('sales-orders.show', $id)
                ->with('success', 'Sales Order updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error updating sales order: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $order = SalesOrder::with([
            'lines.inventoryItem',
            'lines.account',
            'lines.taxCode',
            'customer',
            'companyEntity',
            'warehouse',
            'currency',
            'approvals.user',
            'approvedBy',
            'createdBy',
            'commissions',
            'deliveryOrders'
        ])->findOrFail($id);
        return view('sales_orders.show', compact('order'));
    }

    public function approve(int $id, Request $request)
    {
        $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->salesService->approveSalesOrder($id, Auth::id(), $request->comments);
            return back()->with('success', 'Sales Order approved successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error approving sales order: ' . $e->getMessage());
        }
    }

    public function reject(int $id, Request $request)
    {
        $request->validate([
            'comments' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->salesService->rejectSalesOrder($id, Auth::id(), $request->comments);
            return back()->with('success', 'Sales Order rejected');
        } catch (\Exception $e) {
            return back()->with('error', 'Error rejecting sales order: ' . $e->getMessage());
        }
    }

    public function confirm(int $id)
    {
        try {
            $this->salesService->confirmSalesOrder($id);
            return back()->with('success', 'Sales Order confirmed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error confirming sales order: ' . $e->getMessage());
        }
    }

    public function deliver(int $id)
    {
        $order = SalesOrder::with('lines')->findOrFail($id);
        return view('sales_orders.deliver', compact('order'));
    }

    public function processDeliver(int $id, Request $request)
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_id' => ['required', 'integer', 'exists:sales_order_lines,id'],
            'lines.*.delivered_qty' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->salesService->deliverSalesOrder($id, $data);
            return redirect()->route('sales-orders.show', $id)
                ->with('success', 'Sales Order delivered successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error delivering sales order: ' . $e->getMessage());
        }
    }

    public function close(int $id)
    {
        try {
            $this->salesService->closeSalesOrder($id);
            return back()->with('success', 'Sales Order closed successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error closing sales order: ' . $e->getMessage());
        }
    }

    public function createInvoice(int $id)
    {
        $order = SalesOrder::with('lines')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get();
        $departments = DB::table('departments')->orderBy('name')->get();
        $prefill = [
            'date' => now()->toDateString(),
            'business_partner_id' => $order->business_partner_id,
            'description' => 'From SO ' . ($order->order_no ?: ('#' . $order->id)),
            'reference_no' => $order->reference_no ?? null,
            'lines' => $order->lines->map(function ($l) {
                return [
                    'item_code' => $l->item_code,
                    'item_name' => $l->item_name ?? $l->description,
                    'account_id' => (int)$l->account_id,
                    'description' => $l->description ?? $l->item_name,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('sales_invoices.create', compact('accounts', 'customers', 'taxCodes', 'projects', 'departments') + ['prefill' => $prefill, 'sales_order_id' => $order->id]);
    }

    public function checkCreditLimit(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->salesService->checkCreditLimit($request->business_partner_id, $request->order_amount);
            return response()->json(['status' => 'approved', 'message' => 'Credit limit check passed']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'rejected', 'message' => $e->getMessage()], 400);
        }
    }

    public function getCustomerPricingTier(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $pricingTier = CustomerPricingTier::where('business_partner_id', $request->business_partner_id)
            ->where('is_active', true)
            ->where('min_order_amount', '<=', $request->order_amount)
            ->orderBy('min_order_amount', 'desc')
            ->first();

        if ($pricingTier) {
            return response()->json([
                'tier_name' => $pricingTier->tier_name,
                'discount_percentage' => $pricingTier->discount_percentage,
                'discount_amount' => ($request->order_amount * $pricingTier->discount_percentage) / 100,
                'net_amount' => $request->order_amount - (($request->order_amount * $pricingTier->discount_percentage) / 100),
            ]);
        }

        return response()->json(['message' => 'No pricing tier applicable']);
    }

    public function analyzeCustomerProfitability(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        try {
            $analysis = $this->salesService->analyzeCustomerProfitability(
                $request->business_partner_id,
                $request->start_date,
                $request->end_date
            );
            return response()->json($analysis);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getInventoryItems()
    {
        return response()->json(
            InventoryItem::active()
                ->select('id', 'code', 'name', 'unit_of_measure', 'selling_price', 'current_stock')
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
            'selling_price' => $item->selling_price,
            'current_stock' => $item->current_stock,
            'min_stock_level' => $item->min_stock_level,
        ]);
    }

    public function getCustomerCreditInfo(int $customerId)
    {
        $creditLimit = CustomerCreditLimit::where('business_partner_id', $customerId)->first();

        if (!$creditLimit) {
            return response()->json(['message' => 'No credit limit set for this customer']);
        }

        return response()->json([
            'credit_limit' => $creditLimit->credit_limit,
            'current_balance' => $creditLimit->current_balance,
            'available_credit' => $creditLimit->available_credit,
            'credit_status' => $creditLimit->credit_status,
            'credit_utilization' => $creditLimit->credit_utilization,
        ]);
    }

    /**
     * Copy Item Sales Order to Delivery Note
     */
    public function copyToDeliveryNote(Request $request, $id)
    {
        $so = SalesOrder::with('lines.inventoryItem')->findOrFail($id);

        if (!$this->salesService->canCopyToDeliveryNote($so)) {
            return back()->with('error', 'Sales Order cannot be copied to Delivery Note. Only approved Item Sales Orders are allowed.');
        }

        $selectedLines = $request->input('selected_lines', null);

        try {
            // This would be implemented when Delivery Note functionality is added
            // For now, we'll redirect to a placeholder
            return back()->with('info', 'Delivery Note functionality will be implemented in the next phase');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating Delivery Note: ' . $e->getMessage());
        }
    }

    /**
     * Copy Service Sales Order to Sales Invoice
     */
    public function copyToSalesInvoice($id)
    {
        $so = SalesOrder::with('lines.inventoryItem')->findOrFail($id);

        if (!$this->salesService->canCopyToSalesInvoice($so)) {
            return back()->with('error', 'Sales Order cannot be copied to Sales Invoice. Only approved Service Sales Orders are allowed.');
        }

        try {
            // This would be implemented when Service Sales Order to Invoice functionality is added
            // For now, we'll redirect to a placeholder
            return back()->with('info', 'Service Sales Order to Invoice functionality will be implemented in the next phase');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating Sales Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show copy to Delivery Note form
     */
    public function showCopyToDeliveryNote($id)
    {
        $so = SalesOrder::with(['lines.inventoryItem', 'customer'])->findOrFail($id);

        if (!$this->salesService->canCopyToDeliveryNote($so)) {
            return back()->with('error', 'Sales Order cannot be copied to Delivery Note. Only approved Item Sales Orders are allowed.');
        }

        return view('sales_orders.copy_to_delivery_note', compact('so'));
    }

    /**
     * Show copy to Sales Invoice form
     */
    public function showCopyToSalesInvoice($id)
    {
        $so = SalesOrder::with(['lines.inventoryItem', 'customer'])->findOrFail($id);

        if (!$this->salesService->canCopyToSalesInvoice($so)) {
            return back()->with('error', 'Sales Order cannot be copied to Sales Invoice. Only approved Service Sales Orders are allowed.');
        }

        return view('sales_orders.copy_to_sales_invoice', compact('so'));
    }

    /**
     * Create Sales Invoice from GRPOs
     */
    public function createSalesInvoiceFromGRPOs(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'sales_order_id' => ['nullable', 'integer', 'exists:sales_orders,id'],
            'grpo_ids' => ['required', 'array', 'min:1'],
            'grpo_ids.*' => ['integer', 'exists:goods_receipts,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'fund_id' => ['nullable', 'integer', 'exists:funds,id'],
            'dept_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        try {
            $salesInvoice = $this->salesInvoiceService->createFromGoodsReceipts($data['grpo_ids'], $data);

            return redirect()->route('sales-invoices.show', $salesInvoice->id)
                ->with('success', 'Sales Invoice created from GRPOs successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating Sales Invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show create Sales Invoice from GRPOs form
     */
    public function showCreateSalesInvoiceFromGRPOs(Request $request)
    {
        $filters = $request->only(['business_partner_id', 'date_from', 'date_to', 'source_po_id']);
        $availableGRPOs = $this->salesInvoiceService->getAvailableGRPOs($filters);
        $groupedGRPOs = $this->salesInvoiceService->getGRPOsGroupedByPO($filters);

        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $salesOrders = DB::table('sales_orders')->where('status', 'approved')->orderBy('order_no')->get();
        $projects = DB::table('projects')->orderBy('name')->get();
        $funds = DB::table('funds')->orderBy('name')->get();
        $departments = DB::table('departments')->orderBy('name')->get();

        return view('sales_orders.create_invoice_from_grpos', compact(
            'availableGRPOs',
            'groupedGRPOs',
            'customers',
            'salesOrders',
            'projects',
            'funds',
            'departments'
        ));
    }

    /**
     * Validate GRPO combination (AJAX)
     */
    public function validateGRPOCombination(Request $request)
    {
        $grpoIds = $request->input('grpo_ids', []);

        if (empty($grpoIds)) {
            return response()->json(['errors' => ['No GRPOs selected']], 400);
        }

        $errors = $this->salesInvoiceService->validateGRPOCombination($grpoIds);

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 400);
        }

        return response()->json(['valid' => true]);
    }
}
