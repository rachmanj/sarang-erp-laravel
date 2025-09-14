<?php

namespace App\Services;

use App\Models\TaxTransaction;
use App\Models\TaxPeriod;
use App\Models\TaxReport;
use App\Models\TaxSetting;
use App\Models\TaxComplianceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TaxService
{
    public function createTaxTransaction($data)
    {
        return DB::transaction(function () use ($data) {
            // Generate transaction number
            $transactionNo = TaxTransaction::generateTransactionNumber(
                $data['tax_type'],
                $data['tax_category']
            );

            $transaction = TaxTransaction::create([
                'transaction_no' => $transactionNo,
                'transaction_date' => $data['transaction_date'],
                'transaction_type' => $data['transaction_type'],
                'tax_type' => $data['tax_type'],
                'tax_category' => $data['tax_category'],
                'reference_id' => $data['reference_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'tax_name' => $data['tax_name'] ?? null,
                'tax_address' => $data['tax_address'] ?? null,
                'taxable_amount' => $data['taxable_amount'],
                'tax_rate' => $data['tax_rate'],
                'tax_amount' => 0, // Will be calculated
                'total_amount' => 0, // Will be calculated
                'status' => 'pending',
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Calculate tax amount
            $transaction->calculateTax();

            // Log the creation
            TaxComplianceLog::logTaxTransaction('created', $transaction->id, null, $transaction->toArray());

            return $transaction;
        });
    }

    public function processPurchaseTax($purchaseOrderId, $vendorId, $taxableAmount, $taxType = 'ppn')
    {
        $vendor = \App\Models\Master\Vendor::find($vendorId);
        $taxRate = $this->getTaxRate($taxType);

        $data = [
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'purchase',
            'tax_type' => $taxType,
            'tax_category' => 'input',
            'reference_id' => $purchaseOrderId,
            'reference_type' => 'purchase_order',
            'vendor_id' => $vendorId,
            'tax_number' => $vendor->npwp ?? null,
            'tax_name' => $vendor->name,
            'tax_address' => $vendor->address ?? null,
            'taxable_amount' => $taxableAmount,
            'tax_rate' => $taxRate,
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => "Tax from Purchase Order #{$purchaseOrderId}",
        ];

        return $this->createTaxTransaction($data);
    }

    public function processSalesTax($salesOrderId, $customerId, $taxableAmount, $taxType = 'ppn')
    {
        $customer = \App\Models\Master\Customer::find($customerId);
        $taxRate = $this->getTaxRate($taxType);

        $data = [
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'sales',
            'tax_type' => $taxType,
            'tax_category' => 'output',
            'reference_id' => $salesOrderId,
            'reference_type' => 'sales_order',
            'customer_id' => $customerId,
            'tax_number' => $customer->npwp ?? null,
            'tax_name' => $customer->name,
            'tax_address' => $customer->address ?? null,
            'taxable_amount' => $taxableAmount,
            'tax_rate' => $taxRate,
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => "Tax from Sales Order #{$salesOrderId}",
        ];

        return $this->createTaxTransaction($data);
    }

    public function processWithholdingTax($transactionId, $transactionType, $entityId, $taxableAmount, $taxType, $entityName, $entityNpwp = null)
    {
        $taxRate = $this->getTaxRate($taxType);

        $data = [
            'transaction_date' => now()->toDateString(),
            'transaction_type' => $transactionType,
            'tax_type' => $taxType,
            'tax_category' => 'withholding',
            'reference_id' => $transactionId,
            'reference_type' => $transactionType,
            'tax_number' => $entityNpwp,
            'tax_name' => $entityName,
            'taxable_amount' => $taxableAmount,
            'tax_rate' => $taxRate,
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => "Withholding tax {$taxType} from {$transactionType} #{$transactionId}",
        ];

        return $this->createTaxTransaction($data);
    }

    public function createTaxPeriod($year, $month, $periodType = 'monthly')
    {
        return DB::transaction(function () use ($year, $month, $periodType) {
            $startDate = now()->setYear($year)->setMonth($month)->startOfMonth()->toDateString();
            $endDate = now()->setYear($year)->setMonth($month)->endOfMonth()->toDateString();

            $period = TaxPeriod::create([
                'year' => $year,
                'month' => $month,
                'period_type' => $periodType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'open',
            ]);

            TaxComplianceLog::logTaxPeriod('created', $period->id, null, $period->toArray());

            return $period;
        });
    }

    public function closeTaxPeriod($periodId)
    {
        return DB::transaction(function () use ($periodId) {
            $period = TaxPeriod::findOrFail($periodId);

            if (!$period->canBeClosed()) {
                throw new \Exception('Tax period cannot be closed in current status');
            }

            $oldValues = $period->toArray();
            $period->close(Auth::id());

            TaxComplianceLog::logTaxPeriod('closed', $periodId, $oldValues, $period->toArray());

            return $period;
        });
    }

    public function generateTaxReport($periodId, $reportType)
    {
        return DB::transaction(function () use ($periodId, $reportType) {
            $period = TaxPeriod::findOrFail($periodId);

            $report = TaxReport::create([
                'tax_period_id' => $periodId,
                'report_type' => $reportType,
                'report_name' => $this->getReportName($reportType),
                'status' => 'draft',
                'due_date' => $this->getReportDueDate($period, $reportType),
                'created_by' => Auth::id(),
            ]);

            // Generate report data
            $reportData = $report->generateReportData();
            $report->update(['report_data' => $reportData]);

            TaxComplianceLog::logTaxReport('created', $report->id, null, $report->toArray());

            return $report;
        });
    }

    public function submitTaxReport($reportId)
    {
        return DB::transaction(function () use ($reportId) {
            $report = TaxReport::findOrFail($reportId);

            if (!$report->canBeSubmitted()) {
                throw new \Exception('Report cannot be submitted in current status');
            }

            $oldValues = $report->toArray();
            $report->submit(Auth::id());

            TaxComplianceLog::logTaxReport('submitted', $reportId, $oldValues, $report->toArray());

            return $report;
        });
    }

    public function approveTaxReport($reportId)
    {
        return DB::transaction(function () use ($reportId) {
            $report = TaxReport::findOrFail($reportId);

            if (!$report->canBeApproved()) {
                throw new \Exception('Report cannot be approved in current status');
            }

            $oldValues = $report->toArray();
            $report->approve();

            TaxComplianceLog::logTaxReport('approved', $reportId, $oldValues, $report->toArray());

            return $report;
        });
    }

    public function getTaxSummary($startDate, $endDate)
    {
        $transactions = TaxTransaction::whereBetween('transaction_date', [$startDate, $endDate])->get();

        return [
            'total_transactions' => $transactions->count(),
            'total_taxable_amount' => $transactions->sum('taxable_amount'),
            'total_tax_amount' => $transactions->sum('tax_amount'),
            'total_amount' => $transactions->sum('total_amount'),
            'ppn_input' => $transactions->where('tax_type', 'ppn')->where('tax_category', 'input')->sum('tax_amount'),
            'ppn_output' => $transactions->where('tax_type', 'ppn')->where('tax_category', 'output')->sum('tax_amount'),
            'ppn_net' => $transactions->where('tax_type', 'ppn')->where('tax_category', 'output')->sum('tax_amount') -
                $transactions->where('tax_type', 'ppn')->where('tax_category', 'input')->sum('tax_amount'),
            'pph_21' => $transactions->where('tax_type', 'pph_21')->sum('tax_amount'),
            'pph_22' => $transactions->where('tax_type', 'pph_22')->sum('tax_amount'),
            'pph_23' => $transactions->where('tax_type', 'pph_23')->sum('tax_amount'),
            'pph_26' => $transactions->where('tax_type', 'pph_26')->sum('tax_amount'),
            'pph_4_2' => $transactions->where('tax_type', 'pph_4_2')->sum('tax_amount'),
            'by_status' => $transactions->groupBy('status')->map->count(),
            'by_tax_type' => $transactions->groupBy('tax_type')->map->count(),
            'by_tax_category' => $transactions->groupBy('tax_category')->map->count(),
        ];
    }

    public function getOverdueTransactions()
    {
        return TaxTransaction::overdue()->with(['vendor', 'customer'])->get();
    }

    public function getOverdueReports()
    {
        return TaxReport::overdue()->with('taxPeriod')->get();
    }

    public function getTaxCalendar($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        // Get tax deadlines for the period
        $deadlines = [];

        // PPN deadlines (20th of next month)
        $ppnDeadline = now()->addMonth()->setDay(20);
        if ($ppnDeadline->between($startDate, $endDate)) {
            $deadlines[] = [
                'date' => $ppnDeadline->toDateString(),
                'event_name' => 'SPT PPN Due',
                'event_type' => 'deadline',
                'tax_type' => 'ppn',
                'description' => 'SPT PPN submission deadline',
            ];
        }

        // PPh 21 deadlines (20th of next month)
        $pph21Deadline = now()->addMonth()->setDay(20);
        if ($pph21Deadline->between($startDate, $endDate)) {
            $deadlines[] = [
                'date' => $pph21Deadline->toDateString(),
                'event_name' => 'SPT PPh 21 Due',
                'event_type' => 'deadline',
                'tax_type' => 'pph_21',
                'description' => 'SPT PPh 21 submission deadline',
            ];
        }

        return $deadlines;
    }

    private function getTaxRate($taxType)
    {
        $taxRates = TaxSetting::getTaxRates();
        return $taxRates[$taxType . '_rate'] ?? 0;
    }

    private function getReportName($reportType)
    {
        $reportNames = [
            'spt_ppn' => 'SPT PPN',
            'spt_pph_21' => 'SPT PPh 21',
            'spt_pph_22' => 'SPT PPh 22',
            'spt_pph_23' => 'SPT PPh 23',
            'spt_pph_26' => 'SPT PPh 26',
            'spt_pph_4_2' => 'SPT PPh 4(2)',
            'spt_tahunan' => 'SPT Tahunan',
        ];

        return $reportNames[$reportType] ?? $reportType;
    }

    private function getReportDueDate($period, $reportType)
    {
        // Most reports are due on the 20th of the following month
        return $period->end_date->addMonth()->setDay(20)->toDateString();
    }

    public static function initializeTaxSystem()
    {
        // Initialize default tax settings
        TaxSetting::initializeDefaultSettings();

        // Create current tax period if it doesn't exist
        $currentPeriod = TaxPeriod::getCurrentPeriod();
        if (!$currentPeriod) {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();

            TaxPeriod::create([
                'year' => now()->year,
                'month' => now()->month,
                'period_type' => 'monthly',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'open',
            ]);
        }

        return true;
    }
}
