<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReceiptBankPostingTest extends TestCase
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

    public function test_posting_receipt_uses_selected_bank_account_from_lines(): void
    {
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        if (! $bankCoaId) {
            $bankCoaId = DB::table('accounts')->insertGetId([
                'code' => '1.1.1.02',
                'name' => 'Kas di Bank - Operasional',
                'type' => 'asset',
                'is_postable' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-BANK-001',
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 250,
            'total_amount_foreign' => 250,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->post('/sales-receipts', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'Bank receipt',
            'lines' => [
                ['account_id' => $bankCoaId, 'description' => 'Bank Mandiri', 'amount' => 250],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceId, 'amount' => 250],
            ],
        ]);

        $resp->assertRedirect();
        $location = $resp->headers->get('Location');
        $receiptId = (int) preg_replace('/[^0-9]/', '', (string) substr($location, strrpos($location, '/')));

        $this->post('/sales-receipts/'.$receiptId.'/post')->assertRedirect();

        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'sales_receipt',
            'source_id' => $receiptId,
        ])->value('id');

        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $bankCoaId,
            'debit' => 250.00,
            'credit' => 0,
        ]);
    }
}
