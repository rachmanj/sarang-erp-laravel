<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArInvoicePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post']);
        $this->actingAs($user);
    }

    public function test_posting_invoice_creates_balanced_journal(): void
    {
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');

        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Test AR',
            'lines' => [
                ['account_id' => $revenueId, 'description' => 'Service', 'qty' => 1, 'unit_price' => 100],
            ],
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));
        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'total_amount' => 100.00]);

        $postResp = $this->post('/sales-invoices/'.$invoiceId.'/post');
        $postResp->assertRedirect();

        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'posted']);
        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);
        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }

    public function test_posting_tax_inclusive_line_with_eleven_percent_vat_splits_ar_and_ppn(): void
    {
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id');

        $this->assertGreaterThan(0, $taxCodeId);

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Test VAT-inclusive SI',
            'lines' => [
                [
                    'account_id' => $revenueId,
                    'description' => 'Goods',
                    'qty' => 2,
                    'unit_price' => 30000,
                    'tax_code_id' => $taxCodeId,
                ],
            ],
        ]);
        $resp->assertRedirect();

        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));

        DB::table('sales_invoice_lines')->where('invoice_id', $invoiceId)->update([
            'amount' => 66600,
            'tax_code_id' => $taxCodeId,
        ]);
        DB::table('sales_invoices')->where('id', $invoiceId)->update(['total_amount' => 66600]);

        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $jid = (int) DB::table('journals')->where(['source_type' => 'sales_invoice', 'source_id' => $invoiceId])->value('id');
        $this->assertGreaterThan(0, $jid);

        $arDebit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '1.1.2.01')
            ->value('jl.debit');
        $ppnCredit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '2.1.2')
            ->value('jl.credit');
        $arUnInvoiceCredit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '1.1.2.04')
            ->value('jl.credit');
        $revenueDebit = (float) DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $jid)
            ->where('a.code', '4.1.1')
            ->value('jl.debit');

        $this->assertEqualsWithDelta(66600.0, $arDebit, 0.01);
        $this->assertEqualsWithDelta(66600.0, $arUnInvoiceCredit, 0.01);
        $this->assertEqualsWithDelta(6600.0, $ppnCredit, 0.01);
        $this->assertEqualsWithDelta(6600.0, $revenueDebit, 0.01);

        $sum = DB::table('journal_lines')->where('journal_id', $jid)->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEqualsWithDelta((float) $sum->d, (float) $sum->c, 0.01);
    }

    public function test_validate_posted_journals_command_exits_zero(): void
    {
        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'For validate command',
            'lines' => [
                ['account_id' => $revenueId, 'description' => 'Line', 'qty' => 1, 'unit_price' => 250],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', $resp->headers->get('Location'))));
        $this->post('/sales-invoices/'.$invoiceId.'/post')->assertRedirect();

        $exit = Artisan::call('sales-invoices:validate-posted-journals', ['--id' => (string) $invoiceId]);
        $this->assertSame(0, $exit, Artisan::output());
    }
}
