<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationEntry;
use App\Models\AssetDepreciationRun;
use App\Models\AssetDisposal;
use App\Models\AssetMovement;
use App\Models\CompanyEntity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AssetReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AssetCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->seed(RolePermissionSeeder::class);

        CompanyEntity::query()->firstOrCreate(
            ['code' => '71'],
            ['name' => 'Test Entity', 'is_active' => true]
        );

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'assets.view',
            'assets.disposal.view',
            'assets.movement.view',
            'assets.depreciation.run',
        ]);
        $this->actingAs($this->user);

        $this->category = AssetCategory::factory()->create([
            'code' => 'TEST_EQ',
            'name' => 'Test Equipment',
            'non_depreciable' => false,
            'life_months_default' => 36,
        ]);

        Asset::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'status' => 'active',
            'acquisition_cost' => 5000000,
            'salvage_value' => 500000,
            'accumulated_depreciation' => 500000,
            'current_book_value' => 4500000,
            'life_months' => 36,
            'placed_in_service_date' => now()->subYears(1)->toDateString(),
        ]);

        Asset::factory()->create([
            'category_id' => $this->category->id,
            'status' => 'active',
            'acquisition_cost' => 500000,
            'salvage_value' => 50000,
            'accumulated_depreciation' => 10000,
            'current_book_value' => 490000,
            'life_months' => 36,
            'placed_in_service_date' => now()->subMonths(2)->toDateString(),
        ]);
    }

    public function test_asset_reports_index_loads_successfully(): void
    {
        $response = $this->get(route('reports.assets.index'));

        $response->assertOk();
        $response->assertSee('Asset Reports');
        $response->assertSee('Quick Statistics');
    }

    public function test_asset_register_loads_without_sql_errors(): void
    {
        $response = $this->get(route('reports.assets.register'));

        $response->assertOk();
        $response->assertSee('Asset Register');
    }

    public function test_asset_summary_loads_without_sql_errors(): void
    {
        $response = $this->get(route('reports.assets.summary'));

        $response->assertOk();
        $response->assertSee('Asset Summary');
    }

    public function test_asset_aging_loads_without_sql_errors(): void
    {
        $response = $this->get(route('reports.assets.aging'));

        $response->assertOk();
        $response->assertSee('Asset Aging');
    }

    public function test_depreciation_schedule_loads_without_sql_errors(): void
    {
        $asset = Asset::query()->first();
        $period = now()->subMonth()->format('Y-m');

        AssetDepreciationRun::factory()->posted()->create([
            'period' => $period,
            'created_by' => $this->user->id,
            'posted_by' => $this->user->id,
            'asset_count' => 1,
            'total_depreciation' => 125000,
        ]);

        AssetDepreciationEntry::factory()->create([
            'asset_id' => $asset->id,
            'period' => $period,
            'amount' => 125000,
            'book' => 'financial',
        ]);

        $response = $this->get(route('reports.assets.depreciation-schedule'));

        $response->assertOk();
        $response->assertSee('Depreciation Schedule');
    }

    public function test_disposal_summary_and_movement_log_load(): void
    {
        $asset = Asset::query()->first();
        $entityId = CompanyEntity::query()->value('id');

        AssetDisposal::factory()->posted()->create([
            'asset_id' => $asset->id,
            'company_entity_id' => $entityId,
            'created_by' => $this->user->id,
            'posted_by' => $this->user->id,
        ]);

        AssetMovement::factory()->completed()->create([
            'asset_id' => $asset->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->get(route('reports.assets.disposal-summary'))->assertOk();
        $this->get(route('reports.assets.movement-log'))->assertOk();
        $this->get(route('reports.assets.low-value', ['threshold' => 1000000]))->assertOk();
        $this->get(route('reports.assets.depreciation-history'))->assertOk();
    }

    public function test_quick_stats_endpoint_returns_asset_summary_json(): void
    {
        $response = $this->post(route('reports.assets.data'), [
            'report_type' => 'asset_summary',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'by_status',
                'by_category',
                'depreciation' => [
                    'total_depreciation',
                    'total_book_value',
                    'depreciable_assets',
                    'fully_depreciated',
                ],
                'recent_disposals',
                'recent_movements',
            ],
            'generated_at',
        ]);

        $this->assertGreaterThan(0, collect($response->json('data.by_status'))->sum('count'));
    }

    public function test_report_data_endpoint_requires_assets_view_permission(): void
    {
        $unauthorized = User::factory()->create();
        Permission::findOrCreate('assets.view');
        $this->actingAs($unauthorized);

        $response = $this->post(route('reports.assets.data'), [
            'report_type' => 'asset_summary',
        ]);

        $response->assertForbidden();
    }

    public function test_asset_register_index_requires_assets_view_permission(): void
    {
        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized);

        $this->get(route('reports.assets.index'))->assertForbidden();
        $this->get(route('reports.assets.register'))->assertForbidden();
    }
}
