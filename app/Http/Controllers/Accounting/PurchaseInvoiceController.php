<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\GoodsReceiptPO;
use App\Models\PurchaseOrder;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use App\Services\CompanyEntityService;
use App\Services\PurchaseWorkflowAuditService;
use App\Services\PurchaseInvoiceService;
use App\Services\UnitConversionService;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private CompanyEntityService $companyEntityService,
        private PurchaseInvoiceService $purchaseInvoiceService,
        private UnitConversionService $unitConversionService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ap.invoices.create')->only(['create', 'store']);
        $this->middleware('permission:ap.invoices.post')->only(['post']);
    }

    public function index()
    {
        return view('purchase_invoices.index');
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

    public function store(Request $request)
    {
        $isAccountingUser = auth()->user()->can('accounts.view');

        $validationRules = [
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'payment_method' => ['required', 'in:credit,cash'],
            'is_direct_purchase' => ['nullable', 'boolean'],
            'is_opening_balance' => ['nullable', 'boolean'],
            'cash_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'lines.*.order_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
        ];

        // For accounting users: allow manual account selection OR inventory item
        if ($isAccountingUser) {
            $validationRules['lines.*.account_id'] = ['nullable', 'integer', 'exists:accounts,id'];
            $validationRules['lines.*.inventory_item_id'] = ['nullable', 'integer', 'exists:inventory_items,id'];
        } else {
            // For non-accounting users: require inventory item (account will be auto-selected)
            $validationRules['lines.*.inventory_item_id'] = ['required', 'integer', 'exists:inventory_items,id'];
        }

        $data = $request->validate($validationRules);

        $purchaseOrder = $request->input('purchase_order_id')
            ? PurchaseOrder::select('id', 'company_entity_id')->find($request->input('purchase_order_id'))
            : null;
        $goodsReceipt = $request->input('goods_receipt_id')
            ? GoodsReceiptPO::select('id', 'company_entity_id')->find($request->input('goods_receipt_id'))
            : null;
        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            $goodsReceipt ?? $purchaseOrder
        );

        return DB::transaction(function () use ($data, $request, $purchaseOrder, $goodsReceipt, $entity) {
            // Log the data being used to create the invoice
            \Log::info('Creating Purchase Invoice with data:', [
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $request->input('goods_receipt_id'),
                'description' => $data['description'] ?? null
            ]);

            \Log::info('Creating Purchase Invoice with data:', [
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'] ?? null,
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $request->input('goods_receipt_id'),
                'description' => $data['description'] ?? null
            ]);

            // Make sure business_partner_id is set
            if (!isset($data['business_partner_id'])) {
                \Log::error('Missing business_partner_id in request data', $data);
                throw new \Exception('Business partner is required');
            }

            // Auto-set is_direct_purchase: Cash payment without PO/GRPO = Direct Purchase
            $isDirectPurchase = false;
            if (
                $data['payment_method'] === 'cash' &&
                !$request->input('purchase_order_id') &&
                !$request->input('goods_receipt_id')
            ) {
                $isDirectPurchase = true;
            } else {
                // Allow manual override via checkbox (for edge cases like credit direct purchase)
                $isDirectPurchase = $request->boolean('is_direct_purchase', false);
            }

            // Create invoice data array with all required fields
            $invoiceData = [
                'invoice_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'purchase_order_id' => $request->input('purchase_order_id'),
                'goods_receipt_id' => $request->input('goods_receipt_id'),
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
                'company_entity_id' => $entity->id,
                'payment_method' => $data['payment_method'],
                'is_direct_purchase' => $isDirectPurchase,
                'is_opening_balance' => $request->boolean('is_opening_balance', false),
                'cash_account_id' => $request->input('cash_account_id'),
            ];

            \Log::info('Creating invoice with data:', $invoiceData);

            $invoice = PurchaseInvoice::create($invoiceData);

            $invoiceNo = $this->documentNumberingService->generateNumber('purchase_invoice', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $invoice->update(['invoice_no' => $invoiceNo]);

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;

                // Auto-select account from inventory item if not provided
                $accountId = $l['account_id'] ?? null;
                $inventoryItemId = $l['inventory_item_id'] ?? null;

                if (!$accountId && !empty($inventoryItemId)) {
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
                        throw new \Exception("Line item error: " . $e->getMessage());
                    }
                }

                if (!$accountId) {
                    throw new \Exception('Account is required. Please select account or inventory item.');
                }

                // Handle UOM conversion if unit is selected
                $baseQuantity = (float) $l['qty'];
                $conversionFactor = 1.0;

                if (!empty($l['order_unit_id']) && !empty($inventoryItemId)) {
                    try {
                        $processedLine = $this->unitConversionService->processOrderLine($l, $inventoryItemId);
                        $baseQuantity = $processedLine['base_quantity'] ?? $baseQuantity;
                        $conversionFactor = $processedLine['unit_conversion_factor'] ?? 1.0;
                    } catch (\Exception $e) {
                        \Log::warning('Unit conversion failed for line', [
                            'line' => $l,
                            'error' => $e->getMessage()
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
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'project_id' => $l['project_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'] . ' +' . $termsDays . ' days')) : null;
            $invoice->update(['total_amount' => $total, 'terms_days' => $termsDays ?: null, 'due_date' => $dueDate]);

            // Log invoice creation in Purchase Order audit trail
            if ($purchaseOrder) {
                app(PurchaseWorkflowAuditService::class)->logPurchaseInvoiceCreation($purchaseOrder, $invoice->id);
            }

            // Attempt to close related documents if this PI was created from GRPO
            if ($goodsReceipt) {
                try {
                    $this->documentClosureService->closeGoodsReceipt($goodsReceipt->id, $invoice->id, auth()->id());
                } catch (\Exception $closureException) {
                    // Log closure failure but don't fail the PI creation
                    \Log::warning('Failed to close Goods Receipt after PI creation', [
                        'grpo_id' => $request->input('goods_receipt_id'),
                        'pi_id' => $invoice->id,
                        'error' => $closureException->getMessage()
                    ]);
                }
            }

            return redirect()->route('purchase-invoices.show', $invoice->id)->with('success', 'Purchase invoice created');
        });
    }

    public function show(int $id)
    {
        $invoice = PurchaseInvoice::with([
            'lines.inventoryItem',
            'lines.warehouse',
            'lines.orderUnit',
            'paymentAllocations'
        ])->findOrFail($id);
        return view('purchase_invoices.show', compact('invoice'));
    }

    public function edit(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines.inventoryItem', 'lines.warehouse', 'lines.orderUnit'])->findOrFail($id);

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
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'payment_method' => ['required', 'in:credit,cash'],
            'is_direct_purchase' => ['nullable', 'boolean'],
            'is_opening_balance' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'lines.*.order_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
        ];

        // For accounting users: allow manual account selection OR inventory item
        if ($isAccountingUser) {
            $validationRules['lines.*.account_id'] = ['nullable', 'integer', 'exists:accounts,id'];
            $validationRules['lines.*.inventory_item_id'] = ['nullable', 'integer', 'exists:inventory_items,id'];
        } else {
            // For non-accounting users: require inventory item (account will be auto-selected)
            $validationRules['lines.*.inventory_item_id'] = ['required', 'integer', 'exists:inventory_items,id'];
        }

        $data = $request->validate($validationRules);

        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            null
        );

        return DB::transaction(function () use ($data, $request, $invoice, $entity, $isAccountingUser) {
            // Auto-set is_direct_purchase: Cash payment without PO/GRPO = Direct Purchase
            $isDirectPurchase = false;
            if (
                $data['payment_method'] === 'cash' &&
                !$invoice->purchase_order_id &&
                !$invoice->goods_receipt_id
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
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;

                // Auto-select account from inventory item if not provided
                $accountId = $l['account_id'] ?? null;
                $inventoryItemId = $l['inventory_item_id'] ?? null;

                if (!$accountId && !empty($inventoryItemId)) {
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
                        throw new \Exception("Line item error: " . $e->getMessage());
                    }
                }

                if (!$accountId) {
                    throw new \Exception('Account is required. Please select account or inventory item.');
                }

                // Handle UOM conversion if unit is selected
                $baseQuantity = (float) $l['qty'];
                $conversionFactor = 1.0;

                if (!empty($l['order_unit_id']) && !empty($inventoryItemId)) {
                    try {
                        $processedLine = $this->unitConversionService->processOrderLine($l, $inventoryItemId);
                        $baseQuantity = $processedLine['base_quantity'] ?? $baseQuantity;
                        $conversionFactor = $processedLine['unit_conversion_factor'] ?? 1.0;
                    } catch (\Exception $e) {
                        \Log::warning('Unit conversion failed for line', [
                            'line' => $l,
                            'error' => $e->getMessage()
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
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'project_id' => $l['project_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'] . ' +' . $termsDays . ' days')) : null;
            $invoice->update(['total_amount' => $total, 'terms_days' => $termsDays ?: null, 'due_date' => $dueDate]);

            return redirect()->route('purchase-invoices.show', $invoice->id)->with('success', 'Purchase invoice updated');
        });
    }

    public function post(int $id)
    {
        try {
            $invoice = PurchaseInvoice::with(['lines.inventoryItem', 'lines.warehouse'])->findOrFail($id);

            if ($invoice->status === 'posted') {
                return back()->with('success', 'Already posted');
            }

            DB::transaction(function () use ($invoice) {
                // Create inventory transactions for direct purchases (but NOT for opening balance invoices)
                if ($invoice->is_direct_purchase && !$invoice->is_opening_balance) {
                    foreach ($invoice->lines as $line) {
                        if ($line->inventory_item_id) {
                            try {
                                $this->purchaseInvoiceService->createInventoryTransaction($line, $invoice);
                            } catch (\Exception $e) {
                                \Log::error('Failed to create inventory transaction for Purchase Invoice', [
                                    'invoice_id' => $invoice->id,
                                    'line_id' => $line->id,
                                    'error' => $e->getMessage()
                                ]);
                                throw new \Exception("Failed to create inventory transaction: " . $e->getMessage());
                            }
                        }
                    }
                }

                // Different accounting flow based on payment method
                if ($invoice->payment_method === 'cash' && $invoice->is_direct_purchase) {
                    // Direct cash purchase: Debit Inventory, Credit Cash
                    $this->postDirectCashPurchase($invoice);
                } else {
                    // Credit purchase: Debit AP UnInvoice, Credit Utang Dagang (existing flow)
                    $this->postCreditPurchase($invoice);
                }

                $invoice->update(['status' => 'posted', 'posted_at' => now()]);
            });

            return back()->with('success', 'Purchase invoice posted successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to post Purchase Invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to post invoice: ' . $e->getMessage());
        }
    }

    /**
     * Unpost a posted Purchase Invoice
     * Reverses journal entries and removes inventory transactions
     */
    public function unpost(int $id)
    {
        $invoice = PurchaseInvoice::with(['lines', 'paymentAllocations'])->findOrFail($id);

        if (!$invoice->canBeUnposted()) {
            $reasons = [];
            if ($invoice->status !== 'posted') {
                $reasons[] = 'Invoice is not posted';
            }
            if ($invoice->total_allocated > 0) {
                $reasons[] = 'Invoice has payment allocations (Rp ' . number_format($invoice->total_allocated, 2) . ')';
            }
            if ($invoice->closure_status === 'closed') {
                $reasons[] = 'Invoice has been closed';
            }

            return back()->with('error', 'Cannot unpost invoice: ' . implode(', ', $reasons));
        }

        return DB::transaction(function () use ($invoice) {
            // Find and reverse journal entries
            $journals = DB::table('journals')
                ->where('source_type', 'purchase_invoice')
                ->where('source_id', $invoice->id)
                ->get();

            foreach ($journals as $journal) {
                try {
                    $this->posting->reverseJournal(
                        $journal->id,
                        now()->toDateString(),
                        auth()->id()
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to reverse journal for Purchase Invoice', [
                        'invoice_id' => $invoice->id,
                        'journal_id' => $journal->id,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("Failed to reverse journal entries: " . $e->getMessage());
                }
            }

            // Delete inventory transactions if any (for direct purchases, but not opening balance)
            if ($invoice->is_direct_purchase && !$invoice->is_opening_balance) {
                $inventoryTransactions = $invoice->inventoryTransactions;
                $itemsToUpdate = [];

                foreach ($inventoryTransactions as $transaction) {
                    try {
                        // Track items that need valuation updates
                        if (!in_array($transaction->item_id, $itemsToUpdate)) {
                            $itemsToUpdate[] = $transaction->item_id;
                        }

                        // Reverse inventory transaction by creating opposite transaction
                        \App\Models\InventoryTransaction::create([
                            'item_id' => $transaction->item_id,
                            'transaction_type' => 'adjustment',
                            'quantity' => -$transaction->quantity, // Negative to reverse
                            'unit_cost' => $transaction->unit_cost,
                            'total_cost' => -$transaction->total_cost,
                            'reference_type' => 'purchase_invoice',
                            'reference_id' => $invoice->id,
                            'transaction_date' => now()->toDateString(),
                            'notes' => 'Reversal of purchase invoice #' . $invoice->invoice_no,
                            'warehouse_id' => $transaction->warehouse_id,
                            'created_by' => auth()->id(),
                        ]);

                        // Delete original transaction
                        $transaction->delete();
                    } catch (\Exception $e) {
                        \Log::error('Failed to reverse inventory transaction for Purchase Invoice', [
                            'invoice_id' => $invoice->id,
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage()
                        ]);
                        // Continue even if inventory reversal fails
                    }
                }

                // Update valuations for affected items after reversing transactions
                foreach ($itemsToUpdate as $itemId) {
                    try {
                        $item = \App\Models\InventoryItem::find($itemId);
                        if ($item) {
                            app(\App\Services\InventoryService::class)->updateItemValuation($item);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to update valuation after unposting Purchase Invoice', [
                            'invoice_id' => $invoice->id,
                            'item_id' => $itemId,
                            'error' => $e->getMessage()
                        ]);
                        // Continue even if valuation update fails
                    }
                }
            }

            // Update invoice status back to draft
            $invoice->update([
                'status' => 'draft',
                'posted_at' => null,
            ]);

            return back()->with('success', 'Purchase invoice unposted successfully. Journal entries have been reversed.');
        });
    }

    /**
     * Post direct cash purchase invoice
     * Accounting: Debit Inventory Account, Credit Cash Account
     */
    private function postDirectCashPurchase(PurchaseInvoice $invoice): void
    {
        // Use selected cash account, or fallback to default (Kas di Tangan)
        $cashAccountId = $invoice->cash_account_id;
        if (!$cashAccountId) {
            $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'); // Default: Kas di Tangan
        }
        $ppnInputId = (int) DB::table('accounts')->where('code', '1.1.6')->value('id');

        $totalAmount = 0.0;
        $ppnTotal = 0.0;
        $withholdingTotal = 0.0;
        $journalLines = [];

        foreach ($invoice->lines as $line) {
            $lineAmount = (float) $line->amount;
            $totalAmount += $lineAmount;

            // Calculate taxes
            if (!empty($line->tax_code_id)) {
                $tax = DB::table('tax_codes')->where('id', $line->tax_code_id)->first();
                if ($tax) {
                    $rate = (float) $tax->rate;
                    if (str_contains(strtolower((string)$tax->name), 'ppn') || strtolower((string)$tax->type) === 'ppn_input') {
                        $ppnTotal += round($lineAmount * $rate, 2);
                    }
                    if (strtolower((string)$tax->type) === 'withholding') {
                        $withholdingTotal += round($lineAmount * $rate, 2);
                    }
                }
            }

            // Debit Inventory Account (from line's account_id - auto-selected from item category)
            $journalLines[] = [
                'account_id' => $line->account_id,
                'debit' => $lineAmount,
                'credit' => 0,
                'project_id' => $line->project_id,
                'dept_id' => $line->dept_id,
                'memo' => $line->description ?? 'Direct cash purchase',
            ];
        }

        // PPN Input (if any)
        if ($ppnTotal > 0) {
            $journalLines[] = [
                'account_id' => $ppnInputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Masukan',
            ];
        }

        // Withholding Tax Payable (if any)
        if ($withholdingTotal > 0) {
            $withholdingPayableId = (int) DB::table('accounts')->where('code', '2.1.3')->value('id');
            if ($withholdingPayableId) {
                $journalLines[] = [
                    'account_id' => $withholdingPayableId,
                    'debit' => 0,
                    'credit' => $withholdingTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'Withholding Tax Payable',
                ];
            }
        }

        // Credit Cash Account
        $totalCashCredit = ($totalAmount + $ppnTotal) - $withholdingTotal;
        $journalLines[] = [
            'account_id' => $cashAccountId,
            'debit' => 0,
            'credit' => $totalCashCredit,
            'project_id' => null,
            'dept_id' => null,
            'memo' => 'Cash payment for purchase invoice #' . $invoice->invoice_no,
        ];

        $this->posting->postJournal([
            'date' => $invoice->date->toDateString(),
            'description' => 'Direct Cash Purchase Invoice #' . $invoice->invoice_no,
            'source_type' => 'purchase_invoice',
            'source_id' => $invoice->id,
            'lines' => $journalLines,
        ]);
    }

    /**
     * Post credit purchase invoice (existing flow)
     * Accounting: Debit AP UnInvoice, Credit Utang Dagang
     */
    private function postCreditPurchase(PurchaseInvoice $invoice): void
    {
        $apUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '2.1.1.03')->value('id'); // AP UnInvoice
        $apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id'); // Utang Dagang
        $ppnInputId = (int) DB::table('accounts')->where('code', '1.1.6')->value('id');

        $expenseTotal = 0.0;
        $ppnTotal = 0.0;
        $withholdingTotal = 0.0;
        $lines = [];

        foreach ($invoice->lines as $l) {
            $expenseTotal += (float) $l->amount;
            if (!empty($l->tax_code_id)) {
                $tax = DB::table('tax_codes')->where('id', $l->tax_code_id)->first();
                if ($tax) {
                    $rate = (float) $tax->rate;
                    if (str_contains(strtolower((string)$tax->name), 'ppn') || strtolower((string)$tax->type) === 'ppn_input') {
                        $ppnTotal += round($l->amount * $rate, 2);
                    }
                    if (strtolower((string)$tax->type) === 'withholding') {
                        $withholdingTotal += round($l->amount * $rate, 2);
                    }
                }
            }
        }

        if ($ppnTotal > 0) {
            $lines[] = [
                'account_id' => $ppnInputId,
                'debit' => $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'PPN Masukan',
            ];
        }

        if ($withholdingTotal > 0) {
            $withholdingPayableId = (int) DB::table('accounts')->where('code', '2.1.3')->value('id');
            if ($withholdingPayableId) {
                $lines[] = [
                    'account_id' => $withholdingPayableId,
                    'debit' => 0,
                    'credit' => $withholdingTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'Withholding Tax Payable',
                ];
            }
        }

        // Debit AP UnInvoice (reducing un-invoiced liability)
        $lines[] = [
            'account_id' => $apUnInvoiceAccountId,
            'debit' => ($expenseTotal + $ppnTotal) - $withholdingTotal,
            'credit' => 0,
            'project_id' => null,
            'dept_id' => null,
            'memo' => 'Reduce AP UnInvoice',
        ];

        // Credit Utang Dagang (creating proper liability)
        $lines[] = [
            'account_id' => $apAccountId,
            'debit' => 0,
            'credit' => ($expenseTotal + $ppnTotal) - $withholdingTotal,
            'project_id' => null,
            'dept_id' => null,
            'memo' => 'Accounts Payable',
        ];

        $this->posting->postJournal([
            'date' => $invoice->date->toDateString(),
            'description' => 'Post AP Invoice #' . $invoice->invoice_no,
            'source_type' => 'purchase_invoice',
            'source_id' => $invoice->id,
            'lines' => $lines,
        ]);
    }

    public function print(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        return view('purchase_invoices.print', compact('invoice'));
    }

    public function pdf(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('purchase_invoices.print', [
            'invoice' => $invoice,
        ]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="purchase-invoice-' . $id . '.pdf"'
        ]);
    }

    public function queuePdf(int $id)
    {
        $invoice = PurchaseInvoice::with('lines')->findOrFail($id);
        $path = 'public/pdfs/purchase-invoice-' . $invoice->id . '.pdf';
        \App\Jobs\GeneratePdfJob::dispatch('purchase_invoices.print', ['invoice' => $invoice], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);
        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }

    public function data(Request $request)
    {
        $q = DB::table('purchase_invoices as pi')
            ->leftJoin('business_partners as v', 'v.id', '=', 'pi.business_partner_id')
            ->select('pi.id', 'pi.date', 'pi.invoice_no', 'pi.business_partner_id', 'v.name as vendor_name', 'pi.total_amount', 'pi.status');

        if ($request->filled('status')) {
            $q->where('pi.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('pi.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('pi.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('pi.invoice_no', 'like', '%' . $kw . '%')
                    ->orWhere('pi.description', 'like', '%' . $kw . '%')
                    ->orWhere('v.name', 'like', '%' . $kw . '%');
            });
        }

        return DataTables::of($q)
            ->editColumn('total_amount', function ($row) {
                return number_format((float)$row->total_amount, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper($row->status);
            })
            ->addColumn('vendor', function ($row) {
                return $row->vendor_name ?: ('#' . $row->business_partner_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('purchase-invoices.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
