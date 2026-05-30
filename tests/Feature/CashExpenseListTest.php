<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashExpenseListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_cash_expense_data_filters_by_date_range(): void
    {
        $entityId = (int) DB::table('company_entities')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $inRangeId = DB::table('cash_expenses')->insertGetId([
            'expense_no' => 'CE-FILTER-IN',
            'date' => '2025-06-15',
            'description' => 'In range',
            'account_id' => $accountId,
            'amount' => 100,
            'status' => 'posted',
            'created_by' => $userId,
            'company_entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outOfRangeId = DB::table('cash_expenses')->insertGetId([
            'expense_no' => 'CE-FILTER-OUT',
            'date' => '2024-01-10',
            'description' => 'Out of range',
            'account_id' => $accountId,
            'amount' => 200,
            'status' => 'posted',
            'created_by' => $userId,
            'company_entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/cash-expenses/data?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 100,
            'from' => '2025-06-01',
            'to' => '2025-06-30',
            'order' => [['column' => 1, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => false, 'orderable' => false],
                ['data' => 'date', 'name' => 'ce.date', 'searchable' => true, 'orderable' => true],
                ['data' => 'description', 'name' => 'ce.description', 'searchable' => true, 'orderable' => true],
                ['data' => 'expense_code', 'name' => 'a.code', 'searchable' => true, 'orderable' => true],
                ['data' => 'expense_name', 'name' => 'a.name', 'searchable' => true, 'orderable' => true],
                ['data' => 'cash_account', 'name' => 'cash_account', 'searchable' => false, 'orderable' => false],
                ['data' => 'creator_name', 'name' => 'u.name', 'searchable' => true, 'orderable' => true],
                ['data' => 'amount', 'name' => 'ce.amount', 'searchable' => false, 'orderable' => false],
                ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
            ],
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($inRangeId, $ids);
        $this->assertNotContains($outOfRangeId, $ids);
    }
}
