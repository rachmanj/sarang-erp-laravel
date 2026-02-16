<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceiptPO;
use App\Models\GoodsReceiptPOLine;
use App\Models\PurchaseOrder;
use App\Models\InventoryItem;
use App\Services\DocumentNumberingService;
use App\Services\GRPOCopyService;
use App\Services\DocumentClosureService;
use App\Services\GRPOJournalService;
use App\Services\Accounting\PostingService;
use App\Services\PurchaseWorkflowAuditService;
use App\Services\CompanyEntityService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptPOController extends Controller
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService,
        private GRPOCopyService $grpoCopyService,
        private DocumentClosureService $documentClosureService,
        private GRPOJournalService $grpoJournalService,
        private PostingService $postingService,
        private CompanyEntityService $companyEntityService,
        private InventoryService $inventoryService
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
        $categories = \App\Models\ProductCategory::with('parent')->active()->orderBy('name')->get();
        $warehouses = DB::table('warehouses')->where('is_active', 1)->where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();
        // Don't load POs initially - will be loaded via AJAX based on vendor selection
        return view('goods_receipt_pos.create', compact('vendors', 'accounts', 'taxCodes', 'categories', 'warehouses', 'entities', 'defaultEntity'));
    }

    public function getDocumentNumber(Request $request)
    {
        $entityId = $request->input('company_entity_id');
        $date = $request->input('date', now()->toDateString());

        try {
            if (!$entityId) {
                return response()->json(['error' => 'Company entity is required'], 400);
            }

            $documentNumber = $this->documentNumberingService->previewNumber('goods_receipt', $date, [
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
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
        ]);

        $purchaseOrder = null;
        if (!empty($data['purchase_order_id'])) {
            $purchaseOrder = PurchaseOrder::select('id', 'company_entity_id')->find($data['purchase_order_id']);
        }
        $entityId = $this->companyEntityService->resolveEntityId($request->input('company_entity_id'), $purchaseOrder);

        return DB::transaction(function () use ($data, $entityId, $purchaseOrder) {
            $grpo = GoodsReceiptPO::create([
                'grn_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'company_entity_id' => $entityId,
                'warehouse_id' => $data['warehouse_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'received', // Automatically mark as received when saved
                'total_amount' => 0,
            ]);
            $grpoNo = $this->documentNumberingService->generateNumber('goods_receipt', $data['date'], [
                'company_entity_id' => $entityId,
            ]);
            $grpo->update(['grn_no' => $grpoNo]);
            $totalAmount = 0;
            foreach ($data['lines'] as $l) {
                // Get item details to set unit_price
                $item = InventoryItem::find($l['item_id']);
                $unitPrice = $item ? $item->purchase_price : 0;
                $amount = $unitPrice * (float)$l['qty'];
                $totalAmount += $amount;

                GoodsReceiptPOLine::create([
                    'grpo_id' => $grpo->id,
                    'item_id' => $l['item_id'],
                    'account_id' => 0, // Set a default account_id to avoid the error
                    'description' => $l['description'] ?? null,
                    'qty' => (float)$l['qty'],
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                ]);
            }

            // Update total amount
            $grpo->update(['total_amount' => $totalAmount]);

            // Create inventory transactions for each line
            foreach ($data['lines'] as $l) {
                $item = InventoryItem::find($l['item_id']);
                if ($item) {
                    try {
                        $description = $l['description'] ?? $item->name;
                        $this->inventoryService->processPurchaseTransaction(
                            $l['item_id'],
                            (float)$l['qty'],
                            $item->purchase_price,
                            'goods_receipt_po',
                            $grpo->id,
                            "GRPO {$grpoNo}: {$description}",
                            $data['warehouse_id']
                        );
                    } catch (\Exception $e) {
                        \Log::error('Failed to create inventory transaction for GRPO', [
                            'grpo_id' => $grpo->id,
                            'item_id' => $l['item_id'],
                            'error' => $e->getMessage()
                        ]);
                        throw new \Exception("Failed to create inventory transaction: " . $e->getMessage());
                    }
                }
            }

            // Log GRPO creation in Purchase Order audit trail
            if ($purchaseOrder) {
                app(PurchaseWorkflowAuditService::class)->logGRPOCreation($purchaseOrder, $grpo->id);
            }

            // Automatically create and post journal entries since goods are received
            try {
                $journal = $this->grpoJournalService->createJournalEntries($grpo);

                // Post the journal using PostingService
                $journalPayload = [
                    'date' => $journal->date,
                    'description' => $journal->description,
                    'source_type' => $journal->source_type,
                    'source_id' => $journal->source_id,
                    'posted_by' => Auth::id(),
                    'lines' => $journal->lines->map(function ($line) {
                        return [
                            'account_id' => $line->account_id,
                            'description' => $line->description,
                            'debit' => $line->debit,
                            'credit' => $line->credit,
                            'project_id' => $line->project_id,
                            'dept_id' => $line->dept_id,
                        ];
                    })->toArray()
                ];

                $this->postingService->postJournal($journalPayload);

                return redirect()->route('goods-receipt-pos.index')->with('success', 'Goods Receipt PO created, goods received, and journal entries posted');
            } catch (\Exception $e) {
                return redirect()->route('goods-receipt-pos.index')->with('error', 'Goods Receipt PO created but journal posting failed: ' . $e->getMessage());
            }
        });
    }

    public function show(int $id)
    {
        $grpo = GoodsReceiptPO::with(['lines.item', 'businessPartner', 'warehouse'])->findOrFail($id);

        // Check if inventory transactions exist for this GRPO
        $hasInventoryTransactions = \App\Models\InventoryTransaction::where('reference_type', 'goods_receipt_po')
            ->where('reference_id', $grpo->id)
            ->exists();

        return view('goods_receipt_pos.show', compact('grpo', 'hasInventoryTransactions'));
    }

    public function receive(int $id)
    {
        $grpo = GoodsReceiptPO::with(['lines.item', 'businessPartner'])->findOrFail($id);

        if ($grpo->status === 'received') {
            return back()->with('success', 'Already received');
        }

        try {
            DB::transaction(function () use ($grpo) {
                // Update status to received
                $grpo->update(['status' => 'received']);

                // Create inventory transactions for each line if not already created
                foreach ($grpo->lines as $line) {
                    $item = $line->item;
                    if ($item) {
                        // Check if inventory transaction already exists for this line
                        $existingTransaction = \App\Models\InventoryTransaction::where('reference_type', 'goods_receipt_po')
                            ->where('reference_id', $grpo->id)
                            ->where('item_id', $line->item_id)
                            ->first();

                        if (!$existingTransaction) {
                            try {
                                $transactionDescription = $line->description ?? $item->name;
                                $this->inventoryService->processPurchaseTransaction(
                                    $line->item_id,
                                    (float)$line->qty,
                                    $line->unit_price ?? $item->purchase_price,
                                    'goods_receipt_po',
                                    $grpo->id,
                                    "GRPO {$grpo->grn_no}: {$transactionDescription}",
                                    $grpo->warehouse_id
                                );
                            } catch (\Exception $e) {
                                \Log::error('Failed to create inventory transaction for GRPO receive', [
                                    'grpo_id' => $grpo->id,
                                    'item_id' => $line->item_id,
                                    'error' => $e->getMessage()
                                ]);
                                throw new \Exception("Failed to create inventory transaction: " . $e->getMessage());
                            }
                        }
                    }
                }

                // Create journal entries automatically
                $journal = $this->grpoJournalService->createJournalEntries($grpo);
            });

            return back()->with('success', 'Goods Receipt PO marked as received and journal entries created');
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing GRPO: ' . $e->getMessage());
        }
    }

    public function createInvoice(int $id)
    {
        $grpo = GoodsReceiptPO::with('lines.item')->findOrFail($id);
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('name')->get();
        $departments = DB::table('departments')->orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $entities = app(\App\Services\CompanyEntityService::class)->getActiveEntities();
        $defaultEntity = app(\App\Services\CompanyEntityService::class)->getDefaultEntity();

        $showAccounts = auth()->user()->can('accounts.view');
        $cashAccounts = DB::table('accounts')
            ->where('code', 'LIKE', '1.1.1%')
            ->where('code', '!=', '1.1.1')
            ->where('is_postable', 1)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $prefill = [
            'date' => now()->toDateString(),
            'business_partner_id' => $grpo->business_partner_id,
            'company_entity_id' => $grpo->company_entity_id,
            'description' => 'From GRPO ' . ($grpo->grn_no ?: ('#' . $grpo->id)),
            'lines' => $grpo->lines->map(function ($l) {
                return [
                    'account_id' => (int)$l->account_id,
                    'inventory_item_id' => $l->item_id,
                    'warehouse_id' => $grpo->warehouse_id,
                    'description' => $l->description,
                    'qty' => (float)$l->qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $l->tax_code_id,
                ];
            })->toArray(),
        ];
        return view('purchase_invoices.create', compact('accounts', 'vendors', 'taxCodes', 'projects', 'departments', 'warehouses', 'entities', 'defaultEntity', 'showAccounts', 'cashAccounts') + ['prefill' => $prefill, 'goods_receipt_id' => $grpo->id]);
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
                // Calculate pending_qty on the fly: qty - received_qty
                return $po->lines->filter(function ($line) {
                    $pendingQty = $line->qty - $line->received_qty;
                    return $pendingQty > 0;
                })->count() > 0;
            })
            ->map(function ($po) {
                // Calculate remaining lines count on the fly
                $remainingLines = $po->lines->filter(function ($line) {
                    $pendingQty = $line->qty - $line->received_qty;
                    return $pendingQty > 0;
                });

                return [
                    'id' => $po->id,
                    'order_no' => $po->order_no,
                    'date' => $po->date->format('Y-m-d'),
                    'total_amount' => $po->total_amount,
                    'remaining_lines_count' => $remainingLines->count(),
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
            ->filter(function ($line) {
                // Calculate pending_qty on the fly: qty - received_qty
                $pendingQty = $line->qty - $line->received_qty;
                return $pendingQty > 0;
            })
            ->map(function ($line) {
                $itemCode = $line->item_code ?: ($line->inventoryItem->code ?? '');
                $itemName = $line->item_name ?: ($line->inventoryItem->name ?? '');

                // Calculate pending_qty on the fly: qty - received_qty
                $pendingQty = $line->qty - $line->received_qty;

                return [
                    'id' => $line->id,
                    'item_id' => $line->inventory_item_id,
                    'item_display' => $itemCode && $itemName ? "{$itemCode} - {$itemName}" : '',
                    'item_code' => $itemCode,
                    'item_name' => $itemName,
                    'description' => $line->description,
                    'qty' => $pendingQty, // Use calculated remaining quantity
                    'unit_price' => $line->unit_price,
                ];
            });

        return response()->json(['lines' => $remainingLines]);
    }

    /**
     * Create journal entries for GRPO manually
     */
    public function createJournal(int $id)
    {
        $grpo = GoodsReceiptPO::with('lines.item')->findOrFail($id);

        if (!$grpo->canBeJournalized()) {
            return back()->with('error', 'GRPO cannot be journalized. Status must be "received" and not already journalized.');
        }

        try {
            $journal = $this->grpoJournalService->createJournalEntries($grpo);
            return back()->with('success', "Journal entries created successfully. Journal #{$journal->journal_no}");
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating journal entries: ' . $e->getMessage());
        }
    }

    /**
     * Reverse journal entries for GRPO
     */
    public function reverseJournal(int $id)
    {
        $grpo = GoodsReceiptPO::findOrFail($id);

        if (!$grpo->isJournalized()) {
            return back()->with('error', 'GRPO has not been journalized yet.');
        }

        try {
            $reversalJournal = $this->grpoJournalService->reverseJournalEntries($grpo);
            return back()->with('success', "Journal entries reversed successfully. Reversal Journal #{$reversalJournal->journal_no}");
        } catch (\Exception $e) {
            return back()->with('error', 'Error reversing journal entries: ' . $e->getMessage());
        }
    }

    /**
     * Show journal entries for GRPO
     */
    public function showJournal(int $id)
    {
        $grpo = GoodsReceiptPO::with(['lines.item', 'journal.lines.account'])->findOrFail($id);

        if (!$grpo->isJournalized()) {
            return back()->with('error', 'GRPO has not been journalized yet.');
        }

        $journalEntries = $this->grpoJournalService->getJournalEntries($grpo);

        return view('goods_receipt_pos.journal', compact('grpo', 'journalEntries'));
    }

    /**
     * Fix missing inventory transactions for a GRPO
     */
    public function fixInventoryTransactions(int $id)
    {
        $grpo = GoodsReceiptPO::with(['lines.item'])->findOrFail($id);

        if ($grpo->status !== 'received') {
            return back()->with('error', 'GRPO must be in "received" status to create inventory transactions.');
        }

        try {
            $createdCount = DB::transaction(function () use ($grpo) {
                $count = 0;
                foreach ($grpo->lines as $line) {
                    $item = $line->item;
                    if (!$item) {
                        continue;
                    }

                    // Check if inventory transaction already exists for this line
                    $existingTransaction = \App\Models\InventoryTransaction::where('reference_type', 'goods_receipt_po')
                        ->where('reference_id', $grpo->id)
                        ->where('item_id', $line->item_id)
                        ->first();

                    if (!$existingTransaction) {
                        try {
                            $transactionDescription = $line->description ?? $item->name;
                            $this->inventoryService->processPurchaseTransaction(
                                $line->item_id,
                                (float)$line->qty,
                                $line->unit_price ?? $item->purchase_price,
                                'goods_receipt_po',
                                $grpo->id,
                                "GRPO {$grpo->grn_no}: {$transactionDescription}",
                                $grpo->warehouse_id
                            );
                            $count++;
                        } catch (\Exception $e) {
                            \Log::error('Failed to create inventory transaction for GRPO fix', [
                                'grpo_id' => $grpo->id,
                                'item_id' => $line->item_id,
                                'error' => $e->getMessage()
                            ]);
                            throw new \Exception("Failed to create inventory transaction for item {$item->name}: " . $e->getMessage());
                        }
                    }
                }
                return $count;
            });

            if ($createdCount > 0) {
                return back()->with('success', "Created {$createdCount} inventory transaction(s) for GRPO {$grpo->grn_no}");
            } else {
                return back()->with('info', 'All inventory transactions already exist for this GRPO');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error fixing inventory transactions: ' . $e->getMessage());
        }
    }
}
