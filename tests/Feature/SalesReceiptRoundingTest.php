<?php

namespace Tests\Feature;

use App\Models\ErpParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReceiptRoundingTest extends TestCase
{
    use RefreshDatabase;

    private int $cashId;

    private int $customerId;

    private int $entityId;

    private int $currencyId;

    private int $roundingAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.receipts.view', 'ar.receipts.create', 'ar.receipts.post']);
        $this->actingAs($user);

        $this->cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $this->customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $this->entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $this->currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $this->roundingAccountId = (int) ErpParameter::get('rounding_account_id');
    }

    private function createPostedInvoice(float $amount): int
    {
        return (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-ROUND-'.str_replace('.', '', uniqid('', true)),
            'date' => now()->toDateString(),
            'business_partner_id' => $this->customerId,
            'company_entity_id' => $this->entityId,
            'currency_id' => $this->currencyId,
            'exchange_rate' => 1,
            'total_amount' => $amount,
            'total_amount_foreign' => $amount,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function storeReceipt(int $invoiceId, float $allocation, float $cash, ?int $roundingAccountId = null): \Illuminate\Testing\TestResponse
    {
        $payload = [
            'date' => now()->toDateString(),
            'business_partner_id' => $this->customerId,
            'company_entity_id' => $this->entityId,
            'description' => 'Rounding test',
            'lines' => [
                ['account_id' => $this->cashId, 'description' => 'Cash', 'amount' => $cash],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => $allocation],
            ],
        ];

        if ($roundingAccountId !== null) {
            $payload['rounding_account_id'] = $roundingAccountId;
        }

        return $this->post('/sales-receipts', $payload);
    }

    public function test_overpayment_within_tolerance_posts_rounding_gain_and_closes_invoice(): void
    {
        $invoiceId = $this->createPostedInvoice(8245999.99);

        $response = $this->storeReceipt($invoiceId, 8245999.99, 8246000.00, $this->roundingAccountId);
        $response->assertRedirect();

        $receiptId = (int) basename(parse_url((string) $response->headers->get('Location'), PHP_URL_PATH));

        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'total_amount' => 8246000.00,
            'rounding_amount' => 0.01,
            'rounding_account_id' => $this->roundingAccountId,
        ]);

        $this->post('/sales-receipts/'.$receiptId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')
            ->where('source_type', 'sales_receipt')
            ->where('source_id', $receiptId)
            ->value('id');

        $this->assertGreaterThan(0, $journalId);

        $roundingLine = DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $this->roundingAccountId)
            ->first();

        $this->assertNotNull($roundingLine);
        $this->assertEqualsWithDelta(0.01, (float) $roundingLine->credit, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $roundingLine->debit, 0.001);

        $debitSum = (float) DB::table('journal_lines')->where('journal_id', $journalId)->sum('debit');
        $creditSum = (float) DB::table('journal_lines')->where('journal_id', $journalId)->sum('credit');
        $this->assertEqualsWithDelta($debitSum, $creditSum, 0.01);

        $this->assertDatabaseHas('sales_invoices', [
            'id' => $invoiceId,
            'closure_status' => 'closed',
        ]);
    }

    public function test_underpayment_within_tolerance_posts_rounding_loss(): void
    {
        $invoiceId = $this->createPostedInvoice(1000.85);

        $response = $this->storeReceipt($invoiceId, 1000.85, 1000.00, $this->roundingAccountId);
        $response->assertRedirect();

        $receiptId = (int) basename(parse_url((string) $response->headers->get('Location'), PHP_URL_PATH));

        $this->assertDatabaseHas('sales_receipts', [
            'id' => $receiptId,
            'rounding_amount' => -0.85,
        ]);

        $this->post('/sales-receipts/'.$receiptId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')
            ->where('source_type', 'sales_receipt')
            ->where('source_id', $receiptId)
            ->value('id');

        $roundingLine = DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $this->roundingAccountId)
            ->first();

        $this->assertNotNull($roundingLine);
        $this->assertEqualsWithDelta(0.85, (float) $roundingLine->debit, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $roundingLine->credit, 0.001);
    }

    public function test_difference_beyond_tolerance_is_rejected(): void
    {
        ErpParameter::updateOrCreate(
            ['parameter_key' => 'sales_receipt_rounding_tolerance'],
            [
                'category' => 'payment_settings',
                'parameter_name' => 'Sales Receipt Rounding Tolerance',
                'parameter_value' => '1',
                'data_type' => 'decimal',
                'is_active' => true,
            ]
        );

        $invoiceId = $this->createPostedInvoice(1000.00);

        $response = $this->storeReceipt($invoiceId, 1000.00, 1005.00, $this->roundingAccountId);
        $response->assertSessionHasErrors('lines');
    }

    public function test_missing_rounding_account_when_difference_exists_is_rejected(): void
    {
        ErpParameter::where('parameter_key', 'rounding_account_id')->delete();

        $invoiceId = $this->createPostedInvoice(1000.00);

        $response = $this->storeReceipt($invoiceId, 1000.00, 1000.50, null);
        $response->assertSessionHasErrors('lines');
    }
}
