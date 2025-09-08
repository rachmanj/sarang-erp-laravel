<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');
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
        $response = $this->getJson('/reports/gl-detail?account_id=' . $accountId . '&from=' . $from);
        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('rows', $data);
        foreach ($data['rows'] as $row) {
            $this->assertEquals($accountId, (int) DB::table('accounts')->where('code', $row['account_code'])->value('id'));
        }
    }
}
