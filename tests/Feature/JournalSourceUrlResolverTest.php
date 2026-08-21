<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalSourceUrlResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function postingAccounts(): array
    {
        $cash = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $ap = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id');
        $this->assertGreaterThan(0, $cash);
        $this->assertGreaterThan(0, $ap);

        return [$cash, $ap];
    }

    private function postJournalWithSource(string $sourceType, int $sourceId, array $accounts, string $desc): void
    {
        [$cash, $ap] = $accounts;
        app(PostingService::class)->postJournal([
            'date' => now()->toDateString(),
            'description' => $desc,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'lines' => [
                ['account_id' => $cash, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $ap, 'debit' => 0, 'credit' => 100000],
            ],
        ]);
    }

    public function test_ledger_does_not_link_to_deleted_source_document(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts.view', 'ap.invoices.view']);
        $this->actingAs($user);

        [$cash] = $this->postingAccounts();

        // Journal references a purchase_invoice that no longer exists (hard-deleted).
        $this->postJournalWithSource('purchase_invoice', 999999, $this->postingAccounts(), 'Deleted PI ledger test');

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $response = $this->get(route('accounts.show', [
            'account' => $cash,
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        // Label is still shown as plain text...
        $response->assertSee('Purchase Invoice #999999');
        // ...but there must be NO hyperlink to the deleted document.
        $response->assertDontSee(route('purchase-invoices.show', 999999), false);
    }

    public function test_ledger_links_to_existing_source_document(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['accounts.view', 'ap.invoices.view']);
        $this->actingAs($user);

        [$cash] = $this->postingAccounts();

        $supplierId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $supplierId);
        $this->assertGreaterThan(0, $entityId);
        $this->assertGreaterThan(0, $currencyId);

        $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => 'PI-LEDGER-001',
            'date' => now()->toDateString(),
            'business_partner_id' => $supplierId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJournalWithSource('purchase_invoice', $invoiceId, $this->postingAccounts(), 'Existing PI ledger test');

        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        $response = $this->get(route('accounts.show', [
            'account' => $cash,
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee(route('purchase-invoices.show', $invoiceId), false);
    }
}
