<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseAdvanceTest extends TestCase
{
    use RefreshDatabase;

    private int $entityId;

    private int $vendorId;

    private int $cashAccountId;

    private int $advanceAccountId;

    private int $apAccountId;

    private int $expenseAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();

        $this->entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $this->vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $this->cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $this->advanceAccountId = (int) DB::table('accounts')->where('code', '1.1.6.01')->value('id');
        $this->apAccountId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id');
        $this->expenseAccountId = (int) DB::table('accounts')->where('code', '6.2.1')->value('id');

        $user = User::factory()->create();
        $user->givePermissionTo([
            'ap.payments.view',
            'ap.payments.create',
            'ap.payments.post',
            'ap.invoices.view',
            'ap.invoices.create',
            'ap.invoices.post',
            'accounts.view',
        ]);
        $this->actingAs($user);
    }

    public function test_full_flow_po_advance_invoice_apply_and_payment(): void
    {
        $poTotal = 100_000_000.0;
        $dpAmount = 30_000_000.0;
        $poId = $this->createPurchaseOrder($poTotal);

        $advanceId = $this->createAndPostAdvance($poId, $dpAmount);

        $advanceJournalId = (int) DB::table('journals')
            ->where('source_type', 'purchase_advance')
            ->where('source_id', $advanceId)
            ->value('id');
        $this->assertAdvanceJournalDebitCredit($advanceJournalId, $this->advanceAccountId, $dpAmount, $this->cashAccountId, $dpAmount);

        $invoiceId = $this->createAndPostInvoice($poId, $poTotal);

        $applyResp = $this->post('/purchase-advances/'.$advanceId.'/apply', [
            'invoice_id' => $invoiceId,
            'amount' => $dpAmount,
            'date' => now()->toDateString(),
        ]);
        $applyResp->assertRedirect();
        $applyResp->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_advance_applications', [
            'advance_id' => $advanceId,
            'invoice_id' => $invoiceId,
            'amount' => $dpAmount,
        ]);

        $paymentId = (int) DB::table('purchase_advance_applications')
            ->where('advance_id', $advanceId)
            ->value('payment_id');
        $this->assertGreaterThan(0, $paymentId);

        $applyJournalId = (int) DB::table('journals')
            ->where('source_type', 'purchase_payment')
            ->where('source_id', $paymentId)
            ->value('id');
        $this->assertAdvanceJournalDebitCredit($applyJournalId, $this->apAccountId, $dpAmount, $this->advanceAccountId, $dpAmount);

        $payResp = $this->post('/purchase-payments', [
            'date' => now()->toDateString(),
            'business_partner_id' => $this->vendorId,
            'company_entity_id' => $this->entityId,
            'description' => 'Pelunasan sisa',
            'lines' => [
                ['account_id' => $this->cashAccountId, 'description' => 'Bank', 'amount' => 70_000_000],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 70_000_000],
            ],
        ]);
        $payResp->assertRedirect();
        $payResp->assertSessionHasNoErrors();

        $finalPaymentId = (int) basename(parse_url((string) $payResp->headers->get('Location'), PHP_URL_PATH));
        $postPay = $this->post('/purchase-payments/'.$finalPaymentId.'/post');
        $postPay->assertRedirect();

        $usedDp = (float) DB::table('purchase_advance_applications')->where('advance_id', $advanceId)->sum('amount');
        $totalDp = (float) DB::table('purchase_advances')->where('id', $advanceId)->value('total_amount');
        $this->assertEqualsWithDelta($totalDp, $usedDp, 0.01);

        $allocated = (float) DB::table('purchase_payment_allocations')->where('invoice_id', $invoiceId)->sum('amount');
        $invoiceTotal = (float) DB::table('purchase_invoices')->where('id', $invoiceId)->value('total_amount');
        $this->assertEqualsWithDelta($invoiceTotal, $allocated, 0.01);

        $this->assertTrialBalanceBalanced();
        $this->assertAdvanceAccountMatchesUnusedPostedDp();
    }

    public function test_apply_exceeding_advance_balance_is_rejected(): void
    {
        $advanceId = $this->createAndPostAdvance(null, 10_000_000);
        $invoiceId = $this->createAndPostInvoice(null, 50_000_000);

        $countBefore = (int) DB::table('purchase_advance_applications')->count();

        $resp = $this->post('/purchase-advances/'.$advanceId.'/apply', [
            'invoice_id' => $invoiceId,
            'amount' => 15_000_000,
            'date' => now()->toDateString(),
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('error');

        $this->assertSame($countBefore, (int) DB::table('purchase_advance_applications')->count());
    }

    public function test_apply_to_invoice_from_other_supplier_is_rejected(): void
    {
        $otherVendorId = (int) DB::table('business_partners')
            ->where('partner_type', 'supplier')
            ->where('id', '!=', $this->vendorId)
            ->value('id');

        if ($otherVendorId <= 0) {
            $otherVendorId = DB::table('business_partners')->insertGetId([
                'code' => 'V-OTHER-'.uniqid(),
                'name' => 'Supplier Lain Test',
                'partner_type' => 'supplier',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $advanceId = $this->createAndPostAdvance(null, 5_000_000);

        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $otherInvoiceId = (int) DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => 'PI-OTHER-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $otherVendorId,
            'company_entity_id' => $this->entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 20_000_000,
            'total_amount_foreign' => 20_000_000,
            'status' => 'posted',
            'posted_at' => now(),
            'is_direct_purchase' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->post('/purchase-advances/'.$advanceId.'/apply', [
            'invoice_id' => $otherInvoiceId,
            'amount' => 5_000_000,
            'date' => now()->toDateString(),
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('error');
        $this->assertStringContainsString(
            'supplier lain',
            strtolower((string) $resp->getSession()->get('error'))
        );
    }

    public function test_cancelled_advance_cannot_be_applied(): void
    {
        $advanceId = $this->createAndPostAdvance(null, 8_000_000);
        $cancel = $this->post('/purchase-advances/'.$advanceId.'/cancel');
        $cancel->assertRedirect();

        $invoiceId = $this->createAndPostInvoice(null, 20_000_000);

        $resp = $this->post('/purchase-advances/'.$advanceId.'/apply', [
            'invoice_id' => $invoiceId,
            'amount' => 5_000_000,
            'date' => now()->toDateString(),
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHas('error');
    }

    public function test_advance_with_applications_cannot_be_cancelled(): void
    {
        $advanceId = $this->createAndPostAdvance(null, 6_000_000);
        $invoiceId = $this->createAndPostInvoice(null, 20_000_000);

        $apply = $this->post('/purchase-advances/'.$advanceId.'/apply', [
            'invoice_id' => $invoiceId,
            'amount' => 3_000_000,
            'date' => now()->toDateString(),
        ]);
        $apply->assertRedirect();
        $apply->assertSessionHas('success');

        $cancel = $this->post('/purchase-advances/'.$advanceId.'/cancel');
        $cancel->assertRedirect();
        $cancel->assertSessionHas('error');

        $this->assertDatabaseMissing('purchase_advances', ['id' => $advanceId, 'status' => 'cancelled']);
    }

    private function createPurchaseOrder(float $total): int
    {
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        return (int) DB::table('purchase_orders')->insertGetId([
            'order_no' => 'PO-DP-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $this->vendorId,
            'company_entity_id' => $this->entityId,
            'currency_id' => $currencyId,
            'total_amount' => $total,
            'status' => 'ordered',
            'approval_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAndPostAdvance(?int $poId, float $amount): int
    {
        $payload = [
            'date' => now()->toDateString(),
            'business_partner_id' => $this->vendorId,
            'company_entity_id' => $this->entityId,
            'description' => $poId ? null : 'DP tanpa PO wajib deskripsi',
            'lines' => [
                ['account_id' => $this->cashAccountId, 'description' => 'Bayar DP', 'amount' => $amount],
            ],
        ];
        if ($poId) {
            $payload['purchase_order_id'] = $poId;
        }

        $resp = $this->post('/purchase-advances', $payload);
        $resp->assertRedirect();
        $resp->assertSessionHasNoErrors();

        $advanceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));

        $post = $this->post('/purchase-advances/'.$advanceId.'/post');
        $post->assertRedirect();
        $post->assertSessionHas('success');

        return $advanceId;
    }

    private function createAndPostInvoice(?int $poId, float $total): int
    {
        $payload = [
            'date' => now()->toDateString(),
            'business_partner_id' => $this->vendorId,
            'company_entity_id' => $this->entityId,
            'payment_method' => 'credit',
            'description' => 'Faktur test DP',
            'lines' => [
                ['account_id' => $this->expenseAccountId, 'description' => 'Barang', 'qty' => 1, 'unit_price' => $total],
            ],
        ];
        if ($poId) {
            $payload['purchase_order_id'] = $poId;
        }

        $resp = $this->post('/purchase-invoices', $payload);
        $resp->assertRedirect();
        $resp->assertSessionHasNoErrors();

        $invoiceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));

        $post = $this->post('/purchase-invoices/'.$invoiceId.'/post');
        $post->assertRedirect();

        return $invoiceId;
    }

    private function assertAdvanceJournalDebitCredit(int $journalId, int $debitAccountId, float $debit, int $creditAccountId, float $credit): void
    {
        $this->assertGreaterThan(0, $journalId);
        $debitLine = (float) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $debitAccountId)
            ->value('debit');
        $creditLine = (float) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $creditAccountId)
            ->value('credit');
        $this->assertEqualsWithDelta($debit, $debitLine, 0.01);
        $this->assertEqualsWithDelta($credit, $creditLine, 0.01);

        $sum = DB::table('journal_lines')->where('journal_id', $journalId)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }

    private function assertTrialBalanceBalanced(): void
    {
        $sums = DB::table('journal_lines')->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sums->d, (float) $sums->c, 0.01);
    }

    private function assertAdvanceAccountMatchesUnusedPostedDp(): void
    {
        $accountBalance = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.code', '1.1.6.01')
            ->selectRaw('COALESCE(SUM(jl.debit),0) - COALESCE(SUM(jl.credit),0) as bal')
            ->value('bal');

        $postedTotal = (float) DB::table('purchase_advances')->where('status', 'posted')->sum('total_amount');
        $usedTotal = (float) DB::table('purchase_advance_applications')->sum('amount');
        $expectedUnused = $postedTotal - $usedTotal;

        $this->assertEqualsWithDelta($expectedUnused, $accountBalance, 0.01);
    }
}
