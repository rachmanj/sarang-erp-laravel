<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\PurchaseAdvance;
use App\Models\Accounting\PurchaseAdvanceApplication;
use App\Models\Accounting\PurchaseAdvanceLine;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\PurchasePaymentLine;
use App\Models\Currency;
use App\Models\PurchaseOrder;
use App\Services\Accounting\JournalBuilders\PurchaseAdvanceJournalBuilder;
use App\Services\Accounting\JournalBuilders\PurchasePaymentJournalBuilder;
use App\Services\Accounting\PostingService;
use App\Services\CompanyEntityService;
use App\Services\DocumentClosureService;
use App\Services\DocumentNumberingService;
use App\Support\PostingErrorPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseAdvanceController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private CompanyEntityService $companyEntityService,
        private PurchaseAdvanceJournalBuilder $purchaseAdvanceJournalBuilder,
        private PurchasePaymentJournalBuilder $purchasePaymentJournalBuilder,
        private DocumentClosureService $documentClosureService,
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:ap.payments.view')->only(['index', 'data', 'show']);
        $this->middleware('permission:ap.payments.create')->only(['create', 'store', 'getDocumentNumber']);
        $this->middleware('permission:ap.payments.post')->only(['post', 'cancel', 'applyToInvoice', 'linkPurchaseOrder']);
    }

    public function index()
    {
        $ptCahaya = \App\Models\CompanyEntity::where('code', '71')->first();
        $cvCahaya = \App\Models\CompanyEntity::where('code', '72')->first();

        return view('purchase_advances.index', compact('ptCahaya', 'cvCahaya'));
    }

    public function create()
    {
        $vendors = DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $entities = $this->companyEntityService->getActiveEntities();
        $defaultEntity = $this->companyEntityService->getDefaultEntity();

        $purchaseOrdersByVendor = DB::table('purchase_orders')
            ->where('status', 'ordered')
            ->whereNotNull('company_entity_id')
            ->orderByDesc('date')
            ->limit(1000)
            ->get(['id', 'order_no', 'business_partner_id', 'company_entity_id', 'total_amount', 'date'])
            ->groupBy('business_partner_id');

        return view('purchase_advances.create', compact(
            'vendors',
            'accounts',
            'entities',
            'defaultEntity',
            'purchaseOrdersByVendor',
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

            $documentNumber = $this->documentNumberingService->previewNumber('purchase_advance', $date, [
                'company_entity_id' => $entityId,
            ]);

            return response()->json(['document_number' => $documentNumber]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating document number: '.$e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'company_entity_id' => ['required', 'integer', 'exists:company_entities,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'description' => ['nullable', 'string', 'max:255', 'required_without:purchase_order_id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $entity = $this->companyEntityService->getEntity($request->input('company_entity_id'));

        if (! empty($data['purchase_order_id'])) {
            $po = PurchaseOrder::find($data['purchase_order_id']);
            $poError = $this->purchaseOrderEligibilityError(
                $po,
                (int) $data['business_partner_id'],
                (int) $data['company_entity_id']
            );
            if ($poError !== null) {
                return back()->withErrors(['purchase_order_id' => $poError])->withInput();
            }
        }

        return DB::transaction(function () use ($data, $entity) {
            $total = 0.0;
            foreach ($data['lines'] as $line) {
                $total += (float) $line['amount'];
            }

            $baseCurrency = Currency::getBaseCurrency();
            $currencyId = $baseCurrency ? $baseCurrency->id : 1;

            $advance = PurchaseAdvance::create([
                'advance_no' => null,
                'date' => $data['date'],
                'business_partner_id' => $data['business_partner_id'],
                'company_entity_id' => $entity->id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'total_amount' => $total,
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $advanceNo = $this->documentNumberingService->generateNumber('purchase_advance', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $advance->update(['advance_no' => $advanceNo]);

            foreach ($data['lines'] as $line) {
                PurchaseAdvanceLine::create([
                    'advance_id' => $advance->id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'amount' => (float) $line['amount'],
                ]);
            }

            $redirect = redirect()->route('purchase-advances.show', $advance->id)
                ->with('success', 'Uang muka pembelian berhasil dibuat.');

            if (! empty($data['purchase_order_id'])) {
                $poTotal = (float) PurchaseOrder::whereKey($data['purchase_order_id'])->value('total_amount');
                if ($total > $poTotal + 0.01) {
                    session()->flash(
                        'warning',
                        sprintf(
                            'Total uang muka (%.2f) melebihi nilai PO (%.2f). Dokumen tetap disimpan.',
                            $total,
                            $poTotal
                        )
                    );
                }
            }

            return $redirect;
        });
    }

    public function show(int $id)
    {
        $advance = PurchaseAdvance::with([
            'lines',
            'businessPartner',
            'purchaseOrder',
            'companyEntity',
            'applications.invoice',
            'applications.payment',
        ])->findOrFail($id);

        $usedAmount = (float) $advance->applications->sum('amount');
        $remainingAdvance = max(0, (float) $advance->total_amount - $usedAmount);

        $availableInvoices = collect();
        if ($advance->status === 'posted' && $remainingAdvance > 0.009) {
            $availableInvoices = DB::table('purchase_invoices as pi')
                ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
                ->select(
                    'pi.id',
                    'pi.invoice_no',
                    'pi.date',
                    'pi.total_amount',
                    DB::raw('COALESCE(SUM(ppa.amount), 0) as allocated_amount'),
                    DB::raw('pi.total_amount - COALESCE(SUM(ppa.amount), 0) as remaining_balance')
                )
                ->where('pi.business_partner_id', $advance->business_partner_id)
                ->where('pi.company_entity_id', $advance->company_entity_id)
                ->where('pi.status', 'posted')
                ->where(function ($q) {
                    $q->where('pi.is_direct_purchase', 0)
                        ->orWhereNull('pi.is_direct_purchase');
                })
                ->groupBy('pi.id', 'pi.invoice_no', 'pi.date', 'pi.total_amount')
                ->havingRaw('remaining_balance > 0.009')
                ->orderBy('pi.date')
                ->orderBy('pi.id')
                ->get();
        }

        $linkablePurchaseOrders = collect();
        if (! $advance->purchase_order_id && $advance->applications->isEmpty()) {
            $linkablePurchaseOrders = DB::table('purchase_orders')
                ->where('status', 'ordered')
                ->where('business_partner_id', $advance->business_partner_id)
                ->where('company_entity_id', $advance->company_entity_id)
                ->whereNotNull('company_entity_id')
                ->orderByDesc('date')
                ->limit(100)
                ->get(['id', 'order_no', 'total_amount', 'date']);
        }

        return view('purchase_advances.show', compact(
            'advance',
            'remainingAdvance',
            'usedAmount',
            'availableInvoices',
            'linkablePurchaseOrders',
        ));
    }

    public function linkPurchaseOrder(Request $request, int $id)
    {
        $advance = PurchaseAdvance::with(['purchaseOrder', 'applications'])->find($id);
        if (! $advance) {
            return back()->with('error', 'Uang muka tidak ditemukan.');
        }

        if ($advance->purchase_order_id !== null) {
            $orderNo = optional($advance->purchaseOrder)->order_no ?? '#'.$advance->purchase_order_id;

            return back()->with('error', 'Uang muka ini sudah tertaut ke PO '.$orderNo.'.');
        }

        $applicationCount = $advance->applications->count();
        if ($applicationCount > 0) {
            return back()->with(
                'error',
                sprintf('Uang muka tidak dapat ditautkan karena sudah memiliki %d pemakaian.', $applicationCount)
            );
        }

        $data = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
        ]);

        $po = PurchaseOrder::find((int) $data['purchase_order_id']);
        $poError = $this->purchaseOrderEligibilityError(
            $po,
            (int) $advance->business_partner_id,
            (int) $advance->company_entity_id
        );
        if ($poError !== null) {
            return back()->with('error', $poError);
        }

        $poId = (int) $data['purchase_order_id'];
        $advance->update(['purchase_order_id' => $poId]);

        app(\App\Services\AuditLogService::class)->log(
            'update',
            'purchase_advance',
            $id,
            ['purchase_order_id' => null],
            ['purchase_order_id' => $poId],
            sprintf(
                'Tautkan PO %s ke uang muka %s',
                $po->order_no,
                $advance->advance_no ?? '#'.$advance->id
            )
        );

        return back()->with('success', 'Purchase order berhasil ditautkan ke uang muka.');
    }

    /** @return string|null Error message when PO cannot be used */
    private function purchaseOrderEligibilityError(?PurchaseOrder $po, int $businessPartnerId, int $companyEntityId): ?string
    {
        if (! $po || $po->status !== 'ordered' || $po->company_entity_id === null) {
            return 'Purchase order tidak tersedia atau belum berstatus ordered.';
        }
        if ((int) $po->business_partner_id !== $businessPartnerId) {
            return 'Purchase order tidak sesuai dengan supplier yang dipilih.';
        }
        if ((int) $po->company_entity_id !== $companyEntityId) {
            return 'Purchase order milik entitas lain.';
        }

        return null;
    }

    public function post(int $id)
    {
        $advance = PurchaseAdvance::with('lines')->findOrFail($id);

        if ($advance->status === 'posted') {
            return back()->with('error', 'Dokumen sudah diposting.');
        }

        if ($advance->status === 'cancelled') {
            return back()->with('error', 'Dokumen yang dibatalkan tidak dapat diposting.');
        }

        if ($advance->status !== 'draft') {
            return back()->with('error', 'Hanya dokumen draft yang dapat diposting.');
        }

        $draft = $this->purchaseAdvanceJournalBuilder->build($advance);

        try {
            DB::transaction(function () use ($advance, $draft) {
                $journalId = $this->posting->postJournal([
                    'date' => $draft->date ?? $advance->date->toDateString(),
                    'description' => $draft->description,
                    'source_type' => 'purchase_advance',
                    'source_id' => $advance->id,
                    'company_entity_id' => $advance->company_entity_id,
                    'lines' => $draft->lines,
                ]);

                $advance->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'journal_id' => $journalId,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', PostingErrorPresenter::present($e, $advance->date->toDateString()));
        }

        return back()->with('success', 'Uang muka pembelian berhasil diposting.');
    }

    public function cancel(int $id)
    {
        $advance = PurchaseAdvance::with('applications')->findOrFail($id);

        if ($advance->status === 'cancelled') {
            return back()->with('error', 'Dokumen sudah dibatalkan.');
        }

        if ($advance->status !== 'posted') {
            return back()->with('error', 'Hanya dokumen yang sudah diposting yang dapat dibatalkan.');
        }

        if ($advance->applications->isNotEmpty()) {
            $usedTotal = (float) $advance->applications->sum('amount');

            return back()->with('error', sprintf(
                'Uang muka tidak dapat dibatalkan karena sudah dipakai ke faktur (total pemakaian %.2f).',
                $usedTotal
            ));
        }

        if (! $advance->journal_id) {
            return back()->with('error', 'Jurnal sumber tidak ditemukan untuk pembatalan.');
        }

        try {
            DB::transaction(function () use ($advance) {
                $this->posting->reverseJournal(
                    (int) $advance->journal_id,
                    $advance->date->toDateString(),
                    Auth::id()
                );

                $advance->update([
                    'status' => 'cancelled',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', PostingErrorPresenter::present($e, $advance->date->toDateString()));
        }

        return back()->with('success', 'Uang muka pembelian berhasil dibatalkan.');
    }

    public function applyToInvoice(Request $request, int $id)
    {
        $advance = PurchaseAdvance::with('applications')->findOrFail($id);

        if ($advance->status !== 'posted') {
            if ($advance->status === 'cancelled') {
                return back()->with('error', 'Uang muka yang dibatalkan tidak dapat dipakai.');
            }

            return back()->with('error', 'Hanya uang muka yang sudah diposting yang dapat dipakai ke faktur.');
        }

        $data = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:purchase_invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            return back()->with('error', 'Jumlah pemakaian harus lebih dari nol.');
        }

        $invoice = PurchaseInvoice::find((int) $data['invoice_id']);
        if (! $invoice) {
            return back()->with('error', 'Faktur pembelian tidak ditemukan.');
        }

        if ($invoice->status !== 'posted') {
            return back()->with('error', 'Faktur harus berstatus posted sebelum pemakaian uang muka.');
        }

        if ((int) ($invoice->is_direct_purchase ?? 0) === 1) {
            return back()->with('error', 'Faktur direct purchase tidak dapat dilunasi dengan uang muka.');
        }

        if ((int) $invoice->business_partner_id !== (int) $advance->business_partner_id) {
            return back()->with('error', 'Uang muka milik supplier lain.');
        }

        $usedAdvance = (float) $advance->applications->sum('amount');
        $remainingAdvance = (float) $advance->total_amount - $usedAdvance;
        if ($amount > $remainingAdvance + 0.01) {
            return back()->with('error', sprintf(
                'Jumlah melebihi sisa uang muka (sisa %.2f).',
                max(0, $remainingAdvance)
            ));
        }

        $allocatedPayments = (float) DB::table('purchase_payment_allocations')
            ->where('invoice_id', $invoice->id)
            ->sum('amount');
        $advanceApplicationsOnInvoice = (float) DB::table('purchase_advance_applications')
            ->where('invoice_id', $invoice->id)
            ->whereNull('payment_id')
            ->sum('amount');
        $remainingInvoice = (float) $invoice->total_amount - $allocatedPayments - $advanceApplicationsOnInvoice;
        if ($amount > $remainingInvoice + 0.01) {
            return back()->with('error', sprintf(
                'Jumlah melebihi sisa utang faktur (sisa %.2f).',
                max(0, $remainingInvoice)
            ));
        }

        $advanceAccountId = (int) DB::table('accounts')->where('code', '1.1.6.01')->value('id');
        if ($advanceAccountId <= 0) {
            return back()->with('error', 'Akun uang muka pembelian (1.1.6.01) tidak ditemukan.');
        }

        $memoAdvance = 'Pemakaian uang muka '.($advance->advance_no ?? '#'.$advance->id);

        try {
            DB::transaction(function () use ($advance, $invoice, $data, $amount, $advanceAccountId, $memoAdvance) {
                $baseCurrency = Currency::getBaseCurrency();
                $currencyId = $baseCurrency ? $baseCurrency->id : 1;

                $payment = PurchasePayment::create([
                    'payment_no' => null,
                    'date' => $data['date'],
                    'business_partner_id' => $advance->business_partner_id,
                    'company_entity_id' => $advance->company_entity_id,
                    'created_by' => Auth::id(),
                    'currency_id' => $currencyId,
                    'description' => $memoAdvance,
                    'status' => 'draft',
                    'total_amount' => $amount,
                    'rounding_amount' => 0,
                    'rounding_account_id' => null,
                ]);

                $paymentNo = $this->documentNumberingService->generateNumber('purchase_payment', $data['date'], [
                    'company_entity_id' => $advance->company_entity_id,
                ]);
                $payment->update(['payment_no' => $paymentNo]);

                PurchasePaymentLine::create([
                    'payment_id' => $payment->id,
                    'account_id' => $advanceAccountId,
                    'description' => $memoAdvance,
                    'amount' => $amount,
                ]);

                DB::table('purchase_payment_allocations')->insert([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $payment->load('lines');
                $draft = $this->purchasePaymentJournalBuilder->build($payment);

                $this->posting->postJournal([
                    'date' => $draft->date ?? $payment->date->toDateString(),
                    'description' => $draft->description,
                    'source_type' => 'purchase_payment',
                    'source_id' => $payment->id,
                    'lines' => $draft->lines,
                ]);

                $payment->update(['status' => 'posted', 'posted_at' => now()]);

                PurchaseAdvanceApplication::create([
                    'advance_id' => $advance->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'payment_id' => $payment->id,
                ]);

                try {
                    $this->documentClosureService->closePurchaseInvoiceByPayment($payment->id, Auth::id());
                } catch (\Throwable) {
                    // Non-blocking — same pattern as PurchasePaymentController::store
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', PostingErrorPresenter::present($e, $data['date']));
        }

        return back()->with('success', 'Uang muka berhasil dipakai ke faktur.');
    }

    public function data(Request $request)
    {
        $q = DB::table('purchase_advances as pa')
            ->leftJoin('business_partners as v', 'v.id', '=', 'pa.business_partner_id')
            ->select(
                'pa.id',
                'pa.date',
                'pa.advance_no',
                'pa.business_partner_id',
                'v.name as vendor_name',
                'pa.total_amount',
                'pa.status'
            );

        if ($request->filled('status')) {
            $q->where('pa.status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('pa.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('pa.date', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $kw = $request->input('q');
            $q->where(function ($w) use ($kw) {
                $w->where('pa.advance_no', 'like', '%'.$kw.'%')
                    ->orWhere('pa.description', 'like', '%'.$kw.'%')
                    ->orWhere('v.name', 'like', '%'.$kw.'%');
            });
        }
        if ($request->filled('company_entity_id')) {
            $q->where('pa.company_entity_id', (int) $request->company_entity_id);
        }

        return DataTables::of($q)
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->editColumn('status', function ($row) {
                return strtoupper($row->status);
            })
            ->addColumn('vendor', function ($row) {
                return $row->vendor_name ?: ('#'.$row->business_partner_id);
            })
            ->addColumn('actions', function ($row) {
                $url = route('purchase-advances.show', $row->id);

                return '<a href="'.$url.'" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
