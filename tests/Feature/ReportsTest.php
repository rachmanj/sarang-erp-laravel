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
        $csv->assertHeader('Content-Type', 'text/csv');

        $pdf = $this->get('/reports/withholding-recap?export=pdf');
        $pdf->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
    }
}
