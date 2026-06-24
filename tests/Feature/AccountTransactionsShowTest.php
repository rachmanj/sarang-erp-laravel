<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountTransactionsShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_account_show_displays_ledger_with_balances_and_source_link(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts.view', 'ar.invoices.view']);
        $this->actingAs($user);

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $this->assertGreaterThan(0, $arAccountId);
        $this->assertGreaterThan(0, $revenueAccountId);

        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-LEDGER-001',
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 250000,
            'total_amount_foreign' => 250000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $posting = app(PostingService::class);
        $posting->postJournal([
            'date' => now()->toDateString(),
            'description' => 'SI ledger test',
            'source_type' => 'sales_invoice',
            'source_id' => $invoiceId,
            'lines' => [
                ['account_id' => $arAccountId, 'debit' => 250000, 'credit' => 0],
                ['account_id' => $revenueAccountId, 'debit' => 0, 'credit' => 250000],
            ],
        ]);

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $response = $this->get(route('accounts.show', [
            'account' => $arAccountId,
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee('Opening Balance');
        $response->assertSee('Closing Balance');
        $response->assertSee('250,000.00');
        $response->assertSee('Sales Invoice #'.$invoiceId);
        $response->assertSee(route('sales-invoices.show', $invoiceId), false);
    }

    public function test_account_show_requires_accounts_view_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $accountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $this->assertGreaterThan(0, $accountId);

        $this->get(route('accounts.show', $accountId))->assertForbidden();
    }

    public function test_account_index_links_to_show_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts.view']);
        $this->actingAs($user);

        $accountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $this->assertGreaterThan(0, $accountId);

        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee(route('accounts.show', $accountId), false);
        $response->assertSee('View');
    }
}
