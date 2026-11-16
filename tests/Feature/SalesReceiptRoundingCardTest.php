<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReceiptRoundingCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.receipts.view', 'ar.receipts.create', 'ar.receipts.post']);
        $this->actingAs($user);
    }

    public function test_create_page_renders_cents_based_rounding_condition(): void
    {
        $response = $this->get(route('sales-receipts.create'));

        $response->assertOk()
            ->assertSee('Math.round(Math.abs(roundingAmount) * 100) >= 1', false)
            ->assertDontSee('Math.abs(roundingAmount) > 0.01', false);
    }

    public function test_posting_receipt_with_one_cent_rounding_succeeds(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $roundingAccountId = (int) DB::table('accounts')->where('code', '7.1.4')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-CENT-'.str_replace('.', '', uniqid('', true)),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 150.01,
            'total_amount_foreign' => 150.01,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post('/sales-receipts', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'One cent rounding',
            'lines' => [
                ['account_id' => $cashId, 'description' => 'Cash', 'amount' => 150.00],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 150.01],
            ],
            'rounding_account_id' => $roundingAccountId,
        ]);
        $response->assertRedirect();

        $receiptId = (int) basename(parse_url((string) $response->headers->get('Location'), PHP_URL_PATH));

        $postResp = $this->post('/sales-receipts/'.$receiptId.'/post');
        $postResp->assertRedirect();
        $postResp->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'status' => 'posted',
        ]);

        $journalId = (int) DB::table('journals')
            ->where('source_type', 'sales_receipt')
            ->where('source_id', $receiptId)
            ->value('id');

        $this->assertGreaterThan(0, $journalId);

        $sums = DB::table('journal_lines')->where('journal_id', $journalId)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sums->d, (float) $sums->c, 0.001);

        $roundingLine = DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $roundingAccountId)
            ->first();

        $this->assertNotNull($roundingLine);
        $this->assertEqualsWithDelta(0.01, (float) $roundingLine->debit, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $roundingLine->credit, 0.001);
    }
}
