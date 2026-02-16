<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\SalesQuotation;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use App\Services\DocumentClosureService;
use App\Services\DocumentRelationshipService;
use App\Services\SalesWorkflowAuditService;
use App\Services\CompanyEntityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SalesInvoiceController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private DocumentClosureService $documentClosureService,
        private DocumentRelationshipService $documentRelationshipService,
        private CompanyEntityService $companyEntityService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ar.invoices.view')->only(['index', 'show']);
        $this->middleware('permission:ar.invoices.create')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:ar.invoices.post')->only(['post']);
        $this->middleware('permission:ar.invoices.create')->only(['destroy']);
    }

    public function index()
    {
        return view('sales_invoices.index');
    }

    public function create(Request $request)
    {
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        $prefill = null;
        $salesQuotation = null;
        $deliveryOrder = null;
        $invoicableDeliveryOrders = DeliveryOrder::whereIn('status', ['delivered', 'completed'])
            ->where(function ($q) {
                $q->whereNull('closure_status')->orWhere('closure_status', '!=', 'closed');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('delivery_order_sales_invoice')
                    ->whereColumn('delivery_order_sales_invoice.delivery_order_id', 'delivery_orders.id');
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->with('customer')
            ->get();
        $fromDo = (bool) $request->query('from_do');

        $ids = $request->input('delivery_order_id');
        $ids = is_array($ids) ? $ids : ($ids ? [$ids] : []);
        $idsParam = $request->input('delivery_order_ids');
        if (is_string($idsParam)) {
            $ids = array_merge($ids, array_map('intval', array_map('trim', explode(',', $idsParam))));
        } elseif (is_array($idsParam)) {
            $ids = array_merge($ids, $idsParam);
        }
        $deliveryOrderIds = array_filter(array_unique(array_map('intval', $ids)));
        if (!empty($deliveryOrderIds)) {
            $deliveryOrders = DeliveryOrder::with(['salesOrder', 'lines.inventoryItem.category', 'lines.account', 'lines.salesOrderLine'])
                ->whereIn('id', $deliveryOrderIds)
                ->get();

            if ($deliveryOrders->count() !== count($deliveryOrderIds)) {
                return redirect()->route('sales-invoices.create')
                    ->with('error', 'One or more delivery orders could not be found.');
            }

            foreach ($deliveryOrders as $do) {
                if (!in_array($do->status, ['delivered', 'completed'])) {
                    return redirect()->route('sales-invoices.create')
                        ->with('error', 'Only delivered or completed delivery orders can be converted. DO ' . ($do->do_number ?? '#' . $do->id) . ' is not ready.');
                }
                if (($do->closure_status ?? 'open') === 'closed') {
                    return redirect()->route('sales-invoices.create')
                        ->with('error', 'Delivery order ' . ($do->do_number ?? '#' . $do->id) . ' has already been invoiced.');
                }
            }

            $firstDo = $deliveryOrders->first();
            $customerId = $firstDo->business_partner_id;
            $companyEntityId = $firstDo->company_entity_id ?? $defaultEntity->id;
            foreach ($deliveryOrders as $do) {
                if ($do->business_partner_id != $customerId || ($do->company_entity_id ?? $defaultEntity->id) != $companyEntityId) {
                    return redirect()->route('sales-invoices.create')
                        ->with('error', 'All selected delivery orders must have the same customer and company entity.');
                }
            }

            $alreadyInvoiced = DB::table('delivery_order_sales_invoice')
                ->whereIn('delivery_order_id', $deliveryOrderIds)
                ->exists();
            if ($alreadyInvoiced) {
                $existing = DB::table('delivery_order_sales_invoice')
                    ->whereIn('delivery_order_id', $deliveryOrderIds)
                    ->first();
                $existingSi = SalesInvoice::find($existing->sales_invoice_id);
                return redirect()->route('sales-invoices.create')
                    ->with('error', 'A Sales Invoice (#' . ($existingSi?->invoice_no ?? $existing->sales_invoice_id) . ') already exists for one or more of these Delivery Orders.');
            }

            $deliveryOrder = $deliveryOrders->count() === 1 ? $deliveryOrders->first() : null;
            $prefill = $this->buildPrefillFromDeliveryOrders($deliveryOrders, $defaultEntity->id);
        } elseif ($request->has('quotation_id')) {
            $salesQuotation = SalesQuotation::with(['lines.inventoryItem.category', 'lines.account', 'businessPartner', 'companyEntity'])->findOrFail($request->quotation_id);

            $prefill = [
                'date' => now()->toDateString(),
                'business_partner_id' => $salesQuotation->business_partner_id,
                'company_entity_id' => $salesQuotation->company_entity_id ?? $defaultEntity->id,
                'description' => 'From Quotation ' . ($salesQuotation->quotation_no ?: ('#' . $salesQuotation->id)),
                'lines' => $salesQuotation->lines->map(function ($line) {
                    $accountId = (int)$line->account_id;
                    $accountDisplay = null;
                    $hasInventoryItem = !empty($line->inventory_item_id);
                    if ($hasInventoryItem && $line->inventoryItem) {
                        $salesAccount = $line->inventoryItem->getAccountByType('sales');
                        if ($salesAccount) {
                            $accountId = $salesAccount->id;
                            $accountDisplay = $salesAccount->code . ' - ' . $salesAccount->name;
                        } else {
                            $acc = $line->account;
                            $accountDisplay = $acc ? ($acc->code . ' - ' . $acc->name) : null;
                        }
                    }
                    if (!$accountDisplay && $line->account_id) {
                        $acc = \App\Models\Accounting\Account::find($line->account_id);
                        $accountDisplay = $acc ? ($acc->code . ' - ' . $acc->name) : null;
                    }
                    return [
                        'inventory_item_id' => $line->inventory_item_id,
                        'item_code' => optional($line->inventoryItem)->code ?? $line->item_code,
                        'item_name' => optional($line->inventoryItem)->name ?? $line->item_name ?? $line->description,
                        'account_id' => $accountId,
                        'account_display' => $accountDisplay,
                        'has_inventory_item' => $hasInventoryItem,
                        'description' => $line->description ?? $line->item_name,
                        'qty' => (float)$line->qty,
                        'unit_price' => (float)$line->unit_price,
                        'tax_code_id' => $line->tax_code_id ? (int)$line->tax_code_id : null,
                        'wtax_rate' => (float)($line->wtax_rate ?? 0),
                        'project_id' => null,
                        'dept_id' => null,
                    ];
                }),
            ];
        }

        $vatTaxCodes = DB::table('tax_codes')->where('type', 'ppn_output')->whereIn('rate', [11, 12])->orderBy('rate')->get(['id', 'code', 'rate']);
        return view('sales_invoices.create', compact('accounts', 'customers', 'taxCodes', 'vatTaxCodes', 'projects', 'departments', 'entities', 'defaultEntity', 'prefill', 'salesQuotation', 'deliveryOrder', 'invoicableDeliveryOrders', 'fromDo'));
    }

    public function getDocumentNumber(Request $request)
    {
        $entityId = $request->input('company_entity_id');
        $date = $request->input('date', now()->toDateString());

        try {
            if (!$entityId) {
                return response()->json(['error' => 'Company entity is required'], 400);
            }

            $documentNumber = $this->documentNumberingService->previewNumber('sales_invoice', $date, [
                'company_entity_id' => $entityId,
            ]);

            return response()->json(['document_number' => $documentNumber]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating document number: ' . $e->getMessage()], 500);
        }
    }

    private function buildPrefillFromDeliveryOrders($deliveryOrders, int $defaultEntityId): array
    {
        $deliveryOrders = collect($deliveryOrders);
        $firstDo = $deliveryOrders->first();
        $refs = $deliveryOrders->map(fn ($d) => $d->salesOrder?->reference_no)->filter()->unique()->values();
        $referenceNo = $refs->isEmpty() ? null : $refs->implode(', ');
        $doNumbers = $deliveryOrders->map(fn ($d) => $d->do_number ?: ('#' . $d->id))->implode(', ');

        $lines = collect();
        foreach ($deliveryOrders as $deliveryOrder) {
            $salesOrder = $deliveryOrder->salesOrder;
            foreach ($deliveryOrder->lines as $l) {
                $accountId = (int)$l->account_id;
                $accountDisplay = null;
                if ($l->inventory_item_id && $l->inventoryItem) {
                    $salesAccount = $l->inventoryItem->getAccountByType('sales');
                    if ($salesAccount) {
                        $accountId = $salesAccount->id;
                        $accountDisplay = $salesAccount->code . ' - ' . $salesAccount->name;
                    } else {
                        $acc = $l->account;
                        $accountDisplay = $acc ? ($acc->code . ' - ' . $acc->name) : null;
                    }
                }
                if (!$accountDisplay && $l->account_id) {
                    $acc = \App\Models\Accounting\Account::find($l->account_id);
                    $accountDisplay = $acc ? ($acc->code . ' - ' . $acc->name) : null;
                }
                $soLine = $l->salesOrderLine;
                $taxCodeId = $l->tax_code_id ?? ($soLine?->tax_code_id);
                $wtaxRate = $soLine ? (float)($soLine->wtax_rate ?? 0) : 0;
                $lines->push([
                    'delivery_order_line_id' => $l->id,
                    'delivery_order_id' => $deliveryOrder->id,
                    'inventory_item_id' => $l->inventory_item_id,
                    'item_code' => optional($l->inventoryItem)->code ?? $l->item_code,
                    'item_name' => optional($l->inventoryItem)->name ?? $l->item_name ?? $l->description,
                    'account_id' => $accountId,
                    'account_display' => $accountDisplay,
                    'has_inventory_item' => !empty($l->inventory_item_id),
                    'description' => $l->description ?? optional($l->inventoryItem)->description ?? $l->item_name,
                    'qty' => (float)$l->delivered_qty,
                    'unit_price' => (float)$l->unit_price,
                    'tax_code_id' => $taxCodeId ? (int)$taxCodeId : null,
                    'wtax_rate' => $wtaxRate,
                    'total_amount' => $l->delivered_qty * $l->unit_price,
                ]);
            }
        }

        return [
            'date' => now()->toDateString(),
            'business_partner_id' => $firstDo->business_partner_id,
            'company_entity_id' => $firstDo->company_entity_id ?? $defaultEntityId,
            'description' => 'From DO ' . $doNumbers,
            'reference_no' => $referenceNo,
            'delivery_order_ids' => $deliveryOrders->pluck('id')->values()->all(),
            'lines' => $lines->all(),
        ];
    }

    public function edit(int $id)
    {
        $invoice = SalesInvoice::with(['lines.account', 'lines.taxCode', 'lines.inventoryItem'])->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('sales-invoices.show', $id)
                ->with('error', 'Only draft sales invoices can be edited.');
        }

        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $vatTaxCodes = DB::table('tax_codes')->where('type', 'ppn_output')->whereIn('rate', [11, 12])->orderBy('rate')->get(['id', 'code', 'rate']);
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        return view('sales_invoices.edit', compact('invoice', 'accounts', 'customers', 'vatTaxCodes', 'entities', 'defaultEntity'));
    }

    public function update(Request $request, int $id)
    {
        $invoice = SalesInvoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('sales-invoices.show', $id)
                ->with('error', 'Only draft sales invoices can be edited.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.wtax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
            'lines.*.delivery_order_line_id' => ['nullable', 'integer', 'exists:delivery_order_lines,id'],
        ]);

        $invoice->load('deliveryOrders');
        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            $invoice->deliveryOrders->first() ?? $invoice->salesOrder ?? null
        );

        return DB::transaction(function () use ($data, $request, $invoice, $entity) {
            $invoice->update([
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'company_entity_id' => $entity->id,
                'description' => $data['description'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'is_opening_balance' => $request->boolean('is_opening_balance', false),
            ]);

            $invoice->lines()->delete();

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;
                SalesInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'delivery_order_line_id' => $l['delivery_order_line_id'] ?? null,
                    'inventory_item_id' => $l['inventory_item_id'] ?? null,
                    'item_code' => $l['item_code'] ?? $l['item_code_display'] ?? null,
                    'item_name' => $l['item_name'] ?? $l['item_name_display'] ?? null,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float) $l['qty'],
                    'unit_price' => (float) $l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'wtax_rate' => $l['wtax_rate'] ?? 0,
                    'project_id' => $l['project_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'] . ' +' . $termsDays . ' days')) : null;
            $invoice->update(['total_amount' => $total, 'terms_days' => $termsDays ?: null, 'due_date' => $dueDate]);

            return redirect()->route('sales-invoices.show', $invoice->id)
                ->with('success', 'Sales invoice updated.');
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'delivery_order_id' => ['nullable', 'integer', 'exists:delivery_orders,id'],
            'delivery_order_ids' => ['nullable', 'array'],
            'delivery_order_ids.*' => ['integer', 'exists:delivery_orders,id'],
            'is_opening_balance' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_code_id' => ['nullable', 'integer', 'exists:tax_codes,id'],
            'lines.*.wtax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
        ]);

        $deliveryOrderIds = array_filter(array_map('intval', $data['delivery_order_ids'] ?? []));
        if (empty($deliveryOrderIds) && $request->filled('delivery_order_id')) {
            $deliveryOrderIds = [(int) $request->input('delivery_order_id')];
        }
        $salesOrder = $request->input('sales_order_id')
            ? SalesOrder::select('id', 'company_entity_id')->find($request->input('sales_order_id'))
            : null;
        $deliveryOrders = !empty($deliveryOrderIds)
            ? DeliveryOrder::whereIn('id', $deliveryOrderIds)->get()
            : collect();

        if ($deliveryOrders->isNotEmpty()) {
            $alreadyInvoiced = DB::table('delivery_order_sales_invoice')
                ->whereIn('delivery_order_id', $deliveryOrderIds)
                ->exists();
            if ($alreadyInvoiced) {
                $existing = DB::table('delivery_order_sales_invoice')
                    ->whereIn('delivery_order_id', $deliveryOrderIds)
                    ->first();
                $existingSi = SalesInvoice::find($existing->sales_invoice_id);
                return redirect()->route('sales-invoices.create')
                    ->withInput()
                    ->with('error', 'A Sales Invoice (#' . ($existingSi?->invoice_no ?? $existing->sales_invoice_id) . ') already exists for one or more of these Delivery Orders.');
            }
        }

        $salesQuotation = $request->input('sales_quotation_id')
            ? SalesQuotation::select('id', 'company_entity_id')->find($request->input('sales_quotation_id'))
            : null;
        $entity = $this->companyEntityService->resolveFromModel(
            $request->input('company_entity_id'),
            $deliveryOrders->first() ?? $salesOrder ?? $salesQuotation
        );

        return DB::transaction(function () use ($data, $request, $salesOrder, $deliveryOrders, $salesQuotation, $entity) {
            $invoice = SalesInvoice::create([
                'invoice_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'sales_order_id' => $request->input('sales_order_id'),
                'is_opening_balance' => $request->boolean('is_opening_balance', false),
                'description' => $data['description'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'draft',
                'total_amount' => 0,
                'company_entity_id' => $entity->id,
            ]);

            if ($deliveryOrders->isNotEmpty()) {
                $invoice->deliveryOrders()->attach($deliveryOrders->pluck('id')->all());
                foreach ($deliveryOrders as $do) {
                    $this->documentRelationshipService->createBaseRelationship(
                        $do,
                        $invoice,
                        'Sales Invoice created from Delivery Order'
                    );
                    $this->documentRelationshipService->createTargetRelationship(
                        $do,
                        $invoice,
                        'Sales Invoice created from Delivery Order'
                    );
                }
            }

            if ($salesQuotation && $salesQuotation->quotation_no) {
                $quotationRef = 'From Quotation: ' . $salesQuotation->quotation_no;
                $currentDesc = $data['description'] ?? '';
                if ($currentDesc && strpos($currentDesc, $quotationRef) === false) {
                    $invoice->update(['description' => $currentDesc . ' (' . $quotationRef . ')']);
                } elseif (!$currentDesc) {
                    $invoice->update(['description' => $quotationRef]);
                }
            }

            $invoiceNo = $this->documentNumberingService->generateNumber('sales_invoice', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $invoice->update(['invoice_no' => $invoiceNo]);

            $total = 0;
            foreach ($data['lines'] as $l) {
                $amount = (float) $l['qty'] * (float) $l['unit_price'];
                $total += $amount;
                SalesInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'delivery_order_line_id' => $l['delivery_order_line_id'] ?? null,
                    'inventory_item_id' => $l['inventory_item_id'] ?? null,
                    'item_code' => $l['item_code'] ?? $l['item_code_display'] ?? null,
                    'item_name' => $l['item_name'] ?? $l['item_name_display'] ?? null,
                    'account_id' => $l['account_id'],
                    'description' => $l['description'] ?? null,
                    'qty' => (float) $l['qty'],
                    'unit_price' => (float) $l['unit_price'],
                    'amount' => $amount,
                    'tax_code_id' => $l['tax_code_id'] ?? null,
                    'wtax_rate' => $l['wtax_rate'] ?? 0,
                    'project_id' => $l['project_id'] ?? null,
                    'dept_id' => $l['dept_id'] ?? null,
                ]);
            }

            $termsDays = (int) ($request->input('terms_days') ?? 0);
            $dueDate = $termsDays > 0 ? date('Y-m-d', strtotime($data['date'] . ' +' . $termsDays . ' days')) : null;
            $invoice->update(['total_amount' => $total, 'terms_days' => $termsDays ?: null, 'due_date' => $dueDate]);

            if ($salesOrder) {
                app(SalesWorkflowAuditService::class)->logSalesInvoiceCreation($salesOrder, $invoice->id);
            }

            foreach ($deliveryOrders as $do) {
                try {
                    $this->documentClosureService->closeDeliveryOrder($do->id, $invoice->id, auth()->id());
                } catch (\Exception $closureException) {
                    Log::warning('Failed to close Delivery Order after SI creation', [
                        'do_id' => $do->id,
                        'si_id' => $invoice->id,
                        'error' => $closureException->getMessage()
                    ]);
                }
            }

            return redirect()->route('sales-invoices.show', $invoice->id)->with('success', 'Invoice created');
        });
    }

    public function destroy(int $id)
    {
        $invoice = SalesInvoice::with('deliveryOrders')->findOrFail($id);
        if ($invoice->status !== 'draft') {
            return redirect()->route('sales-invoices.show', $invoice->id)
                ->with('error', 'Only draft Sales Invoices can be deleted.');
        }
        $allocated = DB::table('sales_receipt_allocations')->where('invoice_id', $invoice->id)->exists();
        if ($allocated) {
            return redirect()->route('sales-invoices.show', $invoice->id)
                ->with('error', 'Cannot delete: this invoice has payment allocations.');
        }
        $linkedDoIds = $invoice->deliveryOrders->pluck('id')->all();
        $invoice->lines()->delete();
        $invoice->deliveryOrders()->detach();
        $invoice->delete();
        foreach ($linkedDoIds as $doId) {
            $do = DeliveryOrder::find($doId);
            if ($do && ($do->closure_status ?? 'open') === 'closed' && ($do->closed_by_document_id ?? null) == $id) {
                $do->update([
                    'closure_status' => 'open',
                    'closed_by_document_type' => null,
                    'closed_by_document_id' => null,
                    'closed_at' => null,
                    'closed_by_user_id' => null,
                ]);
            }
        }
        return redirect()->route('sales-invoices.index')->with('success', 'Sales Invoice deleted.');
    }

    public function show(int $id)
    {
        $invoice = SalesInvoice::with([
            'businessPartner.primaryAddress',
            'companyEntity',
            'salesOrder',
            'deliveryOrders',
            'lines.account',
            'lines.taxCode',
            'lines.inventoryItem',
        ])->findOrFail($id);
        return view('sales_invoices.show', compact('invoice'));
    }

    public function pdf(int $id)
    {
        $invoice = SalesInvoice::with(['lines', 'lines.account', 'lines.taxCode', 'lines.inventoryItem', 'businessPartner', 'businessPartner.primaryAddress', 'companyEntity', 'deliveryOrders'])->findOrFail($id);
        $pdf = app(\App\Services\PdfService::class)->renderViewToString('sales_invoices.print', [
            'invoice' => $invoice,
        ]);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-' . $id . '.pdf"'
        ]);
    }

    public function queuePdf(int $id)
    {
        $invoice = SalesInvoice::with(['lines', 'lines.account', 'lines.taxCode', 'lines.inventoryItem', 'businessPartner', 'businessPartner.primaryAddress', 'companyEntity', 'deliveryOrders'])->findOrFail($id);
        $path = 'public/pdfs/invoice-' . $invoice->id . '.pdf';
        \App\Jobs\GeneratePdfJob::dispatch('sales_invoices.print', ['invoice' => $invoice], $path);
        $url = \Illuminate\Support\Facades\Storage::url($path);
        return back()->with('success', 'PDF generation started')->with('pdf_url', $url);
    }

    public function post(int $id)
    {
        $invoice = SalesInvoice::with('lines')->findOrFail($id);
        if ($invoice->status === 'posted') {
            return back()->with('success', 'Already posted');
        }

        $arUnInvoiceAccountId = (int) DB::table('accounts')->where('code', '1.1.2.04')->value('id'); // AR UnInvoice
        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id'); // Piutang Dagang
        $ppnOutputId = (int) DB::table('accounts')->where('code', '2.1.2')->value('id');

        $revenueTotal = 0.0;
        $ppnTotal = 0.0;
        $lines = [];

        // Calculate totals first
        foreach ($invoice->lines as $l) {
            $revenueTotal += (float) $l->amount;
            if (!empty($l->tax_code_id)) {
                $rate = (float) DB::table('tax_codes')->where('id', $l->tax_code_id)->value('rate');
                $ppnTotal += round($l->amount * $rate, 2);
            }
        }

        // Check if this is an opening balance invoice
        // Opening balance invoices post directly to AR and Retained Earnings Opening Balance
        $isOpeningBalance = $invoice->is_opening_balance;

        if ($isOpeningBalance) {
            // Opening balance invoice: Post directly to AR and Retained Earnings Opening Balance (3.3.1)
            $retainedEarningsAccountId = (int) DB::table('accounts')->where('code', '3.3.1')->value('id'); // Saldo Awal Laba Ditahan
            
            if (!$retainedEarningsAccountId) {
                throw new \Exception('Retained Earnings Opening Balance account (3.3.1) not found. Please ensure this account exists in the chart of accounts.');
            }

            // Debit AR Account (creating accounts receivable)
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $revenueTotal + $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Accounts Receivable - Opening Balance',
            ];

            // Credit Retained Earnings Opening Balance (3.3.1)
            $lines[] = [
                'account_id' => $retainedEarningsAccountId,
                'debit' => 0,
                'credit' => $revenueTotal,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Saldo Awal Laba Ditahan - Opening Balance',
            ];

            // Credit VAT Output Account (recognizing VAT liability)
            if ($ppnTotal > 0) {
                $lines[] = [
                    'account_id' => $ppnOutputId,
                    'debit' => 0,
                    'credit' => $ppnTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'PPN Keluaran',
                ];
            }
        } else {
            // Regular invoice: Post using AR UnInvoice flow
            // At DO completion we debited AR UnInvoice for revenue only. When we invoice we:
            // 1. Credit AR UnInvoice (reduce - clear the revenue we had as un-invoiced)
            // 2. Debit AR (create full receivable = revenue + VAT)
            // 3. Credit PPN (VAT liability)
            $lines[] = [
                'account_id' => $arUnInvoiceAccountId,
                'debit' => 0,
                'credit' => $revenueTotal,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Reduce AR UnInvoice - convert to invoiced AR',
            ];

            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => $revenueTotal + $ppnTotal,
                'credit' => 0,
                'project_id' => null,
                'dept_id' => null,
                'memo' => 'Accounts Receivable',
            ];

            if ($ppnTotal > 0) {
                $lines[] = [
                    'account_id' => $ppnOutputId,
                    'debit' => 0,
                    'credit' => $ppnTotal,
                    'project_id' => null,
                    'dept_id' => null,
                    'memo' => 'PPN Keluaran',
                ];
            }
        }

        DB::transaction(function () use ($invoice, $lines) {
            $jid = $this->posting->postJournal([
                'date' => $invoice->date->toDateString(),
                'description' => $invoice->is_opening_balance 
                    ? 'Post AR Invoice (Opening Balance) #' . $invoice->invoice_no
                    : 'Post AR Invoice #' . $invoice->invoice_no,
                'source_type' => 'sales_invoice',
                'source_id' => $invoice->id,
                'lines' => $lines,
            ]);

            $invoice->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return back()->with('success', 'Invoice posted');
    }

    public function data(Request $request)
    {
        $q = DB::table('sales_invoices as si')
            ->leftJoin('business_partners as c', 'c.id', '=', 'si.business_partner_id')
            ->select('si.id', 'si.date', 'si.invoice_no', 'si.business_partner_id', 'c.name as customer_name', 'si.total_amount', 'si.status');

        if ($request->filled('status')) {
            $q->where('si.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('si.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('si.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('si.invoice_no', 'like', '%' . $kw . '%')
                    ->orWhere('si.description', 'like', '%' . $kw . '%')
                    ->orWhere('c.name', 'like', '%' . $kw . '%');
            });
        }

        return DataTables::of($q)
            ->editColumn('total_amount', function ($row) {
                return number_format((float)$row->total_amount, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper($row->status);
            })
            ->addColumn('customer', function ($row) {
                return $row->customer_name ?: ('#' . $row->business_partner_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('sales-invoices.show', $row->id);
                return '<a href="' . $url . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
