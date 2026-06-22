<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

class ReportService
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $balanceSnapshotCache = [];

    public function __construct(private JournalReportQueryBuilder $journalQuery) {}

    public function getTrialBalance(?string $date = null, bool $onlyPostedJournals = true, array $filters = []): array
    {
        $date = $date ?: now()->toDateString();
        $filters = array_merge($filters, ['as_of' => $date]);

        $query = $this->journalQuery->withAccounts($onlyPostedJournals)
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id');
        $this->journalQuery->applyCommonFilters($query, $filters, $onlyPostedJournals);

        $lines = $query
            ->selectRaw('a.id, a.code, a.name, a.type, a.report_group, a.normal_balance, SUM(jl.debit) as debit, SUM(jl.credit) as credit,
                        GROUP_CONCAT(DISTINCT c.code ORDER BY c.code SEPARATOR ", ") as currencies')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.report_group', 'a.normal_balance')
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
                'currencies' => $r->currencies ?: 'IDR',
            ];
        })->toArray();

        return [
            'as_of' => $date,
            'rows' => $rows,
            'totals' => [
                'debit' => array_sum(array_column($rows, 'debit')),
                'credit' => array_sum(array_column($rows, 'credit')),
            ],
            'filters' => $this->journalQuery->normalizeFilters($filters),
        ];
    }

    public function getGlDetail(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $normalized = $this->journalQuery->normalizeFilters($filters);
        $query = $this->journalQuery->withAccounts($onlyPostedJournals)
            ->leftJoin('currencies as c', 'c.id', '=', 'jl.currency_id')
            ->select(
                'j.id as journal_id',
                'j.date',
                'j.description as journal_desc',
                'a.id as account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.type as account_type',
                'a.normal_balance',
                'jl.debit',
                'jl.credit',
                'jl.memo',
                'c.code as currency_code',
                'jl.exchange_rate',
                'jl.debit_foreign',
                'jl.credit_foreign'
            );
        $this->journalQuery->applyCommonFilters($query, $filters, $onlyPostedJournals);

        $rawRows = $query->orderBy('a.code')->orderBy('j.date')->orderBy('j.id')->get();

        $openingByAccount = [];
        if ($normalized['from']) {
            $openQuery = $this->journalQuery->base($onlyPostedJournals)
                ->selectRaw('jl.account_id, COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
                ->whereDate('j.date', '<', $normalized['from']);
            $this->journalQuery->applyCommonFilters(
                $openQuery,
                array_merge($filters, ['from' => null, 'to' => null, 'period_year' => null, 'period_month' => null]),
                $onlyPostedJournals
            );
            if ($normalized['account_id']) {
                $openQuery->where('jl.account_id', $normalized['account_id']);
            }
            foreach ($openQuery->groupBy('jl.account_id')->get() as $row) {
                $openingByAccount[(int) $row->account_id] = (float) $row->balance;
            }
        }

        $runningByAccount = $openingByAccount;
        $rows = [];
        foreach ($rawRows as $r) {
            $accountId = (int) $r->account_id;
            $runningByAccount[$accountId] = ($runningByAccount[$accountId] ?? 0.0)
                + (float) $r->debit - (float) $r->credit;

            $rows[] = [
                'date' => $r->date,
                'journal_desc' => $r->journal_desc,
                'account_id' => $accountId,
                'account_code' => $r->account_code,
                'account_name' => $r->account_name,
                'debit' => (float) $r->debit,
                'credit' => (float) $r->credit,
                'balance' => round($runningByAccount[$accountId], 2),
                'memo' => $r->memo,
                'currency_code' => $r->currency_code ?: 'IDR',
                'exchange_rate' => (float) $r->exchange_rate ?: 1.000000,
                'debit_foreign' => (float) $r->debit_foreign,
                'credit_foreign' => (float) $r->credit_foreign,
            ];
        }

        return [
            'filters' => $normalized,
            'rows' => $rows,
            'opening_balances' => array_map(fn ($v) => round($v, 2), $openingByAccount),
        ];
    }

    public function getBalanceSheet(?string $asOf = null, bool $onlyPostedJournals = true, bool $hideZeroLines = true, ?string $priorAsOf = null, array $filters = []): array
    {
        $date = $asOf ?: now()->toDateString();
        $snapshot = $this->getAccountBalanceSnapshot($date, $onlyPostedJournals, $filters);

        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $totalEquity = 0.0;
        $ownById = [];
        foreach ($snapshot as $row) {
            if (! in_array($row['type'], ['asset', 'liability', 'net_assets'], true)) {
                continue;
            }
            $amount = $row['display_amount'];
            $ownById[$row['account_id']] = round($amount, 2);
            if ($row['type'] === 'asset') {
                $totalAssets += $amount;
            } elseif ($row['type'] === 'liability') {
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
        $unclosedPnl = $this->cumulativeProfitLossDisplayTotal($date, $onlyPostedJournals, $filters);
        $differenceVsUnclosedPnl = round($difference - $unclosedPnl, 2);

        $sections = [
            ['key' => 'assets', 'label' => 'Assets', 'rows' => $assets, 'total' => $totalAssets],
            ['key' => 'liabilities', 'label' => 'Liabilities', 'rows' => $liabilities, 'total' => $totalLiabilities],
            ['key' => 'equity', 'label' => 'Equity / Net assets', 'rows' => $equity, 'total' => $totalEquity],
        ];

        $priorTotals = null;
        if ($priorAsOf) {
            $prior = $this->getBalanceSheet($priorAsOf, $onlyPostedJournals, $hideZeroLines, null, $filters);
            $priorTotals = $prior['totals'];
            foreach ($sections as $sectionIndex => $section) {
                $priorRowsByCode = collect($prior['sections'][$sectionIndex]['rows'] ?? [])->keyBy('code');
                foreach ($section['rows'] as $idx => $row) {
                    $sections[$sectionIndex]['rows'][$idx]['prior_amount'] = (float) ($priorRowsByCode[$row['code']]['amount'] ?? 0);
                }
            }
        }

        return [
            'as_of' => $date,
            'prior_as_of' => $priorAsOf,
            'report_title' => 'Balance Sheet',
            'entity_name' => (string) config('app.name'),
            'sections' => $sections,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
                'difference' => $difference,
                'unclosed_pnl_cumulative' => $unclosedPnl,
                'difference_vs_unclosed_pnl' => $differenceVsUnclosedPnl,
                'prior' => $priorTotals,
            ],
            'only_posted_journals' => $onlyPostedJournals,
            'hide_zero_lines' => $hideZeroLines,
            'filters' => $this->journalQuery->normalizeFilters($filters),
        ];
    }

    public function getProfitAndLoss(array $filters = [], bool $onlyPostedJournals = true, bool $hideZeroLines = true): array
    {
        $normalized = $this->journalQuery->normalizeFilters($filters);
        $from = $normalized['from'] ?? now()->startOfMonth()->toDateString();
        $to = $normalized['to'] ?? now()->toDateString();

        $query = $this->journalQuery->withAccounts($onlyPostedJournals)
            ->whereIn('a.type', ['income', 'expense']);
        $this->journalQuery->applyCommonFilters($query, array_merge($filters, ['from' => $from, 'to' => $to]), $onlyPostedJournals);

        $lines = $query
            ->selectRaw('a.id, a.code, a.name, a.type, a.report_group, a.normal_balance, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.report_group', 'a.normal_balance')
            ->orderBy('a.code')
            ->get();

        $ownById = [];
        foreach ($lines as $r) {
            $ownById[(int) $r->id] = round($this->profitLossRowAmount($r->type, (float) $r->debit, (float) $r->credit, $r->report_group, $r->normal_balance), 2);
        }

        $coaAccounts = DB::table('accounts')
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get(['id', 'parent_id', 'code', 'name', 'is_postable', 'type', 'report_group', 'normal_balance']);
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

        $totals = array_fill_keys($bucketKeys, 0.0);
        foreach ($lines as $r) {
            $amt = round($this->profitLossRowAmount($r->type, (float) $r->debit, (float) $r->credit, $r->report_group, $r->normal_balance), 2);
            if ($hideZeroLines && abs($amt) < 0.0005) {
                continue;
            }
            $bucket = $this->profitLossBucket($r->code, $r->type, $r->report_group);
            $totals[$bucket] += $amt;
        }
        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

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

        $grossProfit = round($totals['revenue'] - $totals['cogs'], 2);
        $operatingIncome = round($grossProfit - $totals['operating'], 2);
        $netIncome = round($operatingIncome + $totals['other_income'] - $totals['other_expense'], 2);

        $priorSubtotals = null;
        if (! empty($filters['prior_from']) && ! empty($filters['prior_to'])) {
            $prior = $this->getProfitAndLoss([
                'from' => $filters['prior_from'],
                'to' => $filters['prior_to'],
            ], $onlyPostedJournals, $hideZeroLines);
            $priorSubtotals = $prior['subtotals'];
        }

        $sections = [];
        foreach ($bucketKeys as $key) {
            $sections[] = [
                'key' => $key,
                'label' => $bucketLabels[$key],
                'rows' => $buckets[$key],
                'total' => $totals[$key],
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'filters' => $normalized,
            'report_title' => 'Profit & Loss Statement',
            'entity_name' => (string) config('app.name'),
            'sections' => $sections,
            'subtotals' => [
                'gross_profit' => $grossProfit,
                'operating_income' => $operatingIncome,
                'net_income' => $netIncome,
            ],
            'prior_subtotals' => $priorSubtotals,
            'only_posted_journals' => $onlyPostedJournals,
            'hide_zero_lines' => $hideZeroLines,
        ];
    }

    public function getCashFlowStatement(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $normalized = $this->journalQuery->normalizeFilters($filters);
        $from = $normalized['from'] ?? now()->startOfMonth()->toDateString();
        $to = $normalized['to'] ?? now()->toDateString();
        $begin = \Carbon\Carbon::parse($from)->subDay()->toDateString();
        $px = config('cash_flow.account_prefixes', []);

        $pl = $this->getProfitAndLoss(array_merge($filters, ['from' => $from, 'to' => $to]), $onlyPostedJournals, false);
        $netIncome = $pl['subtotals']['net_income'];
        $depreciationAddBack = $this->periodDepreciationExpenseAmount($from, $to, $onlyPostedJournals, $filters);

        $beginSnapshot = $this->getAccountBalanceSnapshot($begin, $onlyPostedJournals, $filters);
        $endSnapshot = $this->getAccountBalanceSnapshot($to, $onlyPostedJournals, $filters);

        $arBegin = $this->aggregateDisplayBalanceByPrefixesFromSnapshot($beginSnapshot, $px['receivables'] ?? ['1.1.2']);
        $arEnd = $this->aggregateDisplayBalanceByPrefixesFromSnapshot($endSnapshot, $px['receivables'] ?? ['1.1.2']);
        $wcAr = round(-($arEnd - $arBegin), 2);

        $wcInv = round(-($this->prefixDelta($beginSnapshot, $endSnapshot, $px['inventory'] ?? ['1.1.3'])), 2);
        $prePrefixes = $px['prepaid'] ?? ['1.1.5', '1.1.7'];
        $wcPrepaid = round(-($this->prefixDelta($beginSnapshot, $endSnapshot, $prePrefixes)), 2);
        $wcAp = round($this->prefixDelta($beginSnapshot, $endSnapshot, $px['payables'] ?? ['2.1.1']), 2);
        $wcAccr = round($this->prefixDelta($beginSnapshot, $endSnapshot, $px['accrued_liabilities'] ?? ['2.1.4']), 2);
        $wcTaxPayables = round($this->prefixDelta($beginSnapshot, $endSnapshot, $px['tax_payables'] ?? []), 2);
        $wcInputVatPrepaid = round(-($this->prefixDelta($beginSnapshot, $endSnapshot, $px['input_vat_prepaid_assets'] ?? [])), 2);

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

        $investingAmount = round(-($this->prefixDelta($beginSnapshot, $endSnapshot, $px['non_current_assets'] ?? ['1.2'])), 2);
        $investingLines = [
            ['key' => 'nc_assets', 'label' => 'Net change in non-current assets (configured prefixes)', 'amount' => $investingAmount],
        ];

        $finShortTermBorrowings = round($this->prefixDelta($beginSnapshot, $endSnapshot, $px['short_term_borrowings'] ?? ['2.1.3']), 2);
        $finLongTerm = round($this->prefixDelta($beginSnapshot, $endSnapshot, $px['long_term_liabilities'] ?? ['2.2']), 2);
        $finEquityCapital = round($this->prefixDelta($beginSnapshot, $endSnapshot, $px['equity_financing_prefixes'] ?? ['3.1', '3.2']), 2);
        $financingLines = [
            ['key' => 'short_term_borrowings', 'label' => 'Net change in short-term borrowings (configured prefixes)', 'amount' => $finShortTermBorrowings],
            ['key' => 'lt_liabilities', 'label' => 'Net change in long-term liabilities (configured prefixes)', 'amount' => $finLongTerm],
            ['key' => 'equity_capital', 'label' => 'Net change in share capital / premium (equity_financing_prefixes; excludes 3.3 by default)', 'amount' => $finEquityCapital],
        ];
        $financingSubtotal = round(array_sum(array_column($financingLines, 'amount')), 2);
        $netChangeComputed = round($operatingSubtotal + $investingLines[0]['amount'] + $financingSubtotal, 2);

        $cashPx = $px['cash_and_bank'] ?? ['1.1.1'];
        $cashBegin = $this->aggregateDisplayBalanceByPrefixesFromSnapshot($beginSnapshot, $cashPx);
        $cashEnd = $this->aggregateDisplayBalanceByPrefixesFromSnapshot($endSnapshot, $cashPx);
        $netChangeCash = round($cashEnd - $cashBegin, 2);

        return [
            'from' => $from,
            'to' => $to,
            'begin_balance_date' => $begin,
            'method' => 'indirect',
            'operating' => ['label' => 'Cash flows from operating activities', 'lines' => $operatingLines, 'subtotal' => $operatingSubtotal],
            'investing' => ['label' => 'Cash flows from investing activities', 'lines' => $investingLines, 'subtotal' => $investingLines[0]['amount']],
            'financing' => ['label' => 'Cash flows from financing activities', 'lines' => $financingLines, 'subtotal' => $financingSubtotal],
            'summary' => [
                'net_change_computed' => $netChangeComputed,
                'cash_begin_display' => $cashBegin,
                'cash_end_display' => $cashEnd,
                'net_change_cash_accounts' => $netChangeCash,
                'reconciliation_difference' => round($netChangeComputed - $netChangeCash, 2),
            ],
            'only_posted_journals' => $onlyPostedJournals,
            'filters' => $normalized,
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
        $rows = $this->buildArOutstandingRows($asOfDate, bucket: true, options: $options);

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => $this->sumAgingTotals($rows),
        ];
    }

    public function getApAging(?string $asOf = null, array $options = []): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $rows = $this->buildApOutstandingRows($asOfDate, bucket: true, options: $options);

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => $this->sumAgingTotals($rows),
        ];
    }

    public function getCashLedger(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $accountId = ! empty($filters['account_id'])
            ? (int) $filters['account_id']
            : ($this->journalQuery->defaultCashAccountId() ?? 0);

        $normalized = $this->journalQuery->normalizeFilters($filters);
        $q = $this->journalQuery->base($onlyPostedJournals)
            ->select('j.date', 'j.description', 'jl.debit', 'jl.credit')
            ->where('jl.account_id', $accountId);
        $this->journalQuery->applyCommonFilters($q, $filters, $onlyPostedJournals);

        $rows = $q->orderBy('j.date')->orderBy('j.id')->get()->toArray();
        $opening = 0.0;
        if ($normalized['from']) {
            $openQ = $this->journalQuery->base($onlyPostedJournals)
                ->where('jl.account_id', $accountId)
                ->whereDate('j.date', '<', $normalized['from']);
            $this->journalQuery->applyCommonFilters(
                $openQ,
                array_merge($filters, ['from' => null, 'to' => null, 'period_year' => null, 'period_month' => null]),
                $onlyPostedJournals
            );
            $opening = (float) $openQ->selectRaw('COALESCE(SUM(jl.debit - jl.credit),0) as bal')->value('bal');
        }

        $balance = $opening;
        $out = [];
        if ($opening !== 0.0) {
            $out[] = [
                'date' => $normalized['from'] ?? '',
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

        $account = $accountId ? DB::table('accounts')->where('id', $accountId)->first(['id', 'code', 'name']) : null;

        return [
            'rows' => $out,
            'filters' => $normalized,
            'account_id' => $accountId,
            'account' => $account ? ['id' => (int) $account->id, 'code' => $account->code, 'name' => $account->name] : null,
            'opening_balance' => round($opening, 2),
            'cash_accounts' => $this->listCashAccounts(),
        ];
    }

    public function getArBalances(?string $asOf = null): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $partnerRows = $this->buildArOutstandingRows($asOfDate, bucket: false);
        $rows = array_map(fn (array $r) => [
            'customer_id' => $r['customer_id'],
            'customer_name' => $r['customer_name'],
            'invoices' => round($r['gross'], 2),
            'receipts' => round($r['settled'], 2),
            'balance' => round($r['total'], 2),
        ], $partnerRows);

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => [
                'invoices' => round(array_sum(array_column($rows, 'invoices')), 2),
                'receipts' => round(array_sum(array_column($rows, 'receipts')), 2),
                'balance' => round(array_sum(array_column($rows, 'balance')), 2),
            ],
        ];
    }

    public function getApBalances(?string $asOf = null): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $partnerRows = $this->buildApOutstandingRows($asOfDate, bucket: false);
        $rows = array_map(fn (array $r) => [
            'vendor_id' => $r['vendor_id'],
            'vendor_name' => $r['vendor_name'],
            'invoices' => round($r['gross'], 2),
            'payments' => round($r['settled'], 2),
            'balance' => round($r['total'], 2),
        ], $partnerRows);

        return [
            'as_of' => $asOfDate,
            'rows' => $rows,
            'totals' => [
                'invoices' => round(array_sum(array_column($rows, 'invoices')), 2),
                'payments' => round(array_sum(array_column($rows, 'payments')), 2),
                'balance' => round(array_sum(array_column($rows, 'balance')), 2),
            ],
        ];
    }

    public function getSubledgerReconciliation(?string $asOf = null, bool $onlyPostedJournals = true, array $filters = []): array
    {
        $asOfDate = $asOf ?: now()->toDateString();
        $sections = [];

        foreach ([
            'ar' => ['aging' => 'getArAging', 'label' => 'Accounts Receivable'],
            'ap' => ['aging' => 'getApAging', 'label' => 'Accounts Payable'],
        ] as $controlType => $meta) {
            $aging = $this->{$meta['aging']}($asOfDate);
            $subledgerTotal = (float) ($aging['totals']['total'] ?? 0);

            $control = DB::table('control_accounts as ca')
                ->join('accounts as a', 'a.id', '=', 'ca.account_id')
                ->where('ca.control_type', $controlType)
                ->where('ca.is_active', true)
                ->select('ca.account_id', 'a.code', 'a.name', 'a.type')
                ->first();

            $glBalance = 0.0;
            if ($control) {
                $snapshot = $this->getAccountBalanceSnapshot($asOfDate, $onlyPostedJournals, $filters);
                foreach ($snapshot as $row) {
                    if ((int) $row['account_id'] === (int) $control->account_id) {
                        $glBalance = $row['display_amount'];
                        break;
                    }
                }
            }

            $sections[] = [
                'control_type' => $controlType,
                'label' => $meta['label'],
                'control_account_code' => $control?->code,
                'control_account_name' => $control?->name,
                'subledger_total' => round($subledgerTotal, 2),
                'gl_control_balance' => round($glBalance, 2),
                'variance' => round($glBalance - $subledgerTotal, 2),
                'is_balanced' => abs($glBalance - $subledgerTotal) < 0.05,
            ];
        }

        return [
            'as_of' => $asOfDate,
            'sections' => $sections,
            'only_posted_journals' => $onlyPostedJournals,
            'filters' => $this->journalQuery->normalizeFilters($filters),
        ];
    }

    public function getCashAccountOptions(): array
    {
        return $this->listCashAccounts();
    }

    public function getWithholdingRecap(array $filters = []): array
    {
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
            ->select('pi.id as invoice_id', 'pi.business_partner_id as vendor_id', DB::raw('ROUND(SUM(pil.amount * t.rate / 100), 2) as inv_withholding'))
            ->groupBy('pi.id', 'pi.business_partner_id');

        $rows = DB::query()->fromSub($invoiceQuery, 'w')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'w.vendor_id')
            ->select('w.vendor_id', 'bp.name as vendor_name', DB::raw('SUM(w.inv_withholding) as withholding_total'))
            ->groupBy('w.vendor_id', 'bp.name')
            ->get()
            ->map(fn ($r) => [
                'vendor_id' => (int) $r->vendor_id,
                'vendor_name' => $r->vendor_name,
                'withholding_total' => round((float) $r->withholding_total, 2),
            ])->toArray();

        return [
            'filters' => $filters,
            'rows' => $rows,
            'totals' => ['withholding_total' => array_sum(array_column($rows, 'withholding_total'))],
        ];
    }

    public function getStatementOfChangesInEquity(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $normalized = $this->journalQuery->normalizeFilters($filters);
        $from = $normalized['from'] ?? now()->startOfYear()->toDateString();
        $to = $normalized['to'] ?? now()->toDateString();
        $openingDate = \Carbon\Carbon::parse($from)->subDay()->toDateString();

        $equityAccounts = DB::table('accounts')
            ->where('type', 'net_assets')
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $openingSnapshot = $this->getAccountBalanceSnapshot($openingDate, $onlyPostedJournals, $filters);
        $closingSnapshot = $this->getAccountBalanceSnapshot($to, $onlyPostedJournals, $filters);
        $openingById = collect($openingSnapshot)->keyBy('account_id');
        $closingById = collect($closingSnapshot)->keyBy('account_id');

        $rows = [];
        $openingTotal = 0.0;
        $closingTotal = 0.0;
        foreach ($equityAccounts as $account) {
            $opening = (float) ($openingById[(int) $account->id]['display_amount'] ?? 0);
            $closing = (float) ($closingById[(int) $account->id]['display_amount'] ?? 0);
            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'opening' => round($opening, 2),
                'movement' => round($closing - $opening, 2),
                'closing' => round($closing, 2),
            ];
            $openingTotal += $opening;
            $closingTotal += $closing;
        }

        $pnl = $this->getProfitAndLoss(array_merge($filters, ['from' => $from, 'to' => $to]), $onlyPostedJournals, false);

        return [
            'from' => $from,
            'to' => $to,
            'report_title' => 'Statement of Changes in Equity',
            'entity_name' => (string) config('app.name'),
            'rows' => $rows,
            'net_income' => (float) ($pnl['subtotals']['net_income'] ?? 0),
            'totals' => [
                'opening' => round($openingTotal, 2),
                'movement' => round($closingTotal - $openingTotal, 2),
                'closing' => round($closingTotal, 2),
            ],
            'only_posted_journals' => $onlyPostedJournals,
            'filters' => $normalized,
        ];
    }

    public function getPpnReconciliation(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $normalized = $this->journalQuery->normalizeFilters($filters);
        $from = $normalized['from'] ?? now()->startOfMonth()->toDateString();
        $to = $normalized['to'] ?? now()->toDateString();
        $begin = \Carbon\Carbon::parse($from)->subDay()->toDateString();

        $outputPrefixes = ['2.1.2.01'];
        $inputPrefixes = ['1.1.4.01', '1.1.6'];

        $beginSnapshot = $this->getAccountBalanceSnapshot($begin, $onlyPostedJournals, $filters);
        $endSnapshot = $this->getAccountBalanceSnapshot($to, $onlyPostedJournals, $filters);

        $outputVat = round(abs($this->prefixDelta($beginSnapshot, $endSnapshot, $outputPrefixes)), 2);
        $inputVat = round(abs($this->prefixDelta($beginSnapshot, $endSnapshot, $inputPrefixes)), 2);

        return [
            'from' => $from,
            'to' => $to,
            'report_title' => 'PPN Masukan / Keluaran Reconciliation',
            'ppn_keluaran' => $outputVat,
            'ppn_masukan' => $inputVat,
            'net_payable' => round($outputVat - $inputVat, 2),
            'only_posted_journals' => $onlyPostedJournals,
            'filters' => $normalized,
        ];
    }

    public function exportSptPpn1111(array $filters = [], bool $onlyPostedJournals = true): array
    {
        $recon = $this->getPpnReconciliation($filters, $onlyPostedJournals);

        return [
            'form' => 'SPT-1111',
            'period_from' => $recon['from'],
            'period_to' => $recon['to'],
            'fields' => [
                'ppn_keluaran' => $recon['ppn_keluaran'],
                'ppn_masukan' => $recon['ppn_masukan'],
                'ppn_kurang_lebih_bayar' => $recon['net_payable'],
            ],
        ];
    }

    public function balanceSheetDisplayTotalForPrefixes(?string $asOf, array $prefixes, bool $onlyPostedJournals = true, array $filters = []): float
    {
        $snapshot = $this->getAccountBalanceSnapshot($asOf, $onlyPostedJournals, $filters);

        return $this->aggregateDisplayBalanceByPrefixesFromSnapshot($snapshot, $prefixes);
    }

    /**
     * @return array<int, array{account_id: int, code: string, name: string, type: string, debit: float, credit: float, display_amount: float, report_group: ?string, normal_balance: ?string}>
     */
    private function getAccountBalanceSnapshot(?string $asOf, bool $onlyPostedJournals, array $filters = []): array
    {
        $normalized = $this->journalQuery->normalizeFilters(array_merge($filters, ['as_of' => $asOf ?: now()->toDateString()]));
        $cacheKey = implode('|', [
            $normalized['as_of'] ?? '',
            $onlyPostedJournals ? '1' : '0',
            $normalized['company_entity_id'] ?? '',
        ]);

        if (isset($this->balanceSnapshotCache[$cacheKey])) {
            return $this->balanceSnapshotCache[$cacheKey];
        }

        $query = $this->journalQuery->withAccounts($onlyPostedJournals);
        $this->journalQuery->applyCommonFilters($query, array_merge($filters, ['as_of' => $normalized['as_of']]), $onlyPostedJournals);

        $lines = $query
            ->selectRaw('a.id, a.code, a.name, a.type, a.report_group, a.normal_balance, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.report_group', 'a.normal_balance')
            ->orderBy('a.code')
            ->get();

        $snapshot = [];
        foreach ($lines as $r) {
            $snapshot[] = [
                'account_id' => (int) $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'type' => $r->type,
                'report_group' => $r->report_group,
                'normal_balance' => $r->normal_balance,
                'debit' => (float) $r->debit,
                'credit' => (float) $r->credit,
                'display_amount' => $this->balanceSheetDisplayAmount(
                    $r->type,
                    (float) $r->debit,
                    (float) $r->credit,
                    $r->code,
                    $r->name,
                    $r->report_group,
                    $r->normal_balance
                ),
            ];
        }

        return $this->balanceSnapshotCache[$cacheKey] = $snapshot;
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshot
     */
    private function aggregateDisplayBalanceByPrefixesFromSnapshot(array $snapshot, array $prefixes): float
    {
        if ($prefixes === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($snapshot as $row) {
            if (! in_array($row['type'], ['asset', 'liability', 'net_assets'], true)) {
                continue;
            }
            foreach ($prefixes as $prefix) {
                if ($this->accountCodeUnderPrefix($row['code'], $prefix)) {
                    $sum += $row['display_amount'];
                    break;
                }
            }
        }

        return round($sum, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $beginSnapshot
     * @param  array<int, array<string, mixed>>  $endSnapshot
     */
    private function prefixDelta(array $beginSnapshot, array $endSnapshot, array $prefixes): float
    {
        return $this->aggregateDisplayBalanceByPrefixesFromSnapshot($endSnapshot, $prefixes)
            - $this->aggregateDisplayBalanceByPrefixesFromSnapshot($beginSnapshot, $prefixes);
    }

    private function aggregateDisplayBalanceByPrefixes(?string $asOf, array $prefixes, bool $onlyPostedJournals, array $filters = []): float
    {
        $snapshot = $this->getAccountBalanceSnapshot($asOf, $onlyPostedJournals, $filters);

        return $this->aggregateDisplayBalanceByPrefixesFromSnapshot($snapshot, $prefixes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildArOutstandingRows(string $asOfDate, bool $bucket, array $options = []): array
    {
        $invoices = DB::table('sales_invoices as si')
            ->leftJoin('business_partners as c', 'c.id', '=', 'si.business_partner_id')
            ->where('si.status', 'posted')
            ->whereDate(DB::raw('COALESCE(si.due_date, si.date)'), '<=', $asOfDate)
            ->leftJoin('sales_receipt_allocations as sra', 'sra.invoice_id', '=', 'si.id')
            ->leftJoin('sales_receipts as sr', function ($join) use ($asOfDate) {
                $join->on('sr.id', '=', 'sra.receipt_id')
                    ->where('sr.status', '=', 'posted')
                    ->whereDate('sr.date', '<=', $asOfDate);
            })
            ->select(
                'si.id',
                'si.business_partner_id as customer_id',
                DB::raw('COALESCE(si.due_date, si.date) as effective_date'),
                'si.total_amount',
                DB::raw('COALESCE(SUM(sra.amount),0) as settled_amount'),
                'c.name as customer_name'
            )
            ->groupBy('si.id', 'si.business_partner_id', 'effective_date', 'si.total_amount', 'c.name')
            ->get();

        return $this->aggregateOutstandingRows($invoices, $asOfDate, 'customer_id', 'customer_name', $bucket, $options);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildApOutstandingRows(string $asOfDate, bool $bucket, array $options = []): array
    {
        $invoices = DB::table('purchase_invoices as pi')
            ->leftJoin('business_partners as bp', 'bp.id', '=', 'pi.business_partner_id')
            ->where('pi.status', 'posted')
            ->whereDate(DB::raw('COALESCE(pi.due_date, pi.date)'), '<=', $asOfDate)
            ->leftJoin('purchase_payment_allocations as ppa', 'ppa.invoice_id', '=', 'pi.id')
            ->leftJoin('purchase_payments as pp', function ($join) use ($asOfDate) {
                $join->on('pp.id', '=', 'ppa.payment_id')
                    ->where('pp.status', '=', 'posted')
                    ->whereDate('pp.date', '<=', $asOfDate);
            })
            ->select(
                'pi.id',
                'pi.business_partner_id as vendor_id',
                DB::raw('COALESCE(pi.due_date, pi.date) as effective_date'),
                'pi.total_amount',
                DB::raw('COALESCE(SUM(ppa.amount),0) as settled_amount'),
                'bp.name as vendor_name'
            )
            ->groupBy('pi.id', 'pi.business_partner_id', 'effective_date', 'pi.total_amount', 'bp.name')
            ->get();

        return $this->aggregateOutstandingRows($invoices, $asOfDate, 'vendor_id', 'vendor_name', $bucket, $options);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function aggregateOutstandingRows($invoices, string $asOfDate, string $idKey, string $nameKey, bool $bucket, array $options): array
    {
        $partners = [];
        foreach ($invoices as $inv) {
            $net = max(0, (float) $inv->total_amount - (float) $inv->settled_amount);
            if ($net <= 0) {
                continue;
            }

            $key = (int) $inv->{$idKey};
            if (! isset($partners[$key])) {
                $partners[$key] = [
                    $idKey => $key,
                    $nameKey => $inv->{$nameKey},
                    'gross' => 0.0,
                    'settled' => 0.0,
                    'total' => 0.0,
                    'current' => 0.0,
                    'd31_60' => 0.0,
                    'd61_90' => 0.0,
                    'd91_plus' => 0.0,
                ];
            }

            $partners[$key]['gross'] += (float) $inv->total_amount;
            $partners[$key]['settled'] += (float) $inv->settled_amount;
            $partners[$key]['total'] += $net;

            if ($bucket) {
                $days = \Carbon\Carbon::parse($inv->effective_date)->diffInDays(\Carbon\Carbon::parse($asOfDate));
                match ($this->bucketLabel($days)) {
                    'current' => $partners[$key]['current'] += $net,
                    '31-60' => $partners[$key]['d31_60'] += $net,
                    '61-90' => $partners[$key]['d61_90'] += $net,
                    default => $partners[$key]['d91_plus'] += $net,
                };
            }
        }

        $rows = array_values($partners);
        if ($bucket && ! empty($options['overdue_only'])) {
            $rows = array_values(array_filter($rows, fn ($r) => ($r['d31_60'] + $r['d61_90'] + $r['d91_plus']) > 0));
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, float>
     */
    private function sumAgingTotals(array $rows): array
    {
        return [
            'current' => array_sum(array_column($rows, 'current')),
            'd31_60' => array_sum(array_column($rows, 'd31_60')),
            'd61_90' => array_sum(array_column($rows, 'd61_90')),
            'd91_plus' => array_sum(array_column($rows, 'd91_plus')),
            'total' => array_sum(array_column($rows, 'total')),
        ];
    }

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    private function listCashAccounts(): array
    {
        $prefixes = config('cash_flow.account_prefixes.cash_and_bank', ['1.1.1']);

        return DB::table('accounts')
            ->where('is_postable', true)
            ->where(function ($query) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('code', $prefix)->orWhere('code', 'like', $prefix.'.%');
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn ($a) => ['id' => (int) $a->id, 'code' => $a->code, 'name' => $a->name])
            ->all();
    }

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

    private function buildProfitLossHierarchyForBucket(string $bucketKey, $lines, array $accountById, array $ownById, bool $hideZeroLines): array
    {
        $idsInBucket = [];
        foreach ($lines as $r) {
            $amt = $ownById[(int) $r->id] ?? 0.0;
            if ($hideZeroLines && abs($amt) < 0.0005) {
                continue;
            }
            if ($this->profitLossBucket($r->code, $r->type, $r->report_group ?? null) !== $bucketKey) {
                continue;
            }
            $idsInBucket[] = (int) $r->id;
        }
        $expanded = [];
        foreach ($idsInBucket as $id) {
            $cur = $id;
            while ($cur && isset($accountById[$cur])) {
                $acc = $accountById[$cur];
                if ($this->profitLossBucket($acc->code, $acc->type, $acc->report_group ?? null) !== $bucketKey) {
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

        return $this->buildHierarchyRowsFromAccountSubset($subset, $expanded, $ownById, $hideZeroLines);
    }

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
            usort($children[$pid], fn (int $x, int $y) => strcmp($byId[$x]->code, $byId[$y]->code));
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
        usort($roots, fn (int $x, int $y) => strcmp($byId[$x]->code, $byId[$y]->code));
        $rows = [];
        foreach ($roots as $rid) {
            $rows = array_merge($rows, $this->dfsHierarchyRows($rid, 0, $children, $rollup, $visible, $byId));
        }

        return $rows;
    }

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

    private function dfsHierarchyRows(int $id, int $depth, array $children, array $rollup, array $visible, array $byId): array
    {
        if (! ($visible[$id] ?? false)) {
            return [];
        }
        $a = $byId[$id];
        $row = [
            'account_id' => $id,
            'code' => $a->code,
            'name' => $a->name,
            'amount' => $rollup[$id] ?? 0.0,
            'depth' => $depth,
            'is_parent' => ! empty($children[$id]),
            'is_postable' => (bool) $a->is_postable,
            'is_contra_asset' => $a->type === 'asset' && $this->isContraAssetAccount($a->code, $a->name, $a->report_group ?? null),
        ];
        $out = [$row];
        foreach ($children[$id] ?? [] as $cid) {
            $out = array_merge($out, $this->dfsHierarchyRows($cid, $depth + 1, $children, $rollup, $visible, $byId));
        }

        return $out;
    }

    private function cumulativeProfitLossDisplayTotal(string $asOfDate, bool $onlyPostedJournals, array $filters = []): float
    {
        $query = $this->journalQuery->withAccounts($onlyPostedJournals)
            ->whereIn('a.type', ['income', 'expense']);
        $this->journalQuery->applyCommonFilters($query, array_merge($filters, ['as_of' => $asOfDate]), $onlyPostedJournals);

        $lines = $query
            ->selectRaw('a.type, a.report_group, a.normal_balance, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.type', 'a.report_group', 'a.normal_balance')
            ->get();

        $incomeTotal = 0.0;
        $expenseTotal = 0.0;
        foreach ($lines as $r) {
            $amt = $this->profitLossRowAmount($r->type, (float) $r->debit, (float) $r->credit, $r->report_group, $r->normal_balance);
            if ($r->type === 'income') {
                $incomeTotal += $amt;
            } else {
                $expenseTotal += $amt;
            }
        }

        return round($incomeTotal - $expenseTotal, 2);
    }

    private function balanceSheetDisplayAmount(
        string $type,
        float $debit,
        float $credit,
        ?string $code = null,
        ?string $name = null,
        ?string $reportGroup = null,
        ?string $normalBalance = null
    ): float {
        if ($normalBalance === 'credit' && $type === 'asset') {
            return $credit - $debit;
        }
        if ($normalBalance === 'debit' && in_array($type, ['liability', 'net_assets'], true)) {
            return $debit - $credit;
        }
        if ($type === 'asset' && $this->isContraAssetAccount($code, $name, $reportGroup)) {
            return $credit - $debit;
        }

        return match ($type) {
            'asset' => $debit - $credit,
            'liability', 'net_assets' => $credit - $debit,
            default => 0.0,
        };
    }

    private function isContraAssetAccount(?string $code, ?string $name, ?string $reportGroup = null): bool
    {
        if ($reportGroup === 'contra_asset') {
            return true;
        }
        if ($name && str_contains(strtolower($name), 'akumulasi penyusutan')) {
            return true;
        }

        return $code !== null && (bool) preg_match('/^1\.2\.\d+\.(03|05|07)$/', $code);
    }

    private function profitLossRowAmount(string $type, float $debit, float $credit, ?string $reportGroup = null, ?string $normalBalance = null): float
    {
        if ($normalBalance === 'credit' && $type === 'expense') {
            return $credit - $debit;
        }
        if ($normalBalance === 'debit' && $type === 'income') {
            return $debit - $credit;
        }

        return match ($type) {
            'income' => $credit - $debit,
            'expense' => $debit - $credit,
            default => 0.0,
        };
    }

    private function profitLossBucket(string $accountCode, string $type, ?string $reportGroup = null): string
    {
        if ($reportGroup && in_array($reportGroup, ['revenue', 'cogs', 'operating', 'other_income', 'other_expense'], true)) {
            return $reportGroup;
        }

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

    private function periodDepreciationExpenseAmount(string $from, string $to, bool $onlyPostedJournals, array $filters = []): float
    {
        $query = $this->journalQuery->withAccounts($onlyPostedJournals)
            ->where('a.type', 'expense')
            ->where(function ($q) {
                $q->where('a.code', 'like', '6.2.9%')
                    ->orWhere('a.code', 'like', '5.2.6%')
                    ->orWhereRaw('LOWER(a.name) like ?', ['%depreciation%'])
                    ->orWhereRaw('LOWER(a.name) like ?', ['%penyusutan%']);
            });
        $this->journalQuery->applyCommonFilters($query, array_merge($filters, ['from' => $from, 'to' => $to]), $onlyPostedJournals);

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
}
