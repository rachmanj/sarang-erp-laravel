<?php

namespace App\Http\Controllers\Accounting;

use App\Exports\PurchaseInvoiceListExport;
use App\Http\Controllers\Concerns\HandlesDocumentDeletion;
use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\GoodsReceiptPO;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalBuilders\PurchaseInvoiceJournalBuilder;
use App\Services\Accounting\PostingService;
use App\Services\Accounting\PurchaseDocumentHeaderDiscountApplier;
use App\Services\Accounting\PurchaseInvoiceFooterMath;
use App\Services\Accounting\PurchaseInvoiceUnpostService;
use App\Services\CompanyEntityService;
use App\Services\DocumentClosureService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentRelationshipService;
use App\Services\Documents\DocumentType;
use App\Services\PurchaseInvoiceGrpoAggregationService;
use App\Services\PurchaseInvoiceService;
use App\Services\PurchaseWorkflowAuditService;
use App\Services\TaxService;
use App\Services\UnitConversionService;
use App\Support\DocumentOpenState;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PurchaseInvoiceController extends Controller
{
    use HandlesDocumentDeletion;

    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private CompanyEntityService $companyEntityService,
        private PurchaseInvoiceService $purchaseInvoiceService,
        private UnitConversionService $unitConversionService,
        private DocumentRelationshipService $documentRelationshipService,
        private PurchaseInvoiceGrpoAggregationService $purchaseInvoiceGrpoAggregationService,
        private PurchaseDocumentHeaderDiscountApplier $purchaseDocumentHeaderDiscountApplier,
        private PurchaseInvoiceJournalBuilder $purchaseInvoiceJournalBuilder,
        private PurchaseInvoiceUnpostService $purchaseInvoiceUnpostService,
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ap.invoices.create')->only(['create', 'store', 'availableGrposForSupplier', 'prefillFromGrpos']);
        $this->middleware('permission:ap.invoices.post')->only(['post', 'unpost']);
        $this->middleware('permission:ap.invoices.delete')->only(['destroy', 'deletePreview']);
    }

    public function index()
    {
        $ptCahaya = \App\Models\CompanyEntity::where('code', '71')->first();
        $cvCahaya = \App\Models\CompanyEntity::where('code', '72')->first();

        return view('purchase_invoices.index', compact('ptCahaya', 'cvCahaya'));
    }

    public function create()
    {
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        $warehouses = Warehouse::where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        // Only show accounts to accounting users
        $accounts = null;
        $showAccounts = auth()->user()->can('accounts.view');
        if ($showAccounts) {
            $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        }

        // Load cash accounts (for direct cash purchases)
        $cashAccounts = DB::table('accounts')
            ->where('code', 'LIKE', '1.1.1%')
            ->where('code', '!=', '1.1.1') // Exclude parent account
            ->where('is_postable', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('purchase_invoices.create', compact(
            'accounts',
            'vendors',
            'taxCodes',
            'projects',
            'departments',
            'warehouses',
            'entities',
            'defaultEntity',
            'showAccounts',
            'cashAccounts'
        ));
    }

    public function getDocumentNumber(Request $request)
    {
        $entityId = $request->input('company_entity_id');
        $date = $request->input('date', now()->toDateString());

        try {
            if (! $entityId) {
                return response()->json(['error' => 'Company entity is required'], 400);
            }

            $documentNumber = $this->documentNumberingService->previewNumber('purchase_invoice', $date, [
                'company_entity_id' => $entityId,
            ]);

            return response()->json(['document_number' => $documentNumber]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating document number: '.$e->getMessage()], 500);
        }
    }

    public function availableGrposForSupplier(Request $request)
    {
        $data = $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['nullable', 'integer', 'exists:company_entities,id'],
        ]);

        $entityFilter = isset($data['company_entity_id']) ? (int) $data['company_entity_id'] : null;

        $rows = $this->purchaseInvoiceGrpoAggregationService->getAvailableGrposForSupplier(
            (int) $data['business_partner_id'],
            $entityFilter
        );

        return response()->json([
            'data' => $rows->map(fn ($g) => [
                'id' => $g->id,
                'grn_no' => $g->grn_no,
                'date' => $g->date?->toDateString(),
                'total_amount' => (float) $g->total_amount,
                'status' => $g->status,
                'company_entity_id' => $g->company_entity_id,
                'entity_code' => $g->relationLoaded('companyEntity') ? $g->companyEntity?->code : null,
                'entity_name' => $g->relationLoaded('companyEntity') ? $g->companyEntity?->name : null,
            ])->values()->all(),
        ]);
    }

    public function prefillFromGrpos(Request $request)
    {
        $request->validate([
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'grpo_ids' => ['required', 'array', 'min:1'],
            'grpo_ids.*' => ['integer', 'exists:goods_receipt_po,id'],
        ]);

        $grpos = $this->purchaseInvoiceGrpoAggregationService->assertGrposCanBeMergedIntoInvoice(
            array_map('intval', $request->input('grpo_ids')),
            (int) $request->input('business_partner_id'),
            null
        );

        $prefill = $this->purchaseInvoiceGrpoAggregationService->buildPrefillFromGrpos($grpos);

        return response()->json(['prefill' => $prefill]);
    }

    public function store(Request $request)
    {
        $isAccountingUser = auth()->user()->can('accounts.view');

        $validationRules = [
            'date' => $this->purchaseInvoiceDateRules($request),
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'payment_method' => ['required', 'in:credit,cash'],
            'is_direct_purchase' => ['nullable', 'boolean'],
            'is_opening_balance' => ['nullable', 'boolean'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'goods_receipt_ids' => ['nullable', 'array'],
            'goods_receipt_ids.*' => ['integer', 'exists:goods_receipt_po,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'lines.*.order_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.wtax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        // For accounting users: allow manual account selection OR inventory item
        if ($isAccountingUser) {
            $validationRules['lines.*.account_id'] = ['nullable', 'integer', 'exists:accounts,id'];
            $validationRules['lines.*.inventory_item_id'] = ['nullable', 'integer', 'exists:inventory_items,id'];
        } else {
            // For non-accounting users: require inventory item (account will be auto-selected)
            $validationRules['lines.*.inventory_item_id'] = ['required', 'integer', 'exists:inventory_items,id'];
        }

        $data = $request->validate($validationRules, [
            'date.before_or_equal' => 'The invoice date cannot be later than today unless this is marked as opening balance or you have permission for future-dated invoices.',
        ]);

        $mergedGrpoIds = $this->purchaseInvoiceGrpoAggregationService->normalizeGrpoIdsFromRequest(
            $request->filled('goods_receipt_id') ? (int) $request->input('goods_receipt_id') : null,
            $request->input('goods_receipt_ids', [])
        );

        if ($request->filled('purchase_order_id') && $mergedGrpoIds !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'goods_receipt_ids' => ['You cannot combine a purchase order reference with goods receipt (GRPO) selection.'],
            ]);
        }

        if ($mergedGrpoIds !== []) {
            $this->purchaseInvoiceGrpoAggregationService->assertGrposCanBeMergedIntoInvoice(
                $mergedGrpoIds,
                (int) $data['business_partner_id'],
                (int) $data['company_entity_id']
            );
        }

        $purchaseOrder = $request->input('purchase_order_id')
            ? PurchaseOrder::select('id', 'company_entity_id')->find($request->input('purchase_order_id'))
            : null;
        $representativeGrpo = $mergedGrpoIds !== []
            ? GoodsReceiptPO::select('id', 'company_entity_id')->whereKey($mergedGrpoIds)->orderBy('id')->first()
            : null;
        $goodsReceiptSingle = $representativeGrpo
            ?? (
                $request->input('goods_receipt_id')
                    ? GoodsReceiptPO::select('id', 'company_entity_id')->find($request->input('goods_receipt_id'))
                    : null
            );

        $storedGoodsReceiptColumn = count($mergedGrpoIds) === 1 ? $mergedGrpoIds[0] : null;

        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            $goodsReceiptSingle ?? $purchaseOrder
        );

        return DB::transaction(function () use ($data, $request, $purchaseOrder, $goodsReceiptSingle, $entity, $mergedGrpoIds, $storedGoodsReceiptColumn) {
            // Log the data being used to create the invoice
            \Log::info('Creating Purchase Invoice with data:', [
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_ids' => $mergedGrpoIds,
                'description' => $data['description'] ?? null,
            ]);

            \Log::info('Creating Purchase Invoice with data:', [
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'] ?? null,
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_ids' => $mergedGrpoIds,
                'description' => $data['description'] ?? null,
            ]);

            // Make sure business_partner_id is set
            if (! isset($data['business_partner_id'])) {
                \Log::error('Missing business_partner_id in request data', $data);
                throw new \Exception('Business partner is required');
            }

            // Auto-set is_direct_purchase: Cash payment without PO/GRPO = Direct Purchase
            $isDirectPurchase = false;
            if (
                $data['payment_method'] === 'cash' &&
                ! $request->input('purchase_order_id') &&
                $mergedGrpoIds === []
            ) {
                $isDirectPurchase = true;
            } else {
                // Allow manual override via checkbox (for edge cases like credit direct purchase)
                $isDirectPurchase = $request->boolean('is_direct_purchase', false);
            }

            $currencyId = (int) (DB::table('business_partners')
                ->where('id', $data['business_partner_id'])
                ->value('default_currency_id') ?? DB::table('currencies')->orderBy('id')->value('id'));
            if ($currencyId <= 0) {
                throw new \RuntimeException('No currency is configured; cannot create purchase invoice.');
            }

            // Create invoice data array with all required fields
            $invoiceData = [
                'invoice_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $storedGoodsReceiptColumn,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
                'total_amount_foreign' => 0,
                'company_entity_id' => $entity->id,
                'payment_method' => $data['payment_method'],
                'is_direct_purchase' => $isDirectPurchase,
                'is_opening_balance' => $request->boolean('is_opening_balance', false),
                'cash_account_id' => $request->input('cash_account_id'),
                'created_by' => Auth::id(),
            ];

            \Log::info('Creating invoice with data:', $invoiceData);

            $invoice = PurchaseInvoice::create($invoiceData);

            $invoiceNo = $this->documentNumberingService->generateNumber('purchase_invoice', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $invoice->update(['invoice_no' => $invoiceNo]);

            $total = 0;
            $totalLineDiscountAmount = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $lineDiscountAmount = (float) ($l['discount_amount'] ?? 0);
                $lineDiscountPct = (float) ($l['discount_percentage'] ?? 0);
                if ($lineDiscountPct > 0 && $lineDiscountAmount == 0) {
                    $lineDiscountAmount = ($amount * $lineDiscountPct) / 100;
                } elseif ($lineDiscountAmount > 0 && $lineDiscountPct == 0) {
                    $lineDiscountPct = $amount > 0 ? ($lineDiscountAmount / $amount) * 100 : 0;
                }
                $netAmount = $amount - $lineDiscountAmount;
                $totalLineDiscountAmount += $lineDiscountAmount;
                $vatAmount = $this->calculateVatAmount($netAmount, $l['tax_code_id'] ?? null);
                $amountAfterVat = $netAmount + $vatAmount;
                $total += $amountAfterVat;

                // Auto-select account from inventory item if not provided
                $accountId = $l['account_id'] ?? null;
                $inventoryItemId = $l['inventory_item_id'] ?? null;

                if (! $accountId && ! empty($inventoryItemId)) {
                    try {
                        $item = InventoryItem::find($inventoryItemId);
                        if ($item) {
                            $accountId = $this->purchaseInvoiceService->getAccountIdForItem($item);

                            // Validate warehouse for inventory items
                            if ($item->item_type !== 'service') {
                                $this->purchaseInvoiceService->validateWarehouseForItem(
                                    $inventoryItemId,
                                    $l['warehouse_id'] ?? null
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        throw new \Exception('Line item error: '.$e->getMessage());
                    }
                }

                if (! $accountId) {
                    throw new \Exception('Account is required. Please select account or inventory item.');
                }

                // Handle UOM conversion if unit is selected
                $baseQuantity = (float) $l['qty'];
                $conversionFactor = 1.0;

                if (! empty($l['order_unit_id']) && ! empty($inventoryItemId)) {
                    try {
                        $processedLine = $this->unitConversionService->processOrderLine($l, $inventoryItemId);
                        $baseQuantity = $processedLine['base_quantity'] ?? $baseQuantity;
                        $conversionFactor = $processedLine['unit_conversion_factor'] ?? 1.0;
                    } catch (\Exception $e) {
                        \Log::warning('Unit conversion failed for line', [
                            'line' => $l,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with original values if conversion fails
                    }
                }

                PurchaseInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'inventory_item_id' => $inventoryItemId,
                    'warehouse_id' => $l['warehouse_id'] ?? null,
                    'account_id' => $accountId,
                    'description' => $l['description'] ?? null,
                    'qty' => (float) $l['qty'],
                    'order_unit_id' => $l['order_unit_id'] ?? null,
                    'base_quantity' => $baseQuantity,
                    'unit_conversion_factor' => $conversionFactor,
                    'unit_price' => (float) $l['unit_price'],
                    'amount' => $amount,
                    'discount_amount' => $lineDiscountAmount,
                    'discount_percentage' => $lineDiscountPct,
                    'net_amount' => $netAmount,
                    'vat_amount' => $vatAmount,
                    'amount_after_vat' => $amountAfterVat,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'wtax_rate' => (float) ($l['wtax_rate'] ?? 0),
                    'project_id' => $l['project_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $subtotal = $total;
            $headerDiscountAmount = (float) ($request->input('discount_amount') ?? 0);
            $headerDiscountPct = (float) ($request->input('discount_percentage') ?? 0);
            if ($headerDiscountPct > 0 && $headerDiscountAmount == 0) {
                $headerDiscountAmount = ($subtotal * $headerDiscountPct) / 100;
            } elseif ($headerDiscountAmount > 0 && $headerDiscountPct == 0) {
                $headerDiscountPct = $subtotal > 0 ? ($headerDiscountAmount / $subtotal) * 100 : 0;
            }
            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'].' +'.$termsDays.' days')) : null;
            $invoice->update([
                'discount_amount' => $totalLineDiscountAmount + $headerDiscountAmount,
                'discount_percentage' => $headerDiscountPct,
                'terms_days' => $termsDays ?: null,
                'due_date' => $dueDate,
            ]);

            $invoice->load(['lines' => fn ($q) => $q->orderBy('id')]);
            $this->purchaseDocumentHeaderDiscountApplier->recalculatePurchaseInvoiceLines($invoice);

            $payableTotal = round((float) $invoice->lines()->sum('amount_after_vat'), 2);
            $invoice->update(['total_amount' => $payableTotal]);

            $this->purchaseInvoiceGrpoAggregationService->persistInvoiceGrpoPivot($invoice, $mergedGrpoIds);

            // Log invoice creation in Purchase Order audit trail
            if ($purchaseOrder) {
                app(PurchaseWorkflowAuditService::class)->logPurchaseInvoiceCreation($purchaseOrder, $invoice->id);
            }

            // Attempt to close GRPO only for single-receipt linkage (combined GRPO invoicing skips auto-closure).
            if (count($mergedGrpoIds) === 1 && $goodsReceiptSingle) {
                try {
                    $this->documentClosureService->closeGoodsReceipt($goodsReceiptSingle->id, $invoice->id, auth()->id());
                } catch (\Exception $closureException) {
                    // Log closure failure but don't fail the PI creation
                    \Log::warning('Failed to close Goods Receipt after PI creation', [
                        'grpo_id' => $goodsReceiptSingle->id,
                        'pi_id' => $invoice->id,
                        'error' => $closureException->getMessage(),
                    ]);
                }
            }

            $this->documentRelationshipService->syncPurchaseInvoiceRelationships($invoice);

            return redirect()->route('purchase-invoices.show', $invoice->id)->with('success', 'Purchase invoice created');
        });
    }

    public function show(int $id)
    {
        $invoice = PurchaseInvoice::with([
            'lines.inventoryItem',
            'lines.warehouse',
            'lines.orderUnit',
            'lines.taxCode',
            'paymentAllocations.payment',
            'businessPartner.primaryAddress',
            'companyEntity',
            'journal',
            'inventoryTransactions.item',
            'inventoryTransactions.warehouse',
            'grpos',
        ])->findOrFail($id);

        $invoiceFooter = PurchaseInvoiceFooterMath::invoiceFooterTotals($invoice);

        $totalAllocated = $invoice->paymentAllocations->sum('amount');
        $remainingBalance = $invoiceFooter['amount_due'] - $totalAllocated;

        /** @var User|null $user */
        $user = Auth::user();
        $canCreatePayment = $user instanceof User
            && $user->can('ap.payments.create')
            && $invoice->status === 'posted'
            && $remainingBalance > 0.01
            && $invoice->payment_method !== 'cash'
            && ! $invoice->is_direct_purchase;

        // Get related documents
        $purchaseOrder = null;
        if ($invoice->purchase_order_id) {
            $purchaseOrder = DB::table('purchase_orders')->find($invoice->purchase_order_id);
        }

        $linkedGoodsReceiptPos = collect();
        if ($invoice->linkedGoodsReceiptPoIds() !== []) {
            $linkedGoodsReceiptPos = GoodsReceiptPO::query()
                ->whereIn('id', $invoice->linkedGoodsReceiptPoIds())
                ->orderBy('id')
                ->get(['id', 'grn_no', 'date', 'total_amount', 'status']);
        }

        // Get cash account info if direct purchase
        $cashAccount = null;
        if ($invoice->cash_account_id) {
            $cashAccount = DB::table('accounts')->find($invoice->cash_account_id);
        }

        return view('purchase_invoices.show', compact(
            'invoice',
            'invoiceFooter',
            'totalAllocated',
            'remainingBalance',
            'canCreatePayment',
            'purchaseOrder',
            'linkedGoodsReceiptPos',
            'cashAccount'
        ));
    }

    public function edit(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines.inventoryItem', 'lines.warehouse', 'lines.orderUnit', 'grpos'])->findOrFail($id);

        // Only allow editing of draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->route('purchase-invoices.show', $id)
                ->with('error', 'Only draft purchase invoices can be edited.');
        }

        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        $warehouses = Warehouse::where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        // Only show accounts to accounting users
        $accounts = null;
        $showAccounts = auth()->user()->can('accounts.view');
        if ($showAccounts) {
            $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        }

        // Load cash accounts (for direct cash purchases)
        $cashAccounts = DB::table('accounts')
            ->where('code', 'LIKE', '1.1.1%')
            ->where('code', '!=', '1.1.1') // Exclude parent account
            ->where('is_postable', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('purchase_invoices.edit', compact(
            'invoice',
            'accounts',
            'vendors',
            'taxCodes',
            'projects',
            'departments',
            'warehouses',
            'entities',
            'defaultEntity',
            'showAccounts',
            'cashAccounts'
        ));
    }

    public function update(Request $request, int $id)
    {
        $invoice = PurchaseInvoice::findOrFail($id);

        // Only allow updating draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->route('purchase-invoices.show', $id)
                ->with('error', 'Only draft purchase invoices can be edited.');
        }

        $isAccountingUser = auth()->user()->can('accounts.view');

        $validationRules = [
            'date' => $this->purchaseInvoiceDateRules($request),
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'payment_method' => ['required', 'in:credit,cash'],
            'is_direct_purchase' => ['nullable', 'boolean'],
            'is_opening_balance' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'lines.*.order_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.wtax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        // For accounting users: allow manual account selection OR inventory item
        if ($isAccountingUser) {
            $validationRules['lines.*.account_id'] = ['nullable', 'integer', 'exists:accounts,id'];
            $validationRules['lines.*.inventory_item_id'] = ['nullable', 'integer', 'exists:inventory_items,id'];
        } else {
            // For non-accounting users: require inventory item (account will be auto-selected)
            $validationRules['lines.*.inventory_item_id'] = ['required', 'integer', 'exists:inventory_items,id'];
        }

        $data = $request->validate($validationRules, [
            'date.before_or_equal' => 'The invoice date cannot be later than today unless this is marked as opening balance or you have permission for future-dated invoices.',
        ]);

        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            null
        );

        return DB::transaction(function () use ($data, $request, $invoice, $entity) {
            // Auto-set is_direct_purchase: Cash payment without PO/GRPO = Direct Purchase
            $isDirectPurchase = false;
            if (
                $data['payment_method'] === 'cash' &&
                ! $invoice->purchase_order_id &&
                ! $invoice->isLinkedToGoodsReceiptPo()
            ) {
                $isDirectPurchase = true;
            } else {
                // Allow manual override via checkbox (for edge cases like credit direct purchase)
                $isDirectPurchase = $request->boolean('is_direct_purchase', false);
            }

            // Update invoice header
            $invoice->update([
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'company_entity_id' => $entity->id,
                'description' => $data['description'] ?? null,
                'payment_method' => $data['payment_method'],
                'is_direct_purchase' => $isDirectPurchase,
                'is_opening_balance' => $request->boolean('is_opening_balance', false),
                'cash_account_id' => $request->input('cash_account_id'),
            ]);

            // Delete existing lines
            $invoice->lines()->delete();

            // Create new lines
            $total = 0;
            $totalLineDiscountAmount = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $lineDiscountAmount = (float) ($l['discount_amount'] ?? 0);
                $lineDiscountPct = (float) ($l['discount_percentage'] ?? 0);
                if ($lineDiscountPct > 0 && $lineDiscountAmount == 0) {
                    $lineDiscountAmount = ($amount * $lineDiscountPct) / 100;
                } elseif ($lineDiscountAmount > 0 && $lineDiscountPct == 0) {
                    $lineDiscountPct = $amount > 0 ? ($lineDiscountAmount / $amount) * 100 : 0;
                }
                $netAmount = $amount - $lineDiscountAmount;
                $totalLineDiscountAmount += $lineDiscountAmount;
                $vatAmount = $this->calculateVatAmount($netAmount, $l['tax_code_id'] ?? null);
                $amountAfterVat = $netAmount + $vatAmount;
                $total += $amountAfterVat;

                // Auto-select account from inventory item if not provided
                $accountId = $l['account_id'] ?? null;
                $inventoryItemId = $l['inventory_item_id'] ?? null;

                if (! $accountId && ! empty($inventoryItemId)) {
                    try {
                        $item = InventoryItem::find($inventoryItemId);
                        if ($item) {
                            $accountId = $this->purchaseInvoiceService->getAccountIdForItem($item);

                            // Validate warehouse for inventory items
                            if ($item->item_type !== 'service') {
                                $this->purchaseInvoiceService->validateWarehouseForItem(
                                    $inventoryItemId,
                                    $l['warehouse_id'] ?? null
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        throw new \Exception('Line item error: '.$e->getMessage());
                    }
                }

                if (! $accountId) {
                    throw new \Exception('Account is required. Please select account or inventory item.');
                }

                // Handle UOM conversion if unit is selected
                $baseQuantity = (float) $l['qty'];
                $conversionFactor = 1.0;

                if (! empty($l['order_unit_id']) && ! empty($inventoryItemId)) {
                    try {
                        $processedLine = $this->unitConversionService->processOrderLine($l, $inventoryItemId);
                        $baseQuantity = $processedLine['base_quantity'] ?? $baseQuantity;
                        $conversionFactor = $processedLine['unit_conversion_factor'] ?? 1.0;
                    } catch (\Exception $e) {
                        \Log::warning('Unit conversion failed for line', [
                            'line' => $l,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue with original values if conversion fails
                    }
                }

                PurchaseInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'inventory_item_id' => $inventoryItemId,
                    'warehouse_id' => $l['warehouse_id'] ?? null,
                    'account_id' => $accountId,
                    'description' => $l['description'] ?? null,
                    'qty' => (float) $l['qty'],
                    'order_unit_id' => $l['order_unit_id'] ?? null,
                    'base_quantity' => $baseQuantity,
                    'unit_conversion_factor' => $conversionFactor,
                    'unit_price' => (float) $l['unit_price'],
                    'amount' => $amount,
                    'discount_amount' => $lineDiscountAmount,
                    'discount_percentage' => $lineDiscountPct,
                    'net_amount' => $netAmount,
                    'vat_amount' => $vatAmount,
                    'amount_after_vat' => $amountAfterVat,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'wtax_rate' => (float) ($l['wtax_rate'] ?? 0),
                    'project_id' => $l['project_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $subtotal = $total;
            $headerDiscountAmount = (float) ($request->input('discount_amount') ?? 0);
            $headerDiscountPct = (float) ($request->input('discount_percentage') ?? 0);
            if ($headerDiscountPct > 0 && $headerDiscountAmount == 0) {
                $headerDiscountAmount = ($subtotal * $headerDiscountPct) / 100;
            } elseif ($headerDiscountAmount > 0 && $headerDiscountPct == 0) {
                $headerDiscountPct = $subtotal > 0 ? ($headerDiscountAmount / $subtotal) * 100 : 0;
            }
            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'].' +'.$termsDays.' days')) : null;
            $invoice->update([
                'discount_amount' => $totalLineDiscountAmount + $headerDiscountAmount,
                'discount_percentage' => $headerDiscountPct,
                'terms_days' => $termsDays ?: null,
                'due_date' => $dueDate,
            ]);

            $invoice->load(['lines' => fn ($q) => $q->orderBy('id')]);
            $this->purchaseDocumentHeaderDiscountApplier->recalculatePurchaseInvoiceLines($invoice);

            $payableTotal = round((float) $invoice->lines()->sum('amount_after_vat'), 2);
            $invoice->update(['total_amount' => $payableTotal]);

            return redirect()->route('purchase-invoices.show', $invoice->id)->with('success', 'Purchase invoice updated');
        });
    }

    public function post(int $id)
    {
        try {
            $outcome = DB::transaction(function () use ($id) {
                $invoice = PurchaseInvoice::with(['lines.inventoryItem', 'lines.warehouse'])
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($invoice->status === 'posted') {
                    return 'already_posted';
                }

                // Create inventory transactions for direct purchases (but NOT for opening balance invoices)
                if ($invoice->is_direct_purchase && ! $invoice->is_opening_balance) {
                    foreach ($invoice->lines as $line) {
                        if ($line->inventory_item_id) {
                            try {
                                $this->purchaseInvoiceService->createInventoryTransaction($line, $invoice);
                            } catch (\Exception $e) {
                                \Log::error('Failed to create inventory transaction for Purchase Invoice', [
                                    'invoice_id' => $invoice->id,
                                    'line_id' => $line->id,
                                    'error' => $e->getMessage(),
                                ]);
                                throw new \Exception('Failed to create inventory transaction: '.$e->getMessage());
                            }
                        }
                    }
                }

                // Different accounting flow based on payment method and opening balance flag
                if ($invoice->payment_method === 'cash' && $invoice->is_direct_purchase) {
                    // Direct cash purchase: Debit Inventory, Credit Cash
                    $this->postDirectCashPurchase($invoice);
                } else {
                    // Credit purchase: Debit AP UnInvoice, Credit Utang Dagang (existing flow)
                    // OR for opening balance: Debit Line Accounts, Credit Utang Dagang
                    $this->postCreditPurchase($invoice);
                }

                $invoice->update(['status' => 'posted', 'posted_at' => now()]);

                app(TaxService::class)->syncPostedPurchaseInvoice($invoice->fresh(['lines', 'businessPartner']));

                return 'posted';
            });

            if ($outcome === 'already_posted') {
                return back()->with('success', 'Already posted');
            }

            return back()->with('success', 'Purchase invoice posted successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to post Purchase Invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }
    }

    /**
     * Unpost a posted Purchase Invoice
     * Reverses journal entries and removes inventory transactions
     */
    public function unpost(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines', 'paymentAllocations'])->findOrFail($id);

        if (! $invoice->canBeUnposted()) {
            $reasons = [];
            if ($invoice->status !== 'posted') {
                $reasons[] = 'Invoice is not posted';
            }
            if ($invoice->total_allocated > 0) {
                $reasons[] = 'Invoice has payment allocations (Rp '.number_format($invoice->total_allocated, 2).')';
            }
            if ($invoice->closure_status === 'closed') {
                $reasons[] = 'Invoice has been closed';
            }

            return back()->with('error', 'Cannot unpost invoice: '.implode(', ', $reasons));
        }

        return DB::transaction(function () use ($invoice) {
            $this->purchaseInvoiceUnpostService->unpost($invoice);

            return back()->with('success', 'Purchase invoice unposted successfully. Journal entries have been reversed.');
        });
    }

    protected function documentDeletionType(): string
    {
        return DocumentType::PURCHASE_INVOICE;
    }

    public function destroy(int $id)
    {
        return $this->destroyDocument($id);
    }

    /**
     * Calculate VAT amount from tax code
     */
    private function calculateVatAmount(float $amount, ?int $taxCodeId): float
    {
        if (! $taxCodeId) {
            return 0.0;
        }

        $tax = DB::table('tax_codes')->where('id', $taxCodeId)->first();
        if (! $tax) {
            return 0.0;
        }

        $rate = (float) $tax->rate;
        // Check if this is a VAT/PPN tax code
        if (str_contains(strtolower((string) $tax->name), 'ppn') || strtolower((string) $tax->type) === 'ppn_input') {
            return round($amount * ($rate / 100), 2);
        }

        return 0.0;
    }

    /**
     * Post direct cash purchase invoice
     * Normal flow: Debit Inventory Account, Credit Cash Account
     * Opening balance flow: Debit Line Accounts, Credit Cash Account
     */
    private function postDirectCashPurchase(PurchaseInvoice $invoice): void
    {
        $draft = $this->purchaseInvoiceJournalBuilder->build($invoice);

        $this->posting->postJournal([
            'date' => $draft->date ?? $invoice->date->toDateString(),
            'description' => $draft->description,
            'source_type' => 'purchase_invoice',
            'source_id' => $invoice->id,
            'lines' => $draft->lines,
        ]);
    }

    /**
     * Post credit purchase invoice
     * Normal flow: Debit AP UnInvoice, Credit Utang Dagang
     * Opening balance flow: Debit Line Accounts, Credit Utang Dagang
     */
    private function postCreditPurchase(PurchaseInvoice $invoice): void
    {
        $draft = $this->purchaseInvoiceJournalBuilder->build($invoice);

        $this->posting->postJournal([
            'date' => $draft->date ?? $invoice->date->toDateString(),
            'description' => $draft->description,
            'source_type' => 'purchase_invoice',
            'source_id' => $invoice->id,
            'lines' => $draft->lines,
        ]);
    }

    public function print(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines.taxCode', 'businessPartner'])->findOrFail($id);
        $invoiceFooter = PurchaseInvoiceFooterMath::invoiceFooterTotals($invoice);

        return view('purchase_invoices.print', compact('invoice', 'invoiceFooter'));
    }

    public function pdf(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines.taxCode', 'businessPartner'])->findOrFail($id);
        $invoiceFooter = PurchaseInvoiceFooterMath::invoiceFooterTotals($invoice);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('purchase_invoices.print', [
            'invoice' => $invoice,
            'invoiceFooter' => $invoiceFooter,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="purchase-invoice-'.$id.'.pdf"',
        ]);
    }

    public function queuePdf(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines.taxCode'])->findOrFail($id);
        $path = 'public/pdfs/purchase-invoice-'.$invoice->id.'.pdf';
        $invoiceFooter = PurchaseInvoiceFooterMath::invoiceFooterTotals($invoice);
        \App\Jobs\GeneratePdfJob::dispatch('purchase_invoices.print', [
            'invoice' => $invoice,
            'invoiceFooter' => $invoiceFooter,
        ], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);

        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }

    public function data(Request $request)
    {
        $q = $this->buildPurchaseInvoiceListQuery();
        $this->applyPurchaseInvoiceListFilters($q, $request);

        $totalsRow = DB::query()->fromSub($q->clone(), 'inv')
            ->selectRaw('COALESCE(SUM(inv.total_amount), 0) as sum_total_amount')
            ->selectRaw('COALESCE(SUM(inv.total_amount_after_vat), 0) as sum_amount_after_vat')
            ->first();

        return DataTables::of($q)
            ->with('sum_total_amount', (float) $totalsRow->sum_total_amount)
            ->with('sum_amount_after_vat', (float) $totalsRow->sum_amount_after_vat)
            ->editColumn('date', function ($row) {
                return $this->formatPurchaseInvoiceListDate($row->date);
            })
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->editColumn('total_vat', function ($row) {
                return number_format((float) $row->total_vat, 2);
            })
            ->editColumn('total_amount_after_vat', function ($row) {
                return number_format((float) $row->total_amount_after_vat, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper($row->status);
            })
            ->addColumn('vendor', function ($row) {
                return $row->vendor_name ?: ('#'.$row->business_partner_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('purchase-invoices.show', $row->id);

                return '<a href="'.$url.'" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function export(Request $request)
    {
        $q = $this->buildPurchaseInvoiceListQuery();
        $this->applyPurchaseInvoiceListFilters($q, $request);

        $totalsRow = DB::query()->fromSub($q->clone(), 'inv')
            ->selectRaw('COALESCE(SUM(inv.total_amount), 0) as sum_total_amount')
            ->selectRaw('COALESCE(SUM(inv.total_vat), 0) as sum_vat')
            ->selectRaw('COALESCE(SUM(inv.total_amount_after_vat), 0) as sum_amount_after_vat')
            ->first();

        $rows = $q->clone()->orderByDesc('pi.date')->orderByDesc('pi.id')->get();

        $sheet = [
            ['Date', 'Invoice No', 'Vendor', 'Total', 'VAT', 'Amount After VAT', 'Status'],
        ];

        foreach ($rows as $row) {
            $sheet[] = [
                $this->formatPurchaseInvoiceListDate($row->date),
                $row->invoice_no,
                $row->vendor_name ?: ('#'.$row->business_partner_id),
                round((float) $row->total_amount, 2),
                round((float) $row->total_vat, 2),
                round((float) $row->total_amount_after_vat, 2),
                strtoupper((string) $row->status),
            ];
        }

        $sheet[] = [];
        $sheet[] = [
            '',
            '',
            'Totals (filtered)',
            round((float) $totalsRow->sum_total_amount, 2),
            round((float) $totalsRow->sum_vat, 2),
            round((float) $totalsRow->sum_amount_after_vat, 2),
            '',
        ];

        $filename = 'purchase-invoices-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new PurchaseInvoiceListExport($sheet), $filename);
    }

    private function buildPurchaseInvoiceListQuery(): Builder
    {
        return DB::table('purchase_invoices as pi')
            ->leftJoin('business_partners as v', 'v.id', '=', 'pi.business_partner_id')
            ->leftJoin('purchase_invoice_lines as pil', 'pil.invoice_id', '=', 'pi.id')
            ->select(
                'pi.id',
                'pi.date',
                'pi.invoice_no',
                'pi.business_partner_id',
                'v.name as vendor_name',
                'pi.total_amount',
                'pi.status',
                DB::raw('COALESCE(SUM(pil.vat_amount), 0) as total_vat'),
                DB::raw('CASE 
                    WHEN COALESCE(SUM(pil.amount_after_vat), 0) = 0 AND COALESCE(SUM(pil.amount), 0) > 0 
                    THEN SUM(pil.amount)
                    WHEN COALESCE(SUM(pil.amount_after_vat), 0) > 0 
                    THEN SUM(pil.amount_after_vat)
                    ELSE pi.total_amount
                END as total_amount_after_vat')
            )
            ->groupBy('pi.id', 'pi.date', 'pi.invoice_no', 'pi.business_partner_id', 'v.name', 'pi.total_amount', 'pi.status');
    }

    private function formatPurchaseInvoiceListDate(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->format('d-M-Y');
    }

    private function applyPurchaseInvoiceListFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('pi.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('pi.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('pi.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $query->where(function ($w) use ($kw) {
                $w->where('pi.invoice_no', 'like', '%'.$kw.'%')
                    ->orWhere('pi.description', 'like', '%'.$kw.'%')
                    ->orWhere('v.name', 'like', '%'.$kw.'%');
            });
        }
        if ($request->filled('company_entity_id')) {
            $query->where('pi.company_entity_id', (int) $request->company_entity_id);
        }

        DocumentOpenState::applyToQuery(
            $query,
            'purchase_invoice',
            'pi',
            $request->input('open_state', DocumentOpenState::DEFAULT_STATE)
        );
    }

    /**
     * @return array<int, string>
     */
    private function purchaseInvoiceDateRules(Request $request): array
    {
        $rules = ['required', 'date'];
        $isOpeningBalance = $request->boolean('is_opening_balance', false);
        if (! $isOpeningBalance && ! auth()->user()->can('ap.invoices.future_date')) {
            $rules[] = 'before_or_equal:today';
        }

        return $rules;
    }
}
