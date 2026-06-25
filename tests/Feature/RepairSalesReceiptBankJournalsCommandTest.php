<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairSalesReceiptBankJournalsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_dry_run_lists_receipt_with_wrong_cash_account_journal(): void
    {
        $receiptId = $this->createPostedReceiptWithWrongCashJournal();

        $this->artisan('sales-receipts:repair-bank-journals', [
            '--dry-run' => true,
            '--id' => $receiptId,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('71260900007');
    }

    public function test_repair_reverses_wrong_journal_and_reposts_to_selected_bank(): void
    {
        $receiptId = $this->createPostedReceiptWithWrongCashJournal();
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        $cashCoaId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $arCoaId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');

        $this->artisan('sales-receipts:repair-bank-journals', [
            '--id' => $receiptId,
            '--force' => true,
        ])->assertSuccessful();

        $netBank = $this->netForAccount($receiptId, $bankCoaId);
        $netCash = $this->netForAccount($receiptId, $cashCoaId);
        $netAr = $this->netForAccount($receiptId, $arCoaId);

        $this->assertEqualsWithDelta(250.0, $netBank, 0.02);
        $this->assertEqualsWithDelta(0.0, $netCash, 0.02);
        $this->assertEqualsWithDelta(-250.0, $netAr, 0.02);
    }

    private function createPostedReceiptWithWrongCashJournal(): int
    {
        $bankCoaId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        $cashCoaId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $arCoaId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-REPAIR-001',
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

        $receiptId = DB::table('sales_receipts')->insertGetId([
            'receipt_no' => '71260900007',
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

        DB::table('sales_receipt_lines')->insert([
            'receipt_id' => $receiptId,
            'account_id' => $bankCoaId,
            'amount' => 250,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_receipt_allocations')->insert([
            'receipt_id' => $receiptId,
            'invoice_id' => $invoiceId,
            'amount' => 250,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => 'Post Sales Receipt #'.$receiptId,
            'source_type' => 'sales_receipt',
            'source_id' => $receiptId,
            'company_entity_id' => $entityId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalId,
                'account_id' => $cashCoaId,
                'debit' => 250,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $arCoaId,
                'debit' => 0,
                'credit' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $receiptId;
    }

    private function netForAccount(int $receiptId, int $accountId): float
    {
        return (float) DB::table('journals as j')
            ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->where('j.source_type', 'sales_receipt')
            ->where('j.source_id', $receiptId)
            ->where('jl.account_id', $accountId)
            ->selectRaw('COALESCE(SUM(jl.debit), 0) - COALESCE(SUM(jl.credit), 0) as net')
            ->value('net');
    }
}
