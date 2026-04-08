<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['reports.view', 'reports.open-items']);
        $this->actingAs($user);
    }

    public function test_trial_balance_totals_balance(): void
    {
        $response = $this->getJson('/reports/trial-balance');
        $response->assertOk();
        $data = $response->json();
        $this->assertEqualsWithDelta($data['totals']['debit'], $data['totals']['credit'], 0.01);
    }

    public function test_balance_sheet_structure_and_exports(): void
    {
        $response = $this->getJson('/reports/balance-sheet');
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('as_of', $data);
        $this->assertArrayHasKey('sections', $data);
        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('assets', $data['totals']);
        $this->assertArrayHasKey('liabilities', $data['totals']);
        $this->assertArrayHasKey('equity', $data['totals']);
        $this->assertArrayHasKey('difference', $data['totals']);
        $this->assertArrayHasKey('unclosed_pnl_cumulative', $data['totals']);
        $this->assertArrayHasKey('difference_vs_unclosed_pnl', $data['totals']);
        $this->assertEqualsWithDelta(
            (float) $data['totals']['difference'],
            (float) $data['totals']['unclosed_pnl_cumulative'],
            0.05,
        );
        $this->assertEqualsWithDelta(0.0, (float) $data['totals']['difference_vs_unclosed_pnl'], 0.05);
        $this->assertCount(3, $data['sections']);

        $csv = $this->get('/reports/balance-sheet?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));

        $pdf = $this->get('/reports/balance-sheet?export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_profit_loss_structure_and_exports(): void
    {
        $response = $this->getJson('/reports/profit-loss');
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('from', $data);
        $this->assertArrayHasKey('to', $data);
        $this->assertArrayHasKey('sections', $data);
        $this->assertArrayHasKey('subtotals', $data);
        $this->assertArrayHasKey('gross_profit', $data['subtotals']);
        $this->assertArrayHasKey('operating_income', $data['subtotals']);
        $this->assertArrayHasKey('net_income', $data['subtotals']);
        $this->assertCount(5, $data['sections']);

        $csv = $this->get('/reports/profit-loss?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));

        $pdf = $this->get('/reports/profit-loss?export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_trial_balance_csv_and_pdf_export(): void
    {
        $csv = $this->get('/reports/trial-balance?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));

        $pdf = $this->get('/reports/trial-balance?export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_gl_detail_csv_and_pdf_export(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $q = 'from='.$from.'&to='.$to;

        $csv = $this->get('/reports/gl-detail?'.$q.'&export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));

        $pdf = $this->get('/reports/gl-detail?'.$q.'&export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cash_flow_statement_structure_and_exports(): void
    {
        $response = $this->getJson('/reports/cash-flow-statement');
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('from', $data);
        $this->assertArrayHasKey('to', $data);
        $this->assertArrayHasKey('operating', $data);
        $this->assertArrayHasKey('investing', $data);
        $this->assertArrayHasKey('financing', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('lines', $data['operating']);
        $this->assertArrayHasKey('subtotal', $data['operating']);
        $this->assertArrayHasKey('net_change_computed', $data['summary']);
        $this->assertArrayHasKey('reconciliation_difference', $data['summary']);

        $csv = $this->get('/reports/cash-flow-statement?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));

        $pdf = $this->get('/reports/cash-flow-statement?export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_gl_detail_filters_by_account_and_date(): void
    {
        $accountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $from = now()->toDateString();
        $response = $this->getJson('/reports/gl-detail?account_id='.$accountId.'&from='.$from);
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('rows', $data);
        foreach ($data['rows'] as $row) {
            $this->assertEquals($accountId, (int) DB::table('accounts')->where('code', $row['account_code'])->value('id'));
        }
    }

    public function test_ar_aging_has_totals_and_names(): void
    {
        $response = $this->getJson('/reports/ar-aging');
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('rows', $data);
        $this->assertArrayHasKey('totals', $data);
        // Rows may be empty, but structure must include keys
        if (! empty($data['rows'])) {
            $row = $data['rows'][0];
            $this->assertArrayHasKey('customer_id', $row);
            $this->assertArrayHasKey('total', $row);
        }
    }

    public function test_cash_ledger_supports_opening_balance_and_account_filter(): void
    {
        $accountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $response = $this->getJson('/reports/cash-ledger?account_id='.$accountId.'&from='.$from.'&to='.$to);
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('rows', $data);
        $this->assertArrayHasKey('opening_balance', $data);
    }

    public function test_open_items_index_loads_without_error(): void
    {
        $response = $this->get('/reports/open-items');
        $response->assertOk();
        $response->assertSee('Open Items', false);
    }

    public function test_document_creation_logs_index_loads_without_error(): void
    {
        $response = $this->get('/reports/document-creation-logs');
        $response->assertOk();
        $response->assertSee('Document Creation Logs', false);
    }

    public function test_open_items_export_route_is_not_shadowed_by_document_type(): void
    {
        $response = $this->getJson('/reports/open-items/export/excel');
        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_withholding_recap_structure_and_csv_pdf(): void
    {
        $resp = $this->getJson('/reports/withholding-recap');
        $resp->assertOk();
        $json = $resp->json();
        $this->assertArrayHasKey('rows', $json);
        $this->assertArrayHasKey('totals', $json);

        $csv = $this->get('/reports/withholding-recap?export=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));

        $pdf = $this->get('/reports/withholding-recap?export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }
}
