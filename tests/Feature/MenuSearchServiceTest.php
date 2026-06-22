<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MenuSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_includes_week_june_2026_menu_destinations_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'reports.view',
            'bank_accounts.view',
            'bank_reconciliation.view',
            'tax.view',
            'inventory.view',
            'access-domain-assistant',
        ]);
        $this->actingAs($user);

        $titles = collect(app(MenuSearchService::class)->getSearchableMenuItems())
            ->pluck('title')
            ->all();

        $expected = [
            'Bank Accounts',
            'Bank Reconciliation',
            'Tax Compliance',
            'Statement of Changes in Equity',
            'Subledger Reconciliation',
            'PPN Reconciliation',
            'Inventory Dashboard',
            'Inventory Detail Report',
            'Domain Assistant',
        ];

        foreach ($expected as $title) {
            $this->assertContains($title, $titles, "Missing menu search item: {$title}");
        }
    }

    public function test_menu_search_api_returns_new_report_routes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['reports.view']);
        $this->actingAs($user);

        $response = $this->getJson('/api/menu/search');
        $response->assertOk();

        $titles = collect($response->json('items'))->pluck('title')->all();

        $this->assertContains('Statement of Changes in Equity', $titles);
        $this->assertContains('Subledger Reconciliation', $titles);
        $this->assertContains('PPN Reconciliation', $titles);
    }

    public function test_menu_search_api_includes_bank_reconciliation_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['bank_reconciliation.view']);
        $this->actingAs($user);

        $response = $this->getJson('/api/menu/search');
        $response->assertOk();

        $titles = collect($response->json('items'))->pluck('title')->all();

        $this->assertContains('Bank Reconciliation', $titles);
    }
}
