<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillDraftSalesInvoiceLineAmountsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_backfill_updates_draft_line_amount_and_header_total(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.create', 'ar.invoices.view']);
        $this->actingAs($user);

        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id');
        $this->assertGreaterThan(0, $taxCodeId);

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Draft for backfill',
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
        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', (string) $resp->headers->get('Location'))));

        DB::table('sales_invoice_lines')->where('invoice_id', $invoiceId)->update([
            'amount' => 60000,
        ]);
        DB::table('sales_invoices')->where('id', $invoiceId)->update([
            'total_amount' => 60000,
        ]);

        $exit = Artisan::call('sales-invoices:backfill-draft-line-amounts-from-pricing', [
            '--invoice' => (string) $invoiceId,
        ]);
        $this->assertSame(0, $exit, Artisan::output());

        $this->assertDatabaseHas('sales_invoice_lines', [
            'invoice_id' => $invoiceId,
            'amount' => 66600.00,
        ]);
        $this->assertDatabaseHas('sales_invoices', [
            'id' => $invoiceId,
            'total_amount' => 66600.00,
        ]);
    }

    public function test_dry_run_does_not_persist(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.create', 'ar.invoices.view']);
        $this->actingAs($user);

        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $taxCodeId = (int) DB::table('tax_codes')->where('code', 'PPN11_OUT')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'lines' => [
                [
                    'account_id' => $revenueId,
                    'qty' => 1,
                    'unit_price' => 100,
                    'tax_code_id' => $taxCodeId,
                ],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) preg_replace('/[^0-9]/', '', (string) last(explode('/', (string) $resp->headers->get('Location'))));

        DB::table('sales_invoice_lines')->where('invoice_id', $invoiceId)->update(['amount' => 100]);

        Artisan::call('sales-invoices:backfill-draft-line-amounts-from-pricing', [
            '--invoice' => (string) $invoiceId,
            '--dry-run' => true,
        ]);

        $this->assertDatabaseHas('sales_invoice_lines', [
            'invoice_id' => $invoiceId,
            'amount' => 100.00,
        ]);
    }
}
