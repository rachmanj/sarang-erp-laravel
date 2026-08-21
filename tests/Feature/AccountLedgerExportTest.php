<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountLedgerExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts.view']);
        $this->actingAs($user);
    }

    public function test_export_downloads_xlsx_for_account_ledger(): void
    {
        $account = DB::table('accounts')->where('is_postable', true)->first();
        $this->assertNotNull($account, 'Seeded data must contain a postable account');

        $response = $this->get(route('accounts.export', [
            'account' => $account->id,
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_export_requires_permission(): void
    {
        $account = DB::table('accounts')->where('is_postable', true)->first();

        // Fresh user without accounts.view permission
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('accounts.export', [
            'account' => $account->id,
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]));

        $response->assertForbidden();
    }
}
