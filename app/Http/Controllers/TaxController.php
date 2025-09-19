<?php

namespace App\Http\Controllers;

use App\Models\TaxTransaction;
use App\Models\TaxPeriod;
use App\Models\TaxReport;
use App\Models\TaxSetting;
use App\Models\TaxComplianceLog;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function index()
    {
        $currentPeriod = TaxPeriod::getCurrentPeriod();
        $overdueTransactions = $this->taxService->getOverdueTransactions();
        $overdueReports = $this->taxService->getOverdueReports();
        $taxCalendar = $this->taxService->getTaxCalendar();

        $summary = $currentPeriod ?
            $this->taxService->getTaxSummary($currentPeriod->start_date, $currentPeriod->end_date) :
            [];

        return view('tax.index', compact(
            'currentPeriod',
            'overdueTransactions',
            'overdueReports',
            'taxCalendar',
            'summary'
        ));
    }

    public function transactions()
    {
        return view('tax.transactions');
    }

    public function transactionsData(Request $request)
    {
        $query = TaxTransaction::with(['vendor', 'customer', 'createdBy']);

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('tax_type')) {
            $query->where('tax_type', $request->tax_type);
        }

        if ($request->filled('tax_category')) {
            $query->where('tax_category', $request->tax_category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_no', 'like', "%{$search}%")
                    ->orWhere('tax_name', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(20);

        return response()->json($transactions);
    }

    public function createTransaction()
    {
        $vendors = \App\Models\BusinessPartner::where('partner_type', 'supplier')->orderBy('name')->get();
        $customers = \App\Models\BusinessPartner::where('partner_type', 'customer')->orderBy('name')->get();
        $taxRates = TaxSetting::getTaxRates();

        return view('tax.create-transaction', compact('vendors', 'customers', 'taxRates'));
    }

    public function storeTransaction(Request $request)
    {
        $data = $request->validate([
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', 'string', 'in:purchase,sales,adjustment,refund'],
            'tax_type' => ['required', 'string', 'in:ppn,pph_21,pph_22,pph_23,pph_26,pph_4_2'],
            'tax_category' => ['required', 'string', 'in:input,output,withholding'],
            'business_partner_id' => ['nullable', 'integer', 'exists:business_partners,id'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'tax_name' => ['required', 'string', 'max:255'],
            'tax_address' => ['nullable', 'string'],
            'taxable_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $transaction = $this->taxService->createTaxTransaction($data);
            return redirect()->route('tax.transactions')
                ->with('success', 'Tax transaction created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating tax transaction: ' . $e->getMessage());
        }
    }

    public function showTransaction($id)
    {
        $transaction = TaxTransaction::with(['vendor', 'customer', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        $auditTrail = TaxComplianceLog::getAuditTrail('tax_transaction', $id);

        return view('tax.show-transaction', compact('transaction', 'auditTrail'));
    }

    public function markAsPaid($id, Request $request)
    {
        $request->validate([
            'payment_method' => ['nullable', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $transaction = TaxTransaction::findOrFail($id);
            $oldValues = $transaction->toArray();

            $transaction->markAsPaid(
                $request->payment_method,
                $request->payment_reference
            );

            TaxComplianceLog::logTaxTransaction('paid', $id, $oldValues, $transaction->toArray());

            return back()->with('success', 'Transaction marked as paid successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating transaction: ' . $e->getMessage());
        }
    }

    public function settings()
    {
        $settings = TaxSetting::active()->orderBy('setting_key')->get();
        $taxRates = TaxSetting::getTaxRates();
        $companyInfo = TaxSetting::getCompanyInfo();
        $taxOfficeInfo = TaxSetting::getTaxOfficeInfo();
        $reportingSettings = TaxSetting::getReportingSettings();

        return view('tax.settings', compact(
            'settings',
            'taxRates',
            'companyInfo',
            'taxOfficeInfo',
            'reportingSettings'
        ));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph_21_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph_22_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph_23_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph_26_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph_4_2_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_npwp' => ['required', 'string', 'max:50'],
            'company_address' => ['required', 'string'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'tax_office_code' => ['required', 'string', 'max:20'],
            'tax_office_name' => ['required', 'string', 'max:255'],
            'tax_office_address' => ['required', 'string'],
            'auto_generate_reports' => ['boolean'],
            'report_due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'send_reminders' => ['boolean'],
            'reminder_days_before' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        try {
            foreach ($data as $key => $value) {
                TaxSetting::set($key, $value);
            }

            return back()->with('success', 'Tax settings updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating settings: ' . $e->getMessage());
        }
    }
}
