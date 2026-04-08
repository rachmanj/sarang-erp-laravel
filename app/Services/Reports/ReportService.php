<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getTrialBalance(?string $date = null, bool $onlyPostedJournals = true): array
    {
        $date = $date ?: now()->toDateString();

        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id')
            ->whereDate('j.date', '<=', $date);
        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }
        $lines = $query
            ->selectRaw('a.id, a.code, a.name, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit, 
                        GROUP_CONCAT(DISTINCT c.code ORDER BY c.code SEPARATOR ", ") as currencies')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        $rows = $lines->map(function ($r) {
            $balance = (float) $r->debit - (float) $r->credit;

            return [
                'account_id' => (int) $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'type' => $r->type,
                'debit' => (float) $r->debit,
                'credit' => (float) $r->credit,
                'balance' => $balance,
                'currencies' => $r->currencies ?: 'IDR', // Default to IDR if no currency info
            ];
        })->toArray();

        return [
            'as_of' => $date,
            'rows' => $rows,
            'totals' => [
                'debit' => array_sum(array_column($rows, 'debit')),
                'credit' => array_sum(array_column($rows, 'credit')),
            ],
        ];
    }

    public function getGlDetail(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id')
            ->select(
                'j.date',
                'j.description as journal_desc',
                'a.code as account_code',
                'a.name as account_name',
                'jl.debit',
                'jl.credit',
                'jl.memo',
                'c.code as currency_code',
                'jl.exchange_rate',
                'jl.debit_foreign',
                'jl.credit_foreign'
            );
        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }

        if (! empty($filters['account_id'])) {
            $query->where('a.id', $filters['account_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('j.date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('j.date', '<=', $filters['to']);
        }
        if (! empty($filters['project_id'])) {
            $query->where('jl.project_id', $filters['project_id']);
        }
        if (! empty($filters['fund_id'])) {
            $query->where('jl.fund_id', $filters['fund_id']);
        }
        if (! empty($filters['dept_id'])) {
            $query->where('jl.dept_id', $filters['dept_id']);
        }

        $rows = $query->orderBy('j.date')->orderBy('j.id')->get()->toArray();

        return [
            'filters' => $filters,
            'rows' => array_map(function ($r) {
                return [
                    'date' => $r->date,
                    'journal_desc' => $r->journal_desc,
                    'account_code' => $r->account_code,
                    'account_name' => $r->account_name,
                    'debit' => (float) $r->debit,
                    'credit' => (float) $r->credit,
                    'memo' => $r->memo,
                    'currency_code' => $r->currency_code ?: 'IDR',
                    'exchange_rate' => (float) $r->exchange_rate ?: 1.000000,
                    'debit_foreign' => (float) $r->debit_foreign,
                    'credit_foreign' => (float) $r->credit_foreign,
                ];
            }, $rows),
        ];
    }

    public function getBalanceSheet(?string $asOf = null, bool $onlyPostedJournals = true, bool $hideZeroLines = true): array
    {
        $date = $asOf ?: now()->toDateString();

        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereDate('j.date', '<=', $date)
            ->whereIn('a.type', ['asset', 'liability', 'net_assets']);
        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }
        $lines = $query
            ->selectRaw('a.id, a.code, a.name, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $totalEquity = 0.0;
        $ownById = [];
        foreach ($lines as $r) {
            $amount = $this->balanceSheetDisplayAmount($r->type, (float) $r->debit, (float) $r->credit);
            $ownById[(int) $r->id] = round($amount, 2);
            if ($r->type === 'asset') {
                $totalAssets += $amount;
            } elseif ($r->type === 'liability') {
                $totalLiabilities += $amount;
            } else {
                $totalEquity += $amount;
            }
        }
        $totalAssets = round($totalAssets, 2);
        $totalLiabilities = round($totalLiabilities, 2);
        $totalEquity = round($totalEquity, 2);

        $coaAccounts = DB::table('accounts')
            ->whereIn('type', ['asset', 'liability', 'net_assets'])
            ->orderBy('code')
            ->get(['id', 'parent_id', 'code', 'name', 'is_postable', 'type']);

        foreach ($coaAccounts as $a) {
            if (! array_key_exists((int) $a->id, $ownById)) {
                $ownById[(int) $a->id] = 0.0;
            }
        }

        $assets = $this->buildBalanceSheetHierarchyForType('asset', $coaAccounts, $ownById, $hideZeroLines);
        $liabilities = $this->buildBalanceSheetHierarchyForType('liability', $coaAccounts, $ownById, $hideZeroLines);
        $equity = $this->buildBalanceSheetHierarchyForType('net_assets', $coaAccounts, $ownById, $hideZeroLines);

        $difference = round($totalAssets - $totalLiabilities - $totalEquity, 2);

        $unclosedPnl = $this->cumulativeProfitLossDisplayTotal($date, $onlyPostedJournals);
        $differenceVsUnclosedPnl = round($difference - $unclosedPnl, 2);

        return [
            'as_of' => $date,
            'report_title' => 'Balance Sheet',
            'entity_name' => (string) config('app.name'),
            'sections' => [
                [
                    'key' => 'assets',
                    'label' => 'Assets',
                    'rows' => $assets,
                    'total' => $totalAssets,
                ],
                [
                    'key' => 'liabilities',
                    'label' => 'Liabilities',
                    'rows' => $liabilities,
                    'total' => $totalLiabilities,
                ],
                [
                    'key' => 'equity',
                    'label' => 'Equity / Net assets',
                    'rows' => $equity,
                    'total' => $totalEquity,
                ],
            ],
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
                'difference' => $difference,
                'unclosed_pnl_cumulative' => $unclosedPnl,
                'difference_vs_unclosed_pnl' => $differenceVsUnclosedPnl,
            ],
            'only_posted_journals' => $onlyPostedJournals,
            'hide_zero_lines' => $hideZeroLines,
        ];
    }

    public function getProfitAndLoss(array $filters = [], bool $onlyPostedJournals = true, bool $hideZeroLines = true): array
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.type', ['income', 'expense'])
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to);
        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }
        if (! empty($filters['project_id'])) {
            $query->where('jl.project_id', $filters['project_id']);
        }
        if (! empty($filters['fund_id'])) {
            $query->where('jl.fund_id', $filters['fund_id']);
        }
        if (! empty($filters['dept_id'])) {
            $query->where('jl.dept_id', $filters['dept_id']);
        }

        $lines = $query
            ->selectRaw('a.id, a.code, a.name, a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get();

        $ownById = [];
        foreach ($lines as $r) {
            $debit = (float) $r->debit;
            $credit = (float) $r->credit;
            $ownById[(int) $r->id] = round($this->profitLossRowAmount($r->type, $debit, $credit), 2);
        }

        $coaAccounts = DB::table('accounts')
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get(['id', 'parent_id', 'code', 'name', 'is_postable', 'type']);
        $accountById = [];
        foreach ($coaAccounts as $a) {
            $accountById[(int) $a->id] = $a;
            if (! array_key_exists((int) $a->id, $ownById)) {
                $ownById[(int) $a->id] = 0.0;
            }
        }

        $bucketKeys = ['revenue', 'cogs', 'operating', 'other_income', 'other_expense'];
        $bucketLabels = [
            'revenue' => 'Revenue (4.x)',
            'cogs' => 'Cost of sales / HPP & direct (5.x)',
            'operating' => 'Operating expenses (6.x)',
            'other_income' => 'Other income (7.x — income)',
            'other_expense' => 'Other expense (7.x — expense)',
        ];

        $totalRevenue = 0.0;
        $totalCogs = 0.0;
        $totalOperating = 0.0;
        $totalOtherIncome = 0.0;
        $totalOtherExpense = 0.0;
        foreach ($lines as $r) {
            $amt = round($this->profitLossRowAmount($r->type, (float) $r->debit, (float) $r->credit), 2);
            if ($hideZeroLines && abs($amt) < 0.0005) {
                continue;
            }
            match ($this->profitLossBucket($r->code, $r->type)) {
                'revenue' => $totalRevenue += $amt,
                'cogs' => $totalCogs += $amt,
                'operating' => $totalOperating += $amt,
                'other_income' => $totalOtherIncome += $amt,
                'other_expense' => $totalOtherExpense += $amt,
            };
        }
        $totalRevenue = round($totalRevenue, 2);
        $totalCogs = round($totalCogs, 2);
        $totalOperating = round($totalOperating, 2);
        $totalOtherIncome = round($totalOtherIncome, 2);
        $totalOtherExpense = round($totalOtherExpense, 2);

        $buckets = [];
        foreach ($bucketKeys as $bucketKey) {
            $buckets[$bucketKey] = $this->buildProfitLossHierarchyForBucket(
                $bucketKey,
                $lines,
                $accountById,
                $ownById,
                $hideZeroLines
            );
        }

        $grossProfit = round($totalRevenue - $totalCogs, 2);
        $operatingIncome = round($grossProfit - $totalOperating, 2);
        $netIncome = round($operatingIncome + $totalOtherIncome - $totalOtherExpense, 2);

        $sections = [];
        foreach ($bucketKeys as $key) {
            $sections[] = [
                'key' => $key,
                'label' => $bucketLabels[$key],
                'rows' => $buckets[$key],
                'total' => match ($key) {
                    'revenue' => $totalRevenue,
                    'cogs' => $totalCogs,
                    'operating' => $totalOperating,
                    'other_income' => $totalOtherIncome,
                    'other_expense' => $totalOtherExpense,
                },
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'report_title' => 'Profit & Loss Statement',
            'entity_name' => (string) config('app.name'),
            'sections' => $sections,
            'subtotals' => [
                'gross_profit' => $grossProfit,
                'operating_income' => $operatingIncome,
                'net_income' => $netIncome,
            ],
            'only_posted_journals' => $onlyPostedJournals,
            'hide_zero_lines' => $hideZeroLines,
        ];
    }

    public function getCashFlowStatement(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $begin = \Carbon\Carbon::parse($from)->subDay()->toDateString();

        $px = config('cash_flow.account_prefixes', []);

        $pl = $this->getProfitAndLoss(['from' => $from, 'to' => $to], $onlyPostedJournals, false);
        $netIncome = $pl['subtotals']['net_income'];

        $depreciationAddBack = $this->periodDepreciationExpenseAmount($from, $to, $onlyPostedJournals);

        $arBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $px['receivables'] ?? ['1.1.2'], $onlyPostedJournals);
        $arEnd = $this->aggregateDisplayBalanceByPrefixes($to, $px['receivables'] ?? ['1.1.2'], $onlyPostedJournals);
        $changeAr = round($arEnd - $arBegin, 2);
        $wcAr = round(-$changeAr, 2);

        $invBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $px['inventory'] ?? ['1.1.3'], $onlyPostedJournals);
        $invEnd = $this->aggregateDisplayBalanceByPrefixes($to, $px['inventory'] ?? ['1.1.3'], $onlyPostedJournals);
        $wcInv = round(-($invEnd - $invBegin), 2);

        $prePrefixes = $px['prepaid'] ?? ['1.1.5', '1.1.7'];
        $preBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $prePrefixes, $onlyPostedJournals);
        $preEnd = $this->aggregateDisplayBalanceByPrefixes($to, $prePrefixes, $onlyPostedJournals);
        $wcPrepaid = round(-($preEnd - $preBegin), 2);

        $apBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $px['payables'] ?? ['2.1.1'], $onlyPostedJournals);
        $apEnd = $this->aggregateDisplayBalanceByPrefixes($to, $px['payables'] ?? ['2.1.1'], $onlyPostedJournals);
        $wcAp = round($apEnd - $apBegin, 2);

        $accrBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $px['accrued_liabilities'] ?? ['2.1.4'], $onlyPostedJournals);
        $accrEnd = $this->aggregateDisplayBalanceByPrefixes($to, $px['accrued_liabilities'] ?? ['2.1.4'], $onlyPostedJournals);
        $wcAccr = round($accrEnd - $accrBegin, 2);

        $taxPx = $px['tax_payables'] ?? [];
        $taxBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $taxPx, $onlyPostedJournals);
        $taxEnd = $this->aggregateDisplayBalanceByPrefixes($to, $taxPx, $onlyPostedJournals);
        $wcTaxPayables = round($taxEnd - $taxBegin, 2);

        $vatPx = $px['input_vat_prepaid_assets'] ?? [];
        $vatBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $vatPx, $onlyPostedJournals);
        $vatEnd = $this->aggregateDisplayBalanceByPrefixes($to, $vatPx, $onlyPostedJournals);
        $wcInputVatPrepaid = round(-($vatEnd - $vatBegin), 2);

        $operatingLines = [
            ['key' => 'net_income', 'label' => 'Net income', 'amount' => $netIncome],
            ['key' => 'depreciation', 'label' => 'Depreciation & amortization (add back)', 'amount' => $depreciationAddBack],
            ['key' => 'wc_ar', 'label' => 'Change in receivables (configured prefixes)', 'amount' => $wcAr],
            ['key' => 'wc_inventory', 'label' => 'Change in inventory (configured prefixes)', 'amount' => $wcInv],
            ['key' => 'wc_prepaid', 'label' => 'Change in prepaid (configured prefixes)', 'amount' => $wcPrepaid],
            ['key' => 'wc_ap', 'label' => 'Change in payables (configured prefixes)', 'amount' => $wcAp],
            ['key' => 'wc_accruals', 'label' => 'Change in accrued liabilities (configured prefixes)', 'amount' => $wcAccr],
            ['key' => 'wc_tax_payables', 'label' => 'Change in tax payables (configured prefixes)', 'amount' => $wcTaxPayables],
            ['key' => 'wc_input_vat_prepaid', 'label' => 'Change in input VAT / prepaid tax assets (configured prefixes)', 'amount' => $wcInputVatPrepaid],
        ];
        $operatingSubtotal = round(array_sum(array_column($operatingLines, 'amount')), 2);

        $ncaPx = $px['non_current_assets'] ?? ['1.2'];
        $ncaBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $ncaPx, $onlyPostedJournals);
        $ncaEnd = $this->aggregateDisplayBalanceByPrefixes($to, $ncaPx, $onlyPostedJournals);
        $investingAmount = round(-($ncaEnd - $ncaBegin), 2);
        $investingLines = [
            ['key' => 'nc_assets', 'label' => 'Net change in non-current assets (configured prefixes)', 'amount' => $investingAmount],
        ];
        $investingSubtotal = $investingAmount;

        $stBorrowPx = $px['short_term_borrowings'] ?? ['2.1.3'];
        $stBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $stBorrowPx, $onlyPostedJournals);
        $stEnd = $this->aggregateDisplayBalanceByPrefixes($to, $stBorrowPx, $onlyPostedJournals);
        $finShortTermBorrowings = round($stEnd - $stBegin, 2);

        $ltPx = $px['long_term_liabilities'] ?? ['2.2'];
        $ltBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $ltPx, $onlyPostedJournals);
        $ltEnd = $this->aggregateDisplayBalanceByPrefixes($to, $ltPx, $onlyPostedJournals);
        $finLongTerm = round($ltEnd - $ltBegin, 2);

        $eqFinPx = $px['equity_financing_prefixes'] ?? ['3.1', '3.2'];
        $eqFinBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $eqFinPx, $onlyPostedJournals);
        $eqFinEnd = $this->aggregateDisplayBalanceByPrefixes($to, $eqFinPx, $onlyPostedJournals);
        $finEquityCapital = round($eqFinEnd - $eqFinBegin, 2);

        $financingLines = [
            ['key' => 'short_term_borrowings', 'label' => 'Net change in short-term borrowings (configured prefixes)', 'amount' => $finShortTermBorrowings],
            ['key' => 'lt_liabilities', 'label' => 'Net change in long-term liabilities (configured prefixes)', 'amount' => $finLongTerm],
            ['key' => 'equity_capital', 'label' => 'Net change in share capital / premium (equity_financing_prefixes; excludes 3.3 by default)', 'amount' => $finEquityCapital],
        ];
        $financingSubtotal = round(array_sum(array_column($financingLines, 'amount')), 2);

        $netChangeComputed = round($operatingSubtotal + $investingSubtotal + $financingSubtotal, 2);

        $cashPx = $px['cash_and_bank'] ?? ['1.1.1'];
        $cashBegin = $this->aggregateDisplayBalanceByPrefixes($begin, $cashPx, $onlyPostedJournals);
        $cashEnd = $this->aggregateDisplayBalanceByPrefixes($to, $cashPx, $onlyPostedJournals);
        $netChangeCash = round($cashEnd - $cashBegin, 2);
        $reconciliationDifference = round($netChangeComputed - $netChangeCash, 2);

        return [
            'from' => $from,
            'to' => $to,
            'begin_balance_date' => $begin,
            'method' => 'indirect',
            'operating' => [
                'label' => 'Cash flows from operating activities',
                'lines' => $operatingLines,
                'subtotal' => $operatingSubtotal,
            ],
            'investing' => [
                'label' => 'Cash flows from investing activities',
                'lines' => $investingLines,
                'subtotal' => $investingSubtotal,
            ],
            'financing' => [
                'label' => 'Cash flows from financing activities',
                'lines' => $financingLines,
                'subtotal' => $financingSubtotal,
            ],
            'summary' => [
                'net_change_computed' => $netChangeComputed,
                'cash_begin_display' => $cashBegin,
                'cash_end_display' => $cashEnd,
                'net_change_cash_accounts' => $netChangeCash,
                'reconciliation_difference' => $reconciliationDifference,
            ],
            'only_posted_journals' => $onlyPostedJournals,
            'prefix_config_key' => 'cash_flow.account_prefixes',
            'notes' => [
                'Account prefixes are read from config/cash_flow.php (adjust for nonprofit or custom COA).',
                'Investing uses configured non-current asset prefixes; financing uses short-term borrowings, long-term liabilities, and equity_financing_prefixes (capital / premium — not 3.3 retained earnings by default).',
                'Tax payables and input VAT / prepaid tax use optional prefix lists (tax_payables, input_vat_prepaid_assets); set to [] if your COA maps these elsewhere.',
                'Non-zero reconciliation usually means unmapped working capital, dividends or RE movements via 3.3, intercompany, FX, or bank accounts outside cash_and_bank prefixes.',
            ],
        ];
    }

    public function getArAging(?string $asOf = null, array $options = []): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $invoices = DB::table('sales_invoices as si')
            ->leftJoin('business_partners as c', 'c.id', '=', 'si.business_partner_id')
            ->where('si.status', 'posted')
            ->whereDate(DB::raw('COALESCE(si.due_date, si.date)'), '<=', $asOfDate)
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->leftJoin('sales_receipts as sr', function ($join) {
                $join->on('sr.id', '=', 'sra.receipt_id')->where('sr.status', '=', 'posted');
            })
            ->select('si.id', 'si.business_partner_id as customer_id', DB::raw('COALESCE(si.due_date, si.date) as effective_date'), 'si.total_amount', DB::raw('COALESCE(SUM(sra.amount),0) as settled_amount'), 'c.name as customer_name')
            ->groupBy('si.id', 'si.business_partner_id', 'effective_date', 'si.total_amount', 'c.name')
            ->get();

        $buckets = [];
        foreach ($invoices as $inv) {
            $days = \Carbon\Carbon::parse($inv->effective_date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
            $bucket = $this->bucketLabel($days);
            $key = (int) $inv->customer_id;
            if (! isset($buckets[$key])) {
                $buckets[$key] = ['customer_id' => $key, 'customer_name' => $inv->customer_name, 'current' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd91_plus' => 0, 'total' => 0];
            }
            $net = max(0, (float) $inv->total_amount - (float) $inv->settled_amount);
            if ($net <= 0) {
                continue;
            }
            switch ($bucket) {
                case 'current':
                    $buckets[$key]['current'] += $net;
                    break;
                case '31-60':
                    $buckets[$key]['d31_60'] += $net;
                    break;
                case '61-90':
                    $buckets[$key]['d61_90'] += $net;
                    break;
                default:
                    $buckets[$key]['d91_plus'] += $net;
                    break;
            }
            $buckets[$key]['total'] += $net;
        }

        $rows = array_values($buckets);
        if (! empty($options['overdue_only'])) {
            $rows = array_values(array_filter($rows, function ($r) {
                return ($r['d31_60'] + $r['d61_90'] + $r['d91_plus']) > 0;
            }));
        }

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => [
                'current' => array_sum(array_column($rows, 'current')),
                'd31_60' => array_sum(array_column($rows, 'd31_60')),
                'd61_90' => array_sum(array_column($rows, 'd61_90')),
                'd91_plus' => array_sum(array_column($rows, 'd91_plus')),
                'total' => array_sum(array_column($rows, 'total')),
            ],
        ];
    }

    public function getApAging(?string $asOf = null, array $options = []): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $invoices = DB::table('purchase_invoices as pi')
            ->leftJoin('vendors as v', 'v.id', '=', 'pi.vendor_id')
            ->where('status', 'posted')
            ->whereDate(DB::raw('COALESCE(pi.due_date, pi.date)'), '<=', $asOfDate)
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->leftJoin('purchase_payments as pp', function ($join) {
                $join->on('pp.id', '=', 'ppa.payment_id')->where('pp.status', '=', 'posted');
            })
            ->select('pi.id', 'pi.vendor_id', DB::raw('COALESCE(pi.due_date, pi.date) as effective_date'), 'pi.total_amount', DB::raw('COALESCE(SUM(ppa.amount),0) as settled_amount'), 'v.name as vendor_name')
            ->groupBy('pi.id', 'pi.vendor_id', 'effective_date', 'pi.total_amount', 'v.name')
            ->get();

        $buckets = [];
        foreach ($invoices as $inv) {
            $days = \Carbon\Carbon::parse($inv->effective_date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
            $bucket = $this->bucketLabel($days);
            $key = (int) $inv->vendor_id;
            if (! isset($buckets[$key])) {
                $buckets[$key] = ['vendor_id' => $key, 'vendor_name' => $inv->vendor_name, 'current' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd91_plus' => 0, 'total' => 0];
            }
            $net = max(0, (float) $inv->total_amount - (float) $inv->settled_amount);
            if ($net <= 0) {
                continue;
            }
            switch ($bucket) {
                case 'current':
                    $buckets[$key]['current'] += $net;
                    break;
                case '31-60':
                    $buckets[$key]['d31_60'] += $net;
                    break;
                case '61-90':
                    $buckets[$key]['d61_90'] += $net;
                    break;
                default:
                    $buckets[$key]['d91_plus'] += $net;
                    break;
            }
            $buckets[$key]['total'] += $net;
        }

        $rows = array_values($buckets);
        if (! empty($options['overdue_only'])) {
            $rows = array_values(array_filter($rows, function ($r) {
                return ($r['d31_60'] + $r['d61_90'] + $r['d91_plus']) > 0;
            }));
        }

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => [
                'current' => array_sum(array_column($rows, 'current')),
                'd31_60' => array_sum(array_column($rows, 'd31_60')),
                'd61_90' => array_sum(array_column($rows, 'd61_90')),
                'd91_plus' => array_sum(array_column($rows, 'd91_plus')),
                'total' => array_sum(array_column($rows, 'total')),
            ],
        ];
    }

    public function getCashLedger(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $accountId = ! empty($filters['account_id']) ? (int) $filters['account_id'] : (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $q = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->select('j.date', 'j.description', 'jl.debit', 'jl.credit')
            ->where('jl.account_id', $accountId);
        if ($onlyPostedJournals) {
            $q->whereNotNull('j.posted_at');
        }
        if (! empty($filters['from'])) {
            $q->whereDate('j.date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('j.date', '<=', $filters['to']);
        }
        $rows = $q->orderBy('j.date')->orderBy('j.id')->get()->toArray();
        $opening = 0.0;
        if (! empty($filters['from'])) {
            $openQ = DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->where('jl.account_id', $accountId)
                ->whereDate('j.date', '<', $filters['from']);
            if ($onlyPostedJournals) {
                $openQ->whereNotNull('j.posted_at');
            }
            $opening = (float) $openQ->selectRaw('COALESCE(SUM(jl.debit - jl.credit),0) as bal')
                ->value('bal');
        }
        $balance = $opening;
        $out = [];
        if ($opening !== 0.0) {
            $out[] = [
                'date' => $filters['from'] ?? '',
                'description' => 'Opening Balance',
                'debit' => 0.0,
                'credit' => 0.0,
                'balance' => round($opening, 2),
            ];
        }
        foreach ($rows as $r) {
            $balance += (float) $r->debit - (float) $r->credit;
            $out[] = [
                'date' => $r->date,
                'description' => $r->description,
                'debit' => (float) $r->debit,
                'credit' => (float) $r->credit,
                'balance' => round($balance, 2),
            ];
        }

        return ['rows' => $out, 'filters' => $filters, 'account_id' => $accountId, 'opening_balance' => round($opening, 2)];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>|array<int, object>  $coaAccounts
     * @return array<int, array<string, mixed>>
     */
    private function buildBalanceSheetHierarchyForType(string $type, $coaAccounts, array $ownById, bool $hideZeroLines): array
    {
        $subset = [];
        foreach ($coaAccounts as $a) {
            if ($a->type === $type) {
                $subset[] = $a;
            }
        }
        if ($subset === []) {
            return [];
        }
        $idSet = [];
        foreach ($subset as $a) {
            $idSet[(int) $a->id] = true;
        }

        return $this->buildHierarchyRowsFromAccountSubset($subset, $idSet, $ownById, $hideZeroLines);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $lines
     * @param  array<int, object>  $accountById
     * @return array<int, array<string, mixed>>
     */
    private function buildProfitLossHierarchyForBucket(
        string $bucketKey,
        $lines,
        array $accountById,
        array $ownById,
        bool $hideZeroLines
    ): array {
        $idsInBucket = [];
        foreach ($lines as $r) {
            $amt = $ownById[(int) $r->id] ?? 0.0;
            if ($hideZeroLines && abs($amt) < 0.0005) {
                continue;
            }
            if ($this->profitLossBucket($r->code, $r->type) !== $bucketKey) {
                continue;
            }
            $idsInBucket[] = (int) $r->id;
        }
        $expanded = [];
        foreach ($idsInBucket as $id) {
            $cur = $id;
            while ($cur && isset($accountById[$cur])) {
                $acc = $accountById[$cur];
                if ($this->profitLossBucket($acc->code, $acc->type) !== $bucketKey) {
                    break;
                }
                $expanded[$cur] = true;
                $pid = $acc->parent_id ? (int) $acc->parent_id : 0;
                $cur = $pid ?: 0;
            }
        }
        if ($expanded === []) {
            return [];
        }
        $subset = [];
        foreach (array_keys($expanded) as $vid) {
            if (isset($accountById[$vid])) {
                $subset[] = $accountById[$vid];
            }
        }
        $idSet = $expanded;

        return $this->buildHierarchyRowsFromAccountSubset($subset, $idSet, $ownById, $hideZeroLines);
    }

    /**
     * @param  array<int, object>  $subset
     * @param  array<int, bool>  $idSet
     * @param  array<int, float>  $ownById
     * @return array<int, array<string, mixed>>
     */
    private function buildHierarchyRowsFromAccountSubset(array $subset, array $idSet, array $ownById, bool $hideZeroLines): array
    {
        $byId = [];
        foreach ($subset as $a) {
            $byId[(int) $a->id] = $a;
        }
        $children = [];
        foreach ($subset as $a) {
            $id = (int) $a->id;
            $pid = $a->parent_id ? (int) $a->parent_id : null;
            if ($pid && isset($idSet[$pid])) {
                $children[$pid][] = $id;
            }
        }
        foreach (array_keys($children) as $pid) {
            usort($children[$pid], function (int $x, int $y) use ($byId) {
                return strcmp($byId[$x]->code, $byId[$y]->code);
            });
        }
        $own = [];
        foreach (array_keys($idSet) as $iid) {
            $own[(int) $iid] = $ownById[(int) $iid] ?? 0.0;
        }
        $rollup = $this->computeRollupForForest($idSet, $children, $own);
        $visible = $this->computeVisibilityForForest(array_keys($idSet), $children, $rollup, $hideZeroLines);
        $roots = [];
        foreach ($subset as $a) {
            $id = (int) $a->id;
            $pid = $a->parent_id ? (int) $a->parent_id : null;
            if (! $pid || ! isset($idSet[$pid])) {
                $roots[] = $id;
            }
        }
        usort($roots, function (int $x, int $y) use ($byId) {
            return strcmp($byId[$x]->code, $byId[$y]->code);
        });
        $rows = [];
        foreach ($roots as $rid) {
            $rows = array_merge($rows, $this->dfsHierarchyRows($rid, 0, $children, $rollup, $visible, $byId));
        }

        return $rows;
    }

    /**
     * @param  array<int, bool>  $idSet
     * @param  array<int, array<int>>  $children
     * @param  array<int, float>  $own
     * @return array<int, float>
     */
    private function computeRollupForForest(array $idSet, array $children, array $own): array
    {
        $memo = [];
        $go = function (int $id) use (&$go, &$memo, $children, $own): float {
            if (isset($memo[$id])) {
                return $memo[$id];
            }
            $sum = $own[$id] ?? 0.0;
            foreach ($children[$id] ?? [] as $cid) {
                $sum += $go($cid);
            }

            return $memo[$id] = round($sum, 2);
        };
        foreach (array_keys($idSet) as $id) {
            $go((int) $id);
        }

        return $memo;
    }

    /**
     * @param  array<int>  $allIds
     * @param  array<int, array<int>>  $children
     * @param  array<int, float>  $rollup
     * @return array<int, bool>
     */
    private function computeVisibilityForForest(array $allIds, array $children, array $rollup, bool $hideZeroLines): array
    {
        $vis = [];
        $check = function (int $id) use (&$check, &$vis, $children, $rollup, $hideZeroLines): bool {
            if (isset($vis[$id])) {
                return $vis[$id];
            }
            if (! $hideZeroLines) {
                return $vis[$id] = true;
            }
            if (abs($rollup[$id] ?? 0.0) >= 0.0005) {
                return $vis[$id] = true;
            }
            foreach ($children[$id] ?? [] as $cid) {
                if ($check($cid)) {
                    return $vis[$id] = true;
                }
            }

            return $vis[$id] = false;
        };
        foreach ($allIds as $id) {
            $check((int) $id);
        }

        return $vis;
    }

    /**
     * @param  array<int, array<int>>  $children
     * @param  array<int, float>  $rollup
     * @param  array<int, bool>  $visible
     * @param  array<int, object>  $byId
     * @return array<int, array<string, mixed>>
     */
    private function dfsHierarchyRows(
        int $id,
        int $depth,
        array $children,
        array $rollup,
        array $visible,
        array $byId
    ): array {
        if (! ($visible[$id] ?? false)) {
            return [];
        }
        $a = $byId[$id];
        $isParent = ! empty($children[$id]);
        $row = [
            'account_id' => $id,
            'code' => $a->code,
            'name' => $a->name,
            'amount' => $rollup[$id] ?? 0.0,
            'depth' => $depth,
            'is_parent' => $isParent,
            'is_postable' => (bool) $a->is_postable,
        ];
        $out = [$row];
        foreach ($children[$id] ?? [] as $cid) {
            $out = array_merge($out, $this->dfsHierarchyRows($cid, $depth + 1, $children, $rollup, $visible, $byId));
        }

        return $out;
    }

    private function cumulativeProfitLossDisplayTotal(string $asOfDate, bool $onlyPostedJournals): float
    {
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereDate('j.date', '<=', $asOfDate)
            ->whereIn('a.type', ['income', 'expense']);
        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }
        $lines = $query
            ->selectRaw('a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.type')
            ->get();
        $incomeTotal = 0.0;
        $expenseTotal = 0.0;
        foreach ($lines as $r) {
            $amt = $this->profitLossRowAmount($r->type, (float) $r->debit, (float) $r->credit);
            if ($r->type === 'income') {
                $incomeTotal += $amt;
            } else {
                $expenseTotal += $amt;
            }
        }

        return round($incomeTotal - $expenseTotal, 2);
    }

    private function balanceSheetDisplayAmount(string $type, float $debit, float $credit): float
    {
        return match ($type) {
            'asset' => $debit - $credit,
            'liability', 'net_assets' => $credit - $debit,
            default => 0.0,
        };
    }

    private function profitLossRowAmount(string $type, float $debit, float $credit): float
    {
        return match ($type) {
            'income' => $credit - $debit,
            'expense' => $debit - $credit,
            default => 0.0,
        };
    }

    private function profitLossBucket(string $accountCode, string $type): string
    {
        $dot = strpos($accountCode, '.');
        $root = $dot === false ? $accountCode : substr($accountCode, 0, $dot);
        if ($type === 'income') {
            return $root === '7' ? 'other_income' : 'revenue';
        }
        if ($type === 'expense') {
            return match ($root) {
                '5' => 'cogs',
                '6' => 'operating',
                '7' => 'other_expense',
                default => 'operating',
            };
        }

        return 'revenue';
    }

    private function accountCodeUnderPrefix(string $code, string $prefix): bool
    {
        return $code === $prefix || str_starts_with($code, $prefix.'.');
    }

    /**
     * Balance sheet display total for accounts whose code matches any prefix (asset / liability / net_assets only).
     * Useful for reconciliation and tests against journal data.
     */
    public function balanceSheetDisplayTotalForPrefixes(?string $asOf, array $prefixes, bool $onlyPostedJournals = true): float
    {
        return $this->aggregateDisplayBalanceByPrefixes($asOf, $prefixes, $onlyPostedJournals);
    }

    private function aggregateDisplayBalanceByPrefixes(?string $asOf, array $prefixes, bool $onlyPostedJournals): float
    {
        if ($prefixes === []) {
            return 0.0;
        }
        $tb = $this->getTrialBalance($asOf, $onlyPostedJournals);
        $sum = 0.0;
        foreach ($tb['rows'] as $r) {
            if (! in_array($r['type'], ['asset', 'liability', 'net_assets'], true)) {
                continue;
            }
            foreach ($prefixes as $prefix) {
                if ($this->accountCodeUnderPrefix($r['code'], $prefix)) {
                    $sum += $this->balanceSheetDisplayAmount($r['type'], $r['debit'], $r['credit']);
                    break;
                }
            }
        }

        return round($sum, 2);
    }

    private function periodDepreciationExpenseAmount(string $from, string $to, bool $onlyPostedJournals): float
    {
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.type', 'expense')
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->where(function ($q) {
                $q->where('a.code', 'like', '6.2.9%')
                    ->orWhere('a.code', 'like', '5.2.6%')
                    ->orWhereRaw('LOWER(a.name) like ?', ['%depreciation%'])
                    ->orWhereRaw('LOWER(a.name) like ?', ['%penyusutan%']);
            });
        if ($onlyPostedJournals) {
            $query->whereNotNull('j.posted_at');
        }

        return round((float) $query->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) as x')->value('x'), 2);
    }

    private function bucketLabel(int $days): string
    {
        if ($days <= 30) {
            return 'current';
        }
        if ($days <= 60) {
            return '31-60';
        }
        if ($days <= 90) {
            return '61-90';
        }

        return '91+';
    }

    public function getArBalances(): array
    {
        $inv = DB::table('sales_invoices')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $rcp = DB::table('sales_receipts')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $rows = [];
        $partnerIds = array_unique(array_merge(array_keys($inv->toArray()), array_keys($rcp->toArray())));
        foreach ($partnerIds as $pid) {
            $invt = (float) ($inv[$pid] ?? 0);
            $rcpt = (float) ($rcp[$pid] ?? 0);
            $name = DB::table('business_partners')->where('id', $pid)->value('name');
            $rows[] = ['customer_id' => (int) $pid, 'customer_name' => $name, 'invoices' => $invt, 'receipts' => $rcpt, 'balance' => round($invt - $rcpt, 2)];
        }

        return ['rows' => $rows, 'totals' => [
            'invoices' => array_sum(array_column($rows, 'invoices')),
            'receipts' => array_sum(array_column($rows, 'receipts')),
            'balance' => array_sum(array_column($rows, 'balance')),
        ]];
    }

    public function getApBalances(): array
    {
        $inv = DB::table('purchase_invoices')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $pay = DB::table('purchase_payments')->where('status', 'posted')
            ->select('business_partner_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('business_partner_id')->pluck('total', 'business_partner_id');
        $rows = [];
        $partnerIds = array_unique(array_merge(array_keys($inv->toArray()), array_keys($pay->toArray())));
        foreach ($partnerIds as $pid) {
            $invt = (float) ($inv[$pid] ?? 0);
            $payt = (float) ($pay[$pid] ?? 0);
            $name = DB::table('business_partners')->where('id', $pid)->value('name');
            $rows[] = ['vendor_id' => (int) $pid, 'vendor_name' => $name, 'invoices' => $invt, 'payments' => $payt, 'balance' => round($invt - $payt, 2)];
        }

        return ['rows' => $rows, 'totals' => [
            'invoices' => array_sum(array_column($rows, 'invoices')),
            'payments' => array_sum(array_column($rows, 'payments')),
            'balance' => array_sum(array_column($rows, 'balance')),
        ]];
    }

    public function getWithholdingRecap(array $filters = []): array
    {
        // Per-invoice rounding: first compute each invoice's withholding, then sum by vendor
        $invoiceQuery = DB::table('purchase_invoice_lines as pil')
            ->join('purchase_invoices as pi', 'pi.id', '=', 'pil.invoice_id')
            ->join('tax_codes as t', 't.id', '=', 'pil.tax_code_id')
            ->where('pi.status', 'posted')
            ->whereRaw('LOWER(t.type) = ?', ['withholding']);

        if (! empty($filters['from'])) {
            $invoiceQuery->whereDate('pi.date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $invoiceQuery->whereDate('pi.date', '<=', $filters['to']);
        }
        if (! empty($filters['vendor_id'])) {
            $invoiceQuery->where('pi.business_partner_id', (int) $filters['vendor_id']);
        }

        $invoiceQuery = $invoiceQuery
            ->select('pi.id as invoice_id', 'pi.business_partner_id as vendor_id', DB::raw('ROUND(SUM(pil.amount * t.rate), 2) as inv_withholding'))
            ->groupBy('pi.id', 'pi.business_partner_id');

        $q = DB::query()->fromSub($invoiceQuery, 'w')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'w.vendor_id')
            ->select('w.vendor_id', 'bp.name as vendor_name', DB::raw('SUM(w.inv_withholding) as withholding_total'))
            ->groupBy('w.vendor_id', 'bp.name');

        $rows = $q->get()->map(function ($r) {
            return [
                'vendor_id' => (int) $r->vendor_id,
                'vendor_name' => $r->vendor_name,
                'withholding_total' => round((float) $r->withholding_total, 2),
            ];
        })->toArray();

        return [
            'filters' => $filters,
            'rows' => $rows,
            'totals' => [
                'withholding_total' => array_sum(array_column($rows, 'withholding_total')),
            ],
        ];
    }
}
