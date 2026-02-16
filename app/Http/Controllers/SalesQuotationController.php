<?php

namespace App\Http\Controllers;

use App\Models\SalesQuotation;
use App\Models\SalesQuotationLine;
use App\Models\SalesQuotationApproval;
use App\Models\InventoryItem;
use App\Services\QuotationService;
use App\Services\QuotationConversionService;
use App\Services\DocumentNumberingService;
use App\Services\CompanyEntityService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SalesQuotationController extends Controller
{
    protected $quotationService;
    protected $quotationConversionService;
    protected $documentNumberingService;
    protected $companyEntityService;
    protected $currencyService;
    protected $exchangeRateService;

    public function __construct(
        QuotationService $quotationService,
        QuotationConversionService $quotationConversionService,
        DocumentNumberingService $documentNumberingService,
        CompanyEntityService $companyEntityService,
        CurrencyService $currencyService,
        ExchangeRateService $exchangeRateService
    ) {
        $this->quotationService = $quotationService;
        $this->quotationConversionService = $quotationConversionService;
        $this->documentNumberingService = $documentNumberingService;
        $this->companyEntityService = $companyEntityService;
        $this->currencyService = $currencyService;
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index()
    {
        return view('sales_quotations.index');
    }

    public function data(Request $request)
    {
        $query = SalesQuotation::with(['businessPartner', 'companyEntity'])
            ->select([
                'id',
                'quotation_no',
                'date',
                'valid_until_date',
                'business_partner_id',
                'company_entity_id',
                'total_amount',
                'net_amount',
                'status',
                'approval_status',
                'created_at'
            ]);

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('date', '<=', $request->to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        if ($request->filled('expired')) {
            if ($request->expired === 'yes') {
                $query->where('valid_until_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['converted', 'rejected', 'expired']);
            } else {
                $query->where(function ($q) {
                    $q->where('valid_until_date', '>=', now()->toDateString())
                        ->orWhereIn('status', ['converted', 'rejected', 'expired']);
                });
            }
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('quotation_no', 'like', '%' . $request->q . '%')
                    ->orWhere('reference_no', 'like', '%' . $request->q . '%')
                    ->orWhereHas('businessPartner', function ($bp) use ($request) {
                        $bp->where('name', 'like', '%' . $request->q . '%');
                    });
            });
        }

        return DataTables::of($query)
            ->addColumn('customer', function ($row) {
                return $row->businessPartner->name ?? '';
            })
            ->addColumn('entity', function ($row) {
                return $row->companyEntity->name ?? '';
            })
            ->editColumn('date', function ($row) {
                return $row->date ? $row->date->format('d-M-Y') : '';
            })
            ->editColumn('valid_until_date', function ($row) {
                if (!$row->valid_until_date) {
                    return '';
                }
                $date = $row->valid_until_date->format('d-M-Y');
                $isExpired = $row->valid_until_date < now()->toDateString() && !in_array($row->status, ['converted', 'rejected', 'expired']);
                $isExpiringSoon = $row->valid_until_date <= now()->addDays(3)->toDateString() && !$isExpired && !in_array($row->status, ['converted', 'rejected', 'expired']);
                
                if ($isExpired) {
                    return '<span class="text-danger" title="Expired">' . $date . '</span>';
                } elseif ($isExpiringSoon) {
                    return '<span class="text-warning" title="Expiring Soon">' . $date . '</span>';
                }
                return $date;
            })
            ->editColumn('total_amount', function ($row) {
                return 'Rp ' . number_format($row->total_amount, 0, ',', '.');
            })
            ->editColumn('net_amount', function ($row) {
                return 'Rp ' . number_format($row->net_amount, 0, ',', '.');
            })
            ->addColumn('status_badge', function ($row) {
                $badges = [
                    'draft' => '<span class="badge badge-secondary">Draft</span>',
                    'sent' => '<span class="badge badge-info">Sent</span>',
                    'accepted' => '<span class="badge badge-success">Accepted</span>',
                    'rejected' => '<span class="badge badge-danger">Rejected</span>',
                    'expired' => '<span class="badge badge-warning">Expired</span>',
                    'converted' => '<span class="badge badge-primary">Converted</span>',
                ];
                return $badges[$row->status] ?? '<span class="badge badge-secondary">' . ucfirst($row->status) . '</span>';
            })
            ->addColumn('approval_badge', function ($row) {
                $badges = [
                    'pending' => '<span class="badge badge-warning">Pending</span>',
                    'approved' => '<span class="badge badge-success">Approved</span>',
                    'rejected' => '<span class="badge badge-danger">Rejected</span>',
                ];
                return $badges[$row->approval_status] ?? '';
            })
            ->addColumn('actions', function ($row) {
                $actions = '<div class="btn-group">';
                $actions .= '<a href="' . route('sales-quotations.show', $row->id) . '" class="btn btn-xs btn-info" title="View"><i class="fas fa-eye"></i></a>';
                
                if ($row->status === 'draft') {
                    $actions .= '<a href="' . route('sales-quotations.edit', $row->id) . '" class="btn btn-xs btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                }
                
                if ($row->canBeSent()) {
                    $actions .= '<form method="POST" action="' . route('sales-quotations.send', $row->id) . '" style="display:inline;" data-confirm="Send this quotation to customer?">';
                    $actions .= csrf_field();
                    $actions .= '<button type="submit" class="btn btn-xs btn-primary" title="Send"><i class="fas fa-paper-plane"></i></button>';
                    $actions .= '</form>';
                }
                
                if ($row->canBeConverted()) {
                    $actions .= '<a href="' . route('sales-quotations.convert', $row->id) . '" class="btn btn-xs btn-success" title="Convert to Sales Order"><i class="fas fa-exchange-alt"></i></a>';
                }
                
                if (in_array($row->status, ['sent', 'accepted'])) {
                    $actions .= '<a href="' . route('sales-quotations.print', $row->id) . '" class="btn btn-xs btn-secondary" title="Print" target="_blank"><i class="fas fa-print"></i></a>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['valid_until_date', 'status_badge', 'approval_badge', 'actions'])
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

        return view('sales_quotations.create', compact(
            'customers',
            'accounts',
            'taxCodes',
            'inventoryItems',
            'warehouses',
            'currencies',
            'defaultEntity',
            'entities'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'quotation_no' => ['nullable', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'valid_until_date' => ['required', 'date', 'after_or_equal:date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
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
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
            'lines.*.order_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'lines.*.base_quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_conversion_factor' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $totalAmount = 0;
            $totalAmountForeign = 0;
            $exchangeRate = $data['exchange_rate'];

            foreach ($data['lines'] as &$line) {
                $originalAmount = $line['qty'] * $line['unit_price'];
                $vatAmount = $originalAmount * ($line['vat_rate'] / 100);
                $wtaxAmount = $originalAmount * ($line['wtax_rate'] / 100);
                $lineAmount = $originalAmount + $vatAmount - $wtaxAmount;
                
                $lineDiscountAmount = $line['discount_amount'] ?? 0;
                $lineDiscountPercentage = $line['discount_percentage'] ?? 0;
                
                if ($lineDiscountPercentage > 0) {
                    $lineDiscountAmount = ($lineAmount * $lineDiscountPercentage) / 100;
                }
                
                $lineAmount = $lineAmount - $lineDiscountAmount;
                $totalAmount += $lineAmount;

                $unitPriceForeign = $line['unit_price_foreign'] ?? $line['unit_price'];
                $lineAmountForeign = $line['qty'] * $unitPriceForeign;
                $totalAmountForeign += $lineAmountForeign;

                $line['amount_foreign'] = $lineAmountForeign;
            }

            $data['total_amount'] = $totalAmount;
            $data['total_amount_foreign'] = $totalAmountForeign;

            $quotation = $this->quotationService->createQuotation($data);
            
            return redirect()->route('sales-quotations.show', $quotation->id)
                ->with('success', 'Sales Quotation created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating quotation: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $salesQuotation = SalesQuotation::with(['lines.inventoryItem', 'lines.account', 'lines.orderUnit', 'lines.taxCode', 'businessPartner', 'companyEntity', 'currency', 'warehouse', 'approvals.user', 'convertedToSalesOrder'])
            ->findOrFail($id);
        
        return view('sales_quotations.show', compact('salesQuotation'));
    }

    public function edit($id)
    {
        $quotation = SalesQuotation::with(['lines', 'businessPartner', 'companyEntity', 'warehouse', 'currency'])->findOrFail($id);
        
        if ($quotation->status !== 'draft') {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Quotation can only be edited when in draft status');
        }

        $customers = DB::table('business_partners')->where('partner_type', 'customer')->orderBy('name')->get();
        $accounts = DB::table('accounts')->where('is_postable', 1)->orderBy('code')->get();
        $taxCodes = DB::table('tax_codes')->orderBy('code')->get();
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $warehouses = DB::table('warehouses')->where('is_active', 1)->where('name', 'not like', '%Transit%')->orderBy('name')->get();
        $currencies = $this->currencyService->getActiveCurrencies();
        $entities = $this->companyEntityService->getActiveEntities();

        return view('sales_quotations.edit', compact(
            'quotation',
            'customers',
            'accounts',
            'taxCodes',
            'inventoryItems',
            'warehouses',
            'currencies',
            'entities'
        ));
    }

    public function update(Request $request, $id)
    {
        $quotation = SalesQuotation::findOrFail($id);
        
        if ($quotation->status !== 'draft') {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Quotation can only be updated when in draft status');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'valid_until_date' => ['required', 'date', 'after_or_equal:date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'business_partner_id' => ['required', 'integer', 'exists:business_partners,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
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
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string'],
            'lines.*.order_unit_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'lines.*.base_quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_conversion_factor' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $totalAmount = 0;
            $totalAmountForeign = 0;

            foreach ($data['lines'] as &$line) {
                $originalAmount = $line['qty'] * $line['unit_price'];
                $vatAmount = $originalAmount * ($line['vat_rate'] / 100);
                $wtaxAmount = $originalAmount * ($line['wtax_rate'] / 100);
                $lineAmount = $originalAmount + $vatAmount - $wtaxAmount;
                
                $lineDiscountAmount = $line['discount_amount'] ?? 0;
                $lineDiscountPercentage = $line['discount_percentage'] ?? 0;
                
                if ($lineDiscountPercentage > 0) {
                    $lineDiscountAmount = ($lineAmount * $lineDiscountPercentage) / 100;
                }
                
                $lineAmount = $lineAmount - $lineDiscountAmount;
                $totalAmount += $lineAmount;

                $unitPriceForeign = $line['unit_price_foreign'] ?? $line['unit_price'];
                $lineAmountForeign = $line['qty'] * $unitPriceForeign;
                $totalAmountForeign += $lineAmountForeign;

                $line['amount_foreign'] = $lineAmountForeign;
            }

            $data['total_amount'] = $totalAmount;
            $data['total_amount_foreign'] = $totalAmountForeign;

            $quotation = $this->quotationService->updateQuotation($id, $data);
            
            return redirect()->route('sales-quotations.show', $quotation->id)
                ->with('success', 'Sales Quotation updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error updating quotation: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $quotation = SalesQuotation::findOrFail($id);
        
        if ($quotation->status !== 'draft') {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Quotation can only be deleted when in draft status');
        }

        try {
            $quotation->delete();
            return redirect()->route('sales-quotations.index')
                ->with('success', 'Sales Quotation deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error deleting quotation: ' . $e->getMessage());
        }
    }

    public function send($id)
    {
        try {
            $quotation = $this->quotationService->sendQuotation($id);
            return redirect()->route('sales-quotations.show', $id)
                ->with('success', 'Quotation sent successfully');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error sending quotation: ' . $e->getMessage());
        }
    }

    public function accept($id)
    {
        try {
            $quotation = $this->quotationService->acceptQuotation($id);
            return redirect()->route('sales-quotations.show', $id)
                ->with('success', 'Quotation accepted successfully');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error accepting quotation: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $quotation = $this->quotationService->rejectQuotation($id, $data['reason'] ?? null);
            return redirect()->route('sales-quotations.show', $id)
                ->with('success', 'Quotation rejected successfully');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error rejecting quotation: ' . $e->getMessage());
        }
    }

    public function convert($id)
    {
        $quotation = SalesQuotation::findOrFail($id);
        
        if (!$quotation->canBeConverted()) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Quotation cannot be converted in current status');
        }

        return view('sales_quotations.convert', compact('quotation'));
    }

    public function convertToSalesOrder(Request $request, $id)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
        ]);

        try {
            $salesOrder = $this->quotationConversionService->convertQuotationToSalesOrder($id, $data);
            return redirect()->route('sales-orders.show', $salesOrder->id)
                ->with('success', 'Quotation converted to Sales Order successfully');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error converting quotation: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $salesQuotation = SalesQuotation::with(['lines.inventoryItem', 'lines.account', 'businessPartner', 'companyEntity', 'warehouse', 'currency'])->findOrFail($id);
        return view('sales_quotations.print', compact('salesQuotation'));
    }

    public function approve(Request $request, $id)
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $quotation = SalesQuotation::findOrFail($id);
            $approval = $quotation->approvals()
                ->where('user_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return redirect()->route('sales-quotations.show', $id)
                    ->with('error', 'No pending approval found for this user');
            }

            $approval->approve($data['comments'] ?? null);

            $pendingApprovals = $quotation->approvals()->where('status', 'pending')->count();

            if ($pendingApprovals === 0) {
                $quotation->update([
                    'approval_status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);
            }

            return redirect()->route('sales-quotations.show', $id)
                ->with('success', 'Quotation approved successfully');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error approving quotation: ' . $e->getMessage());
        }
    }

    public function rejectApproval(Request $request, $id)
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $quotation = SalesQuotation::findOrFail($id);
            $approval = $quotation->approvals()
                ->where('user_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return redirect()->route('sales-quotations.show', $id)
                    ->with('error', 'No pending approval found for this user');
            }

            $approval->reject($data['comments'] ?? null);

            $quotation->update([
                'approval_status' => 'rejected',
            ]);

            return redirect()->route('sales-quotations.show', $id)
                ->with('success', 'Quotation approval rejected');
        } catch (\Exception $e) {
            return redirect()->route('sales-quotations.show', $id)
                ->with('error', 'Error rejecting approval: ' . $e->getMessage());
        }
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

            $documentNumber = $this->documentNumberingService->previewNumber('sales_quotation', $date, [
                'company_entity_id' => $entityId,
            ]);

            return response()->json(['document_number' => $documentNumber]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error generating document number: ' . $e->getMessage()], 500);
        }
    }
}
