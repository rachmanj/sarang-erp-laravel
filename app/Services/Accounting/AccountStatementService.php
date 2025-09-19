<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountStatement;
use App\Models\Accounting\AccountStatementLine;
use App\Models\Accounting\Account;
use App\Models\BusinessPartner;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\Accounting\PurchasePayment;
use App\Models\Accounting\CashExpense;
use App\Models\AssetDisposal;
use App\Models\DeliveryOrder;
use App\Services\DocumentNumberingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountStatementService
{
    public function __construct(
        private DocumentNumberingService $documentNumberingService
    ) {}

    /**
     * Generate account statement for a GL account
     */
    public function generateGlAccountStatement(
        int $accountId,
        Carbon $fromDate,
        Carbon $toDate,
        ?int $projectId = null,
        ?int $deptId = null,
        ?int $userId = null
    ): AccountStatement {
        $account = Account::findOrFail($accountId);

        if (!$account->is_postable) {
            throw new \InvalidArgumentException('Account is not postable and cannot have statements');
        }

        return DB::transaction(function () use ($account, $accountId, $fromDate, $toDate, $projectId, $deptId, $userId) {
            // Create statement header
            $statement = AccountStatement::create([
                'statement_no' => $this->documentNumberingService->generateNumber('account_statement', $fromDate),
                'statement_type' => 'gl_account',
                'account_id' => $accountId,
                'statement_date' => $toDate,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'opening_balance' => $this->calculateOpeningBalance($accountId, $fromDate, $projectId, $deptId),
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            // Generate statement lines
            $this->generateGlAccountStatementLines($statement, $fromDate, $toDate, $projectId, $deptId);

            // Recalculate balances
            $statement->recalculateBalances();

            return $statement;
        });
    }

    /**
     * Generate account statement for a business partner
     */
    public function generateBusinessPartnerStatement(
        int $businessPartnerId,
        Carbon $fromDate,
        Carbon $toDate,
        ?int $projectId = null,
        ?int $deptId = null,
        ?int $userId = null
    ): AccountStatement {
        $businessPartner = BusinessPartner::findOrFail($businessPartnerId);

        return DB::transaction(function () use ($businessPartner, $businessPartnerId, $fromDate, $toDate, $projectId, $deptId, $userId) {
            // Create statement header
            $statement = AccountStatement::create([
                'statement_no' => $this->documentNumberingService->generateNumber('account_statement', $fromDate),
                'statement_type' => 'business_partner',
                'business_partner_id' => $businessPartnerId,
                'statement_date' => $toDate,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'opening_balance' => $this->calculateBusinessPartnerOpeningBalance($businessPartnerId, $fromDate, $projectId, $deptId),
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            // Generate statement lines
            $this->generateBusinessPartnerStatementLines($statement, $fromDate, $toDate, $projectId, $deptId);

            // Recalculate balances
            $statement->recalculateBalances();

            return $statement;
        });
    }

    /**
     * Generate statement lines for GL account
     */
    protected function generateGlAccountStatementLines(
        AccountStatement $statement,
        Carbon $fromDate,
        Carbon $toDate,
        ?int $projectId = null,
        ?int $deptId = null
    ): void {
        $lines = collect();

        // Get journal lines
        $journalLines = JournalLine::where('account_id', $statement->account_id)
            ->whereBetween('created_at', [$fromDate->startOfDay(), $toDate->endOfDay()])
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($deptId, fn($q) => $q->where('dept_id', $deptId))
            ->with(['journal'])
            ->get();

        foreach ($journalLines as $line) {
            $lines->push([
                'account_statement_id' => $statement->id,
                'transaction_date' => $line->journal->date,
                'reference_type' => 'journal',
                'reference_id' => $line->journal_id,
                'reference_no' => $line->journal->journal_no,
                'description' => $line->journal->description ?? "Journal Entry #{$line->journal->journal_no}",
                'debit_amount' => $line->debit,
                'credit_amount' => $line->credit,
                'project_id' => $line->project_id,
                'dept_id' => $line->dept_id,
                'memo' => $line->memo,
                'sort_order' => $lines->count() + 1,
            ]);
        }

        // Insert all lines
        if ($lines->isNotEmpty()) {
            AccountStatementLine::insert($lines->toArray());
        }
    }

    /**
     * Generate statement lines for business partner
     */
    protected function generateBusinessPartnerStatementLines(
        AccountStatement $statement,
        Carbon $fromDate,
        Carbon $toDate,
        ?int $projectId = null,
        ?int $deptId = null
    ): void {
        $lines = collect();

        // Sales Invoices
        $salesInvoices = SalesInvoice::where('business_partner_id', $statement->business_partner_id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        foreach ($salesInvoices as $invoice) {
            $lines->push([
                'account_statement_id' => $statement->id,
                'transaction_date' => $invoice->date,
                'reference_type' => 'sales_invoice',
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => "Sales Invoice #{$invoice->invoice_no}",
                'debit_amount' => $invoice->total_amount,
                'credit_amount' => 0,
                'sort_order' => $lines->count() + 1,
            ]);
        }

        // Purchase Invoices
        $purchaseInvoices = PurchaseInvoice::where('business_partner_id', $statement->business_partner_id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        foreach ($purchaseInvoices as $invoice) {
            $lines->push([
                'account_statement_id' => $statement->id,
                'transaction_date' => $invoice->date,
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => "Purchase Invoice #{$invoice->invoice_no}",
                'debit_amount' => 0,
                'credit_amount' => $invoice->total_amount,
                'sort_order' => $lines->count() + 1,
            ]);
        }

        // Sales Receipts
        $salesReceipts = SalesReceipt::where('business_partner_id', $statement->business_partner_id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        foreach ($salesReceipts as $receipt) {
            $lines->push([
                'account_statement_id' => $statement->id,
                'transaction_date' => $receipt->date,
                'reference_type' => 'sales_receipt',
                'reference_id' => $receipt->id,
                'reference_no' => $receipt->receipt_no,
                'description' => "Sales Receipt #{$receipt->receipt_no}",
                'debit_amount' => 0,
                'credit_amount' => $receipt->total_amount,
                'sort_order' => $lines->count() + 1,
            ]);
        }

        // Purchase Payments
        $purchasePayments = PurchasePayment::where('business_partner_id', $statement->business_partner_id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        foreach ($purchasePayments as $payment) {
            $lines->push([
                'account_statement_id' => $statement->id,
                'transaction_date' => $payment->date,
                'reference_type' => 'purchase_payment',
                'reference_id' => $payment->id,
                'reference_no' => $payment->payment_no,
                'description' => "Purchase Payment #{$payment->payment_no}",
                'debit_amount' => $payment->total_amount,
                'credit_amount' => 0,
                'sort_order' => $lines->count() + 1,
            ]);
        }

        // Sort by date and insert
        $sortedLines = $lines->sortBy('transaction_date')->values();
        if ($sortedLines->isNotEmpty()) {
            AccountStatementLine::insert($sortedLines->toArray());
        }
    }

    /**
     * Calculate opening balance for GL account
     */
    protected function calculateOpeningBalance(int $accountId, Carbon $fromDate, ?int $projectId = null, ?int $deptId = null): float
    {
        $query = JournalLine::where('account_id', $accountId)
            ->where('created_at', '<', $fromDate->startOfDay())
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($deptId, fn($q) => $q->where('dept_id', $deptId));

        $totalDebits = $query->sum('debit');
        $totalCredits = $query->sum('credit');

        return $totalDebits - $totalCredits;
    }

    /**
     * Calculate opening balance for business partner
     */
    protected function calculateBusinessPartnerOpeningBalance(int $businessPartnerId, Carbon $fromDate, ?int $projectId = null, ?int $deptId = null): float
    {
        $openingBalance = 0;

        // Sales Invoices (debit balance)
        $salesInvoices = SalesInvoice::where('business_partner_id', $businessPartnerId)
            ->where('date', '<', $fromDate)
            ->sum('total_amount');

        // Purchase Invoices (credit balance)
        $purchaseInvoices = PurchaseInvoice::where('business_partner_id', $businessPartnerId)
            ->where('date', '<', $fromDate)
            ->sum('total_amount');

        // Sales Receipts (credit balance - reduces AR)
        $salesReceipts = SalesReceipt::where('business_partner_id', $businessPartnerId)
            ->where('date', '<', $fromDate)
            ->sum('total_amount');

        // Purchase Payments (debit balance - reduces AP)
        $purchasePayments = PurchasePayment::where('business_partner_id', $businessPartnerId)
            ->where('date', '<', $fromDate)
            ->sum('total_amount');

        $openingBalance = ($salesInvoices + $purchasePayments) - ($purchaseInvoices + $salesReceipts);

        return $openingBalance;
    }

    /**
     * Get account statement summary
     */
    public function getStatementSummary(AccountStatement $statement): array
    {
        $lines = $statement->lines()->orderBy('transaction_date')->orderBy('sort_order')->get();

        return [
            'total_transactions' => $lines->count(),
            'total_debits' => $lines->sum('debit_amount'),
            'total_credits' => $lines->sum('credit_amount'),
            'net_movement' => $lines->sum('debit_amount') - $lines->sum('credit_amount'),
            'opening_balance' => $statement->opening_balance,
            'closing_balance' => $statement->closing_balance,
            'balance_change' => $statement->closing_balance - $statement->opening_balance,
        ];
    }

    /**
     * Export statement to array for reports
     */
    public function exportStatement(AccountStatement $statement): array
    {
        $lines = $statement->lines()->orderBy('transaction_date')->orderBy('sort_order')->get();

        return [
            'statement' => $statement->toArray(),
            'lines' => $lines->toArray(),
            'summary' => $this->getStatementSummary($statement),
        ];
    }

    /**
     * Delete statement and its lines
     */
    public function deleteStatement(AccountStatement $statement): bool
    {
        if ($statement->status === 'finalized') {
            throw new \InvalidArgumentException('Cannot delete finalized statement');
        }

        return DB::transaction(function () use ($statement) {
            $statement->lines()->delete();
            return $statement->delete();
        });
    }
}
