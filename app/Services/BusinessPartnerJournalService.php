<?php

namespace App\Services;

use App\Models\BusinessPartner;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessPartnerJournalService
{
    protected $businessPartner;
    protected $accountId;

    public function __construct(BusinessPartner $businessPartner)
    {
        $this->businessPartner = $businessPartner;
        $this->accountId = $businessPartner->getAccountOrDefault()?->id;
    }

    /**
     * Get journal history for the business partner
     */
    public function getJournalHistory($startDate = null, $endDate = null, $page = 1, $perPage = 25)
    {
        if (!$this->accountId) {
            return [
                'opening_balance' => 0,
                'closing_balance' => 0,
                'total_debits' => 0,
                'total_credits' => 0,
                'transactions' => [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 0,
                    'total_records' => 0,
                    'per_page' => $perPage
                ]
            ];
        }

        // Default to current year if no dates provided
        if (!$startDate) {
            $startDate = Carbon::now()->startOfYear();
        } else {
            $startDate = Carbon::parse($startDate);
        }

        if (!$endDate) {
            $endDate = Carbon::now()->endOfYear();
        } else {
            $endDate = Carbon::parse($endDate);
        }

        // Get opening balance (balance before start date)
        $openingBalance = $this->getOpeningBalance($startDate);

        // Get all transactions
        $transactions = $this->getTransactions($startDate, $endDate, $page, $perPage);

        // Calculate totals
        $totalDebits = $transactions->sum('debit');
        $totalCredits = $transactions->sum('credit');
        $closingBalance = $openingBalance + $totalDebits - $totalCredits;

        // Calculate running balances
        $runningBalance = $openingBalance;
        $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction->debit - $transaction->credit;
            $transaction->cumulative_balance = $runningBalance;
            return $transaction;
        });

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'transactions' => $transactionsWithBalance,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($this->getTransactionCount($startDate, $endDate) / $perPage),
                'total_records' => $this->getTransactionCount($startDate, $endDate),
                'per_page' => $perPage
            ]
        ];
    }

    /**
     * Get opening balance before the start date
     */
    protected function getOpeningBalance($startDate)
    {
        $query = DB::table('journal_lines')
            ->where('account_id', $this->accountId)
            ->where('posting_date', '<', $startDate->format('Y-m-d'));

        $debits = $query->clone()->sum('debit');
        $credits = $query->clone()->sum('credit');

        return $debits - $credits;
    }

    /**
     * Get transactions for the date range
     */
    protected function getTransactions($startDate, $endDate, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;

        // Union all transaction sources
        $transactions = collect();

        // 1. Journal Lines (direct journal entries)
        $journalLines = DB::table('journal_lines as jl')
            ->join('journals as j', 'jl.journal_id', '=', 'j.id')
            ->join('accounts as a', 'jl.offset_account_id', '=', 'a.id')
            ->leftJoin('projects as p', 'jl.project_id', '=', 'p.id')
            ->leftJoin('departments as d', 'jl.department_id', '=', 'd.id')
            ->leftJoin('users as u', 'j.created_by', '=', 'u.id')
            ->where('jl.account_id', $this->accountId)
            ->whereBetween('jl.posting_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select([
                'jl.posting_date',
                'j.created_at as create_date',
                'jl.posting_date as document_date',
                DB::raw("'Journal Entry' as type"),
                DB::raw("'' as document_no"),
                'j.journal_no',
                'jl.description',
                'a.code as offset_account',
                'a.name as account_name',
                'jl.debit',
                'jl.credit',
                DB::raw("CONCAT(COALESCE(p.code, ''), ' / ', COALESCE(d.name, '')) as project_dept"),
                'u.name as created_by'
            ])
            ->get();

        // 2. Sales Invoice Lines (for customers)
        if ($this->businessPartner->is_customer) {
            $salesInvoiceLines = DB::table('sales_invoice_lines as sil')
                ->join('sales_invoices as si', 'sil.sales_invoice_id', '=', 'si.id')
                ->join('accounts as a', 'sil.account_id', '=', 'a.id')
                ->leftJoin('projects as p', 'sil.project_id', '=', 'p.id')
                ->leftJoin('departments as d', 'sil.department_id', '=', 'd.id')
                ->leftJoin('users as u', 'si.created_by', '=', 'u.id')
                ->where('si.business_partner_id', $this->businessPartner->id)
                ->whereBetween('si.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select([
                    'si.date as posting_date',
                    'si.created_at as create_date',
                    'si.date as document_date',
                    DB::raw("'Sales Invoice' as type"),
                    'si.invoice_no as document_no',
                    DB::raw("'' as journal_no"),
                    'sil.description',
                    'a.code as offset_account',
                    'a.name as account_name',
                    'sil.debit',
                    'sil.credit',
                    DB::raw("CONCAT(COALESCE(p.code, ''), ' / ', COALESCE(d.name, '')) as project_dept"),
                    'u.name as created_by'
                ])
                ->get();

            $transactions = $transactions->merge($salesInvoiceLines);
        }

        // 3. Sales Receipt Lines (for customers)
        if ($this->businessPartner->is_customer) {
            $salesReceiptLines = DB::table('sales_receipt_lines as srl')
                ->join('sales_receipts as sr', 'srl.sales_receipt_id', '=', 'sr.id')
                ->join('accounts as a', 'srl.account_id', '=', 'a.id')
                ->leftJoin('projects as p', 'srl.project_id', '=', 'p.id')
                ->leftJoin('departments as d', 'srl.department_id', '=', 'd.id')
                ->leftJoin('users as u', 'sr.created_by', '=', 'u.id')
                ->where('sr.business_partner_id', $this->businessPartner->id)
                ->whereBetween('sr.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select([
                    'sr.date as posting_date',
                    'sr.created_at as create_date',
                    'sr.date as document_date',
                    DB::raw("'Sales Receipt' as type"),
                    'sr.receipt_no as document_no',
                    DB::raw("'' as journal_no"),
                    'srl.description',
                    'a.code as offset_account',
                    'a.name as account_name',
                    'srl.debit',
                    'srl.credit',
                    DB::raw("CONCAT(COALESCE(p.code, ''), ' / ', COALESCE(d.name, '')) as project_dept"),
                    'u.name as created_by'
                ])
                ->get();

            $transactions = $transactions->merge($salesReceiptLines);
        }

        // 4. Purchase Invoice Lines (for suppliers)
        if ($this->businessPartner->is_supplier) {
            $purchaseInvoiceLines = DB::table('purchase_invoice_lines as pil')
                ->join('purchase_invoices as pi', 'pil.purchase_invoice_id', '=', 'pi.id')
                ->join('accounts as a', 'pil.account_id', '=', 'a.id')
                ->leftJoin('projects as p', 'pil.project_id', '=', 'p.id')
                ->leftJoin('departments as d', 'pil.department_id', '=', 'd.id')
                ->leftJoin('users as u', 'pi.created_by', '=', 'u.id')
                ->where('pi.business_partner_id', $this->businessPartner->id)
                ->whereBetween('pi.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select([
                    'pi.date as posting_date',
                    'pi.created_at as create_date',
                    'pi.date as document_date',
                    DB::raw("'Purchase Invoice' as type"),
                    'pi.invoice_no as document_no',
                    DB::raw("'' as journal_no"),
                    'pil.description',
                    'a.code as offset_account',
                    'a.name as account_name',
                    'pil.debit',
                    'pil.credit',
                    DB::raw("CONCAT(COALESCE(p.code, ''), ' / ', COALESCE(d.name, '')) as project_dept"),
                    'u.name as created_by'
                ])
                ->get();

            $transactions = $transactions->merge($purchaseInvoiceLines);
        }

        // 5. Purchase Payment Lines (for suppliers)
        if ($this->businessPartner->is_supplier) {
            $purchasePaymentLines = DB::table('purchase_payment_lines as ppl')
                ->join('purchase_payments as pp', 'ppl.purchase_payment_id', '=', 'pp.id')
                ->join('accounts as a', 'ppl.account_id', '=', 'a.id')
                ->leftJoin('projects as p', 'ppl.project_id', '=', 'p.id')
                ->leftJoin('departments as d', 'ppl.department_id', '=', 'd.id')
                ->leftJoin('users as u', 'pp.created_by', '=', 'u.id')
                ->where('pp.business_partner_id', $this->businessPartner->id)
                ->whereBetween('pp.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select([
                    'pp.date as posting_date',
                    'pp.created_at as create_date',
                    'pp.date as document_date',
                    DB::raw("'Purchase Payment' as type"),
                    'pp.payment_no as document_no',
                    DB::raw("'' as journal_no"),
                    'ppl.description',
                    'a.code as offset_account',
                    'a.name as account_name',
                    'ppl.debit',
                    'ppl.credit',
                    DB::raw("CONCAT(COALESCE(p.code, ''), ' / ', COALESCE(d.name, '')) as project_dept"),
                    'u.name as created_by'
                ])
                ->get();

            $transactions = $transactions->merge($purchasePaymentLines);
        }

        // Merge journal lines
        $transactions = $transactions->merge($journalLines);

        // Sort by posting date and created date
        $transactions = $transactions->sortBy([
            ['posting_date', 'asc'],
            ['create_date', 'asc']
        ]);

        // Apply pagination
        $totalRecords = $transactions->count();
        $transactions = $transactions->slice($offset, $perPage);

        return $transactions;
    }

    /**
     * Get total transaction count for pagination
     */
    protected function getTransactionCount($startDate, $endDate)
    {
        $count = 0;

        // Count journal lines
        $count += DB::table('journal_lines')
            ->where('account_id', $this->accountId)
            ->whereBetween('posting_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->count();

        // Count sales transactions (for customers)
        if ($this->businessPartner->is_customer) {
            $count += DB::table('sales_invoice_lines as sil')
                ->join('sales_invoices as si', 'sil.sales_invoice_id', '=', 'si.id')
                ->where('si.business_partner_id', $this->businessPartner->id)
                ->whereBetween('si.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();

            $count += DB::table('sales_receipt_lines as srl')
                ->join('sales_receipts as sr', 'srl.sales_receipt_id', '=', 'sr.id')
                ->where('sr.business_partner_id', $this->businessPartner->id)
                ->whereBetween('sr.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
        }

        // Count purchase transactions (for suppliers)
        if ($this->businessPartner->is_supplier) {
            $count += DB::table('purchase_invoice_lines as pil')
                ->join('purchase_invoices as pi', 'pil.purchase_invoice_id', '=', 'pi.id')
                ->where('pi.business_partner_id', $this->businessPartner->id)
                ->whereBetween('pi.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();

            $count += DB::table('purchase_payment_lines as ppl')
                ->join('purchase_payments as pp', 'ppl.purchase_payment_id', '=', 'pp.id')
                ->where('pp.business_partner_id', $this->businessPartner->id)
                ->whereBetween('pp.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->count();
        }

        return $count;
    }
}
