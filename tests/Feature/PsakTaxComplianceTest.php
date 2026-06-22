<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Accounting\YearEndClosingService;
use App\Services\Reports\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsakTaxComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['reports.view', 'tax.view', 'periods.view', 'periods.close']);
        $this->actingAs($user);
    }

    public function test_statement_of_changes_in_equity_returns_structure(): void
    {
        $data = app(ReportService::class)->getStatementOfChangesInEquity([
            'from' => now()->startOfYear()->toDateString(),
            'to' => now()->toDateString(),
        ]);

        $this->assertSame('Statement of Changes in Equity', $data['report_title']);
        $this->assertArrayHasKey('rows', $data);
        $this->assertArrayHasKey('net_income', $data);
        $this->assertArrayHasKey('totals', $data);
    }

    public function test_ppn_reconciliation_and_spt_export(): void
    {
        $service = app(ReportService::class);
        $recon = $service->getPpnReconciliation([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);

        $this->assertArrayHasKey('ppn_keluaran', $recon);
        $this->assertArrayHasKey('ppn_masukan', $recon);
        $this->assertArrayHasKey('net_payable', $recon);

        $spt = $service->exportSptPpn1111([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
        $this->assertSame('SPT-1111', $spt['form']);
    }

    public function test_year_end_closing_posts_balanced_journal_when_pnl_exists(): void
    {
        $service = app(\App\Services\Accounting\PostingService::class);
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $cashId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');

        $service->postJournal([
            'date' => now()->startOfYear()->toDateString(),
            'description' => 'Seed revenue',
            'source_type' => 'test',
            'source_id' => 99,
            'lines' => [
                ['account_id' => $cashId, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenueId, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $journalId = app(YearEndClosingService::class)->closeFiscalYear((int) now()->year);
        $this->assertGreaterThan(0, $journalId);

        $sum = DB::table('journal_lines')->where('journal_id', $journalId)
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }

    public function test_tax_compliance_routes_are_accessible(): void
    {
        $this->get('/tax')->assertOk();
        $this->get('/tax/periods')->assertOk();
        $this->get('/tax/reports')->assertOk();
        $this->get('/tax/calendar')->assertOk();
        $this->get('/reports/statement-of-changes-in-equity')->assertOk();
        $this->get('/reports/ppn-reconciliation')->assertOk();
    }
}
