<?php

namespace Tests\Feature;

use App\Services\DashboardDataService;
use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $this->reports = app(ReportService::class);
    }

    public function test_balance_sheet_section_totals_match_trial_balance(): void
    {
        $date = now()->toDateString();
        $tb = $this->reports->getTrialBalance($date);
        $sumAssets = 0.0;
        $sumLiabilities = 0.0;
        $sumEquity = 0.0;
        foreach ($tb['rows'] as $r) {
            if ($r['type'] === 'asset') {
                $sumAssets += $r['debit'] - $r['credit'];
            } elseif ($r['type'] === 'liability') {
                $sumLiabilities += $r['credit'] - $r['debit'];
            } elseif ($r['type'] === 'net_assets') {
                $sumEquity += $r['credit'] - $r['debit'];
            }
        }
        $bs = $this->reports->getBalanceSheet($date, true, false);
        $this->assertEqualsWithDelta(round($sumAssets, 2), $bs['totals']['assets'], 0.05);
        $this->assertEqualsWithDelta(round($sumLiabilities, 2), $bs['totals']['liabilities'], 0.05);
        $this->assertEqualsWithDelta(round($sumEquity, 2), $bs['totals']['equity'], 0.05);
    }

    public function test_balance_sheet_difference_matches_unclosed_pnl(): void
    {
        $date = now()->toDateString();
        $bs = $this->reports->getBalanceSheet($date, true, false);
        $this->assertEqualsWithDelta(
            (float) $bs['totals']['difference'],
            (float) $bs['totals']['unclosed_pnl_cumulative'],
            0.05
        );
    }

    public function test_cash_flow_cash_change_matches_prefix_delta_from_tb(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $begin = Carbon::parse($from)->subDay()->toDateString();
        $onlyPosted = true;
        $cf = $this->reports->getCashFlowStatement(['from' => $from, 'to' => $to], $onlyPosted);
        $cashPx = config('cash_flow.account_prefixes.cash_and_bank', ['1.1.1']);
        $endCash = $this->reports->balanceSheetDisplayTotalForPrefixes($to, $cashPx, $onlyPosted);
        $beginCash = $this->reports->balanceSheetDisplayTotalForPrefixes($begin, $cashPx, $onlyPosted);
        $this->assertEqualsWithDelta(
            round($endCash - $beginCash, 2),
            (float) $cf['summary']['net_change_cash_accounts'],
            0.05
        );
    }

    public function test_cash_flow_financing_lines_match_prefix_deltas_from_tb(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $begin = Carbon::parse($from)->subDay()->toDateString();
        $onlyPosted = true;
        $cf = $this->reports->getCashFlowStatement(['from' => $from, 'to' => $to], $onlyPosted);
        $px = config('cash_flow.account_prefixes');

        $st = $px['short_term_borrowings'] ?? ['2.1.3'];
        $expectedSt = $this->reports->balanceSheetDisplayTotalForPrefixes($to, $st, $onlyPosted)
            - $this->reports->balanceSheetDisplayTotalForPrefixes($begin, $st, $onlyPosted);
        $lt = $px['long_term_liabilities'] ?? ['2.2'];
        $expectedLt = $this->reports->balanceSheetDisplayTotalForPrefixes($to, $lt, $onlyPosted)
            - $this->reports->balanceSheetDisplayTotalForPrefixes($begin, $lt, $onlyPosted);
        $eq = $px['equity_financing_prefixes'] ?? ['3.1', '3.2'];
        $expectedEq = $this->reports->balanceSheetDisplayTotalForPrefixes($to, $eq, $onlyPosted)
            - $this->reports->balanceSheetDisplayTotalForPrefixes($begin, $eq, $onlyPosted);

        $lines = collect($cf['financing']['lines'])->keyBy('key');
        $this->assertEqualsWithDelta($expectedSt, (float) $lines['short_term_borrowings']['amount'], 0.05);
        $this->assertEqualsWithDelta($expectedLt, (float) $lines['lt_liabilities']['amount'], 0.05);
        $this->assertEqualsWithDelta($expectedEq, (float) $lines['equity_capital']['amount'], 0.05);
    }

    public function test_cash_flow_section_subtotals_sum_to_net_change_computed(): void
    {
        $cf = $this->reports->getCashFlowStatement([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ], true);
        $expected = round(
            $cf['operating']['subtotal'] + $cf['investing']['subtotal'] + $cf['financing']['subtotal'],
            2
        );
        $this->assertEqualsWithDelta($expected, (float) $cf['summary']['net_change_computed'], 0.05);
    }

    public function test_profit_and_loss_net_income_matches_income_minus_expense_from_tb_in_period(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $pl = $this->reports->getProfitAndLoss(['from' => $from, 'to' => $to], true, false);
        $query = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('a.type', ['income', 'expense'])
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->whereNotNull('j.posted_at')
            ->selectRaw('a.type, SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.type')
            ->get();
        $income = 0.0;
        $expense = 0.0;
        foreach ($query as $r) {
            if ($r->type === 'income') {
                $income += (float) $r->credit - (float) $r->debit;
            } else {
                $expense += (float) $r->debit - (float) $r->credit;
            }
        }
        $expectedNi = round($income - $expense, 2);
        $this->assertEqualsWithDelta($expectedNi, (float) $pl['subtotals']['net_income'], 0.05);
    }

    public function test_dashboard_cash_on_hand_matches_configured_cash_and_bank_prefixes(): void
    {
        Cache::forget('dashboard:data:global:v4');
        $date = now()->toDateString();
        $expected = $this->reports->balanceSheetDisplayTotalForPrefixes(
            $date,
            config('cash_flow.account_prefixes.cash_and_bank', ['1.1.1']),
            true
        );

        $dashboard = app(DashboardDataService::class)->getDashboardData(true);
        $this->assertEqualsWithDelta($expected, (float) $dashboard['kpis']['cash_on_hand'], 0.02);
    }
}
