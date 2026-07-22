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

    public function test_dashboard_cash_on_hand_matches_configured_petty_cash_account(): void
    {
        Cache::forget('dashboard:data:global:v5');
        $date = now()->toDateString();
        $expected = $this->reports->balanceSheetDisplayTotalForPrefixes(
            $date,
            config('cash_flow.account_prefixes.petty_cash', ['1.1.1.01']),
            true
        );

        $dashboard = app(DashboardDataService::class)->getDashboardData(true);
        $this->assertEqualsWithDelta($expected, (float) $dashboard['kpis']['cash_on_hand'], 0.02);
    }

    public function test_balance_snapshot_cache_isolates_project_filter(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $arId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $this->assertGreaterThan(0, $cashId);
        $this->assertGreaterThan(0, $arId);

        $projectA = (int) DB::table('projects')->insertGetId([
            'code' => 'TST-PROJ-A',
            'name' => 'Test Project A',
            'budget_total' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $projectB = (int) DB::table('projects')->insertGetId([
            'code' => 'TST-PROJ-B',
            'name' => 'Test Project B',
            'budget_total' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => 'ReportAccuracy project filter cache test',
            'source_type' => 'test',
            'source_id' => 99001,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalId,
                'account_id' => $cashId,
                'debit' => 1234.56,
                'credit' => 0,
                'project_id' => $projectA,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $arId,
                'debit' => 0,
                'credit' => 1234.56,
                'project_id' => $projectA,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(ReportService::class);
        $date = now()->toDateString();
        $cashPx = ['1.1.1.01'];

        $withA = $service->balanceSheetDisplayTotalForPrefixes($date, $cashPx, true, ['project_id' => $projectA]);
        $withB = $service->balanceSheetDisplayTotalForPrefixes($date, $cashPx, true, ['project_id' => $projectB]);

        $this->assertNotEquals(
            round($withA, 2),
            round($withB, 2),
            'Snapshot cache must not reuse balances across different project_id filters'
        );
        $this->assertGreaterThanOrEqual(1234.56, $withA);
    }

    public function test_balance_sheet_comparative_includes_prior_amounts(): void
    {
        $asOf = now()->toDateString();
        $priorAsOf = now()->subYear()->toDateString();
        $bs = $this->reports->getBalanceSheet($asOf, true, false, $priorAsOf);

        $this->assertSame($priorAsOf, $bs['prior_as_of']);
        $this->assertNotNull($bs['totals']['prior']);
        $this->assertArrayHasKey('assets', $bs['totals']['prior']);

        $foundPrior = false;
        foreach ($bs['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                if (array_key_exists('prior_amount', $row)) {
                    $foundPrior = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($foundPrior, 'Comparative BS rows should include prior_amount');
    }

    public function test_profit_and_loss_comparative_includes_prior_section_totals(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $priorFrom = now()->subYear()->startOfMonth()->toDateString();
        $priorTo = now()->subYear()->toDateString();

        $pl = $this->reports->getProfitAndLoss([
            'from' => $from,
            'to' => $to,
            'prior_from' => $priorFrom,
            'prior_to' => $priorTo,
        ], true, false);

        $this->assertNotNull($pl['prior_subtotals']);
        $this->assertArrayHasKey('net_income', $pl['prior_subtotals']);
        foreach ($pl['sections'] as $section) {
            $this->assertArrayHasKey('prior_total', $section);
            $this->assertNotNull($section['prior_total']);
        }
    }

    public function test_entity_name_resolves_from_company_entity_filter(): void
    {
        $entity = DB::table('company_entities')->where('code', '71')->first(['id', 'name']);
        $this->assertNotNull($entity);

        $bs = $this->reports->getBalanceSheet(now()->toDateString(), true, true, null, [
            'company_entity_id' => $entity->id,
        ]);
        $this->assertSame($entity->name, $bs['entity_name']);

        $pl = $this->reports->getProfitAndLoss([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'company_entity_id' => $entity->id,
        ]);
        $this->assertSame($entity->name, $pl['entity_name']);

        $tb = $this->reports->getTrialBalance(now()->toDateString(), true, [
            'company_entity_id' => $entity->id,
        ]);
        $this->assertSame($entity->name, $tb['entity_name']);

        $defaultBs = $this->reports->getBalanceSheet(now()->toDateString());
        $this->assertSame((string) config('app.name'), $defaultBs['entity_name']);
    }
}
