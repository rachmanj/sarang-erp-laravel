<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_journal_show_displays_lines_and_source_link(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['journals.view', 'ar.invoices.view']);
        $this->actingAs($user);

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $this->assertGreaterThan(0, $arAccountId);
        $this->assertGreaterThan(0, $revenueAccountId);

        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-JOURNAL-SHOW-001',
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

        $journalId = app(PostingService::class)->postJournal([
            'date' => now()->toDateString(),
            'description' => 'SI journal show test',
            'source_type' => 'sales_invoice',
            'source_id' => $invoiceId,
            'lines' => [
                ['account_id' => $arAccountId, 'debit' => 250000, 'credit' => 0],
                ['account_id' => $revenueAccountId, 'debit' => 0, 'credit' => 250000],
            ],
        ]);

        $journalNo = DB::table('journals')->where('id', $journalId)->value('journal_no');

        $response = $this->get(route('journals.show', $journalId));

        $response->assertOk();
        $response->assertSee($journalNo);
        $response->assertSee('SI journal show test');
        $response->assertSee('250,000.00');
        $response->assertSee('Sales Invoice #'.$invoiceId);
        $response->assertSee(route('sales-invoices.show', $invoiceId), false);
    }

    public function test_journal_show_requires_journals_view_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

        $journalId = app(PostingService::class)->postJournal([
            'date' => now()->toDateString(),
            'description' => 'Permission test journal',
            'source_type' => 'manual_journal',
            'source_id' => 0,
            'lines' => [
                ['account_id' => $arAccountId, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $revenueAccountId, 'debit' => 0, 'credit' => 50000],
            ],
        ]);

        $this->get(route('journals.show', $journalId))->assertForbidden();
    }

    public function test_journals_data_actions_include_view_link(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['journals.view']);
        $this->actingAs($user);

        $arAccountId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

        $journalId = app(PostingService::class)->postJournal([
            'date' => now()->toDateString(),
            'description' => 'Journal data actions test',
            'source_type' => 'manual_journal',
            'source_id' => 0,
            'lines' => [
                ['account_id' => $arAccountId, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $revenueAccountId, 'debit' => 0, 'credit' => 100000],
            ],
        ]);

        $response = $this->getJson(route('journals.data'));

        $response->assertOk();
        $response->assertJsonFragment([
            'actions' => '<a href="'.route('journals.show', $journalId).'" class="btn btn-info btn-xs mr-1"><i class="fas fa-eye"></i> View</a>'
                .'<button type="button" class="btn btn-danger btn-xs reverse-button" data-id="'.$journalId.'" data-url="'.route('journals.reverse', $journalId).'">Reverse</button>',
        ]);
    }
}
