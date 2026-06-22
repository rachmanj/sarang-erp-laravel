<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\Accounting\PurchaseInvoiceLineTaxMath;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseSalesFlowFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_ordered_purchase_order_can_copy_to_grpo_after_approval(): void
    {
        $po = new PurchaseOrder([
            'order_type' => 'item',
            'approval_status' => 'approved',
            'status' => 'ordered',
        ]);

        $this->assertTrue($po->canCopyToGRPO());
    }

    public function test_sales_invoice_with_wtax_posts_balanced_journal(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post']);
        $this->actingAs($user);

        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'WTax SI test',
            'lines' => [
                [
                    'account_id' => $revenueId,
                    'description' => 'Service with WTax',
                    'qty' => 1,
                    'unit_price' => 100000,
                    'tax_code_id' => $taxCodeId,
                    'wtax_rate' => 2,
                ],
            ],
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));
        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);

        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);

        $arDebit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '1.1.2.01')
            ->value('jl.debit');
        $this->assertEqualsWithDelta(109000.0, $arDebit, 0.01);

        $wtaxDebit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '1.1.4.02')
            ->value('jl.debit');
        $this->assertEqualsWithDelta(2000.0, $wtaxDebit, 0.01);

        $arUnInvoiceCredit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '1.1.2.04')
            ->value('jl.credit');
        $this->assertEqualsWithDelta(111000.0, $arUnInvoiceCredit, 0.01);
    }

    public function test_credit_memo_posting_uses_correct_ppn_math(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post', 'ar.credit-memos.view', 'ar.credit-memos.create', 'ar.credit-memos.post']);
        $this->actingAs($user);

        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Credit memo source',
            'lines' => [
                [
                    'account_id' => $revenueId,
                    'description' => 'Goods',
                    'qty' => 1,
                    'unit_price' => 100000,
                    'tax_code_id' => $taxCodeId,
                ],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));
        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $memoResp = $this->post('/sales-credit-memos', [
            'sales_invoice_id' => $invoiceId,
            'date' => now()->toDateString(),
            'description' => 'Full credit',
        ]);
        $memoResp->assertRedirect();

        $memoId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $memoResp->headers->get('Location'))));
        $this->post('/sales-credit-memos/'.$memoId.'/post')->assertRedirect();

        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_credit_memo', 'source_id' => $memoId])->value('id');
        $ppnDebit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '2.1.2.01')
            ->value('jl.debit');

        $this->assertEqualsWithDelta(11000.0, $ppnDebit, 0.01);

        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }

    public function test_withholding_uses_single_source_when_tax_code_and_wtax_rate_both_set(): void
    {
        $withholdingTax = (object) [
            'type' => 'withholding',
            'rate' => 2.0,
            'code' => 'PPH23',
            'name' => 'PPh Pasal 23',
        ];

        $amount = PurchaseInvoiceLineTaxMath::withholdingAmount(100000, $withholdingTax, 2.0);

        $this->assertEqualsWithDelta(2000.0, $amount, 0.01);
    }

    public function test_sales_invoice_posting_math_percent_rate_for_ppn(): void
    {
        $line = new \App\Models\Accounting\SalesInvoiceLine([
            'qty' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'tax_code_id' => (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id'),
        ]);

        $parts = SalesInvoicePostingMath::splitLineFromTaxExclusivePricing($line);

        $this->assertEqualsWithDelta(11000.0, $parts['output_vat'], 0.01);
        $this->assertEqualsWithDelta(111000.0, $parts['gross'], 0.01);
    }
}
