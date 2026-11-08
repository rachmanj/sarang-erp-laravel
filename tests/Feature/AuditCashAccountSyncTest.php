<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditCashAccountSyncTest extends TestCase
{
    use RefreshDatabase;

    private const AUDIT_DATE = '2099-01-15';

    private const AUDIT_FROM = '2099-01-01';

    private const AUDIT_TO = '2099-01-31';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_detects_mismatch_when_payment_line_bank_but_journal_credits_cash(): void
    {
        $this->seedMismatchedPurchasePayment();

        $exitCode = Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('1.1.1.02', $output);
        $this->assertStringContainsString('Selisih ditemukan: 1', $output);
        $this->assertStringContainsString('Mode baca saja', $output);
    }

    public function test_fix_moves_journal_cash_line_to_document_bank_account(): void
    {
        $paymentId = $this->seedMismatchedPurchasePayment();
        $bankId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');

        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'purchase_payment',
            'source_id' => $paymentId,
        ])->value('id');

        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $cashId,
        ]);

        $exitCode = Artisan::call('finance:cash-account-sync', [
            '--fix' => true,
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);
        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $bankId,
            'credit' => 500000,
        ]);
        $this->assertDatabaseMissing('journal_lines', [
            'journal_id' => $journalId,
            'account_id' => $cashId,
        ]);

        Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);
        $output = Artisan::output();
        $this->assertStringContainsString('Selisih ditemukan: 0', $output);
    }

    public function test_cash_expense_matching_header_cash_account_and_journal_is_not_reported_as_mismatch(): void
    {
        $this->seedCashExpenseWithJournal(cashAccountCode: '1.1.1.02', journalCashAccountCode: '1.1.1.02');

        $exitCode = Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Pengeluaran Kas', $output);
        $this->assertStringContainsString('Selisih ditemukan: 0', $output);
    }

    public function test_cash_expense_mismatch_when_header_cash_account_differs_from_journal_credit(): void
    {
        $this->seedCashExpenseWithJournal(cashAccountCode: '1.1.1.02', journalCashAccountCode: '1.1.1.01');

        $exitCode = Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Pengeluaran Kas', $output);
        $this->assertStringContainsString('1.1.1.02', $output);
        $this->assertStringContainsString('1.1.1.01', $output);
        $this->assertStringContainsString('Selisih ditemukan: 1', $output);
    }

    public function test_sales_receipt_with_reversal_chain_matches_document_bank_net_without_mismatch(): void
    {
        $this->seedSalesReceiptWithPostReverseRepostChain();

        $exitCode = Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);

        $this->assertSame(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Penerimaan Penjualan', $output);
        $this->assertStringContainsString('Selisih ditemukan: 0', $output);
    }

    public function test_without_fix_does_not_change_journal_lines(): void
    {
        $paymentId = $this->seedMismatchedPurchasePayment();
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $journalId = (int) DB::table('journals')->where([
            'source_type' => 'purchase_payment',
            'source_id' => $paymentId,
        ])->value('id');

        $lineCountBefore = (int) DB::table('journal_lines')->where('journal_id', $journalId)->count();
        $cashCreditBefore = (float) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $cashId)
            ->value('credit');

        Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);
        Artisan::call('finance:cash-account-sync', [
            '--from' => self::AUDIT_FROM,
            '--to' => self::AUDIT_TO,
        ]);

        $lineCountAfter = (int) DB::table('journal_lines')->where('journal_id', $journalId)->count();
        $cashCreditAfter = (float) DB::table('journal_lines')
            ->where('journal_id', $journalId)
            ->where('account_id', $cashId)
            ->value('credit');

        $this->assertSame($lineCountBefore, $lineCountAfter);
        $this->assertEqualsWithDelta($cashCreditBefore, $cashCreditAfter, 0.001);
    }

    private function seedMismatchedPurchasePayment(): int
    {
        $bankId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $apId = (int) DB::table('accounts')->where('code', '2.1.1.01')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $this->assertGreaterThan(0, $bankId);
        $this->assertGreaterThan(0, $cashId);
        $this->assertGreaterThan(0, $apId);

        $paymentId = (int) DB::table('purchase_payments')->insertGetId([
            'payment_no' => 'PP-AUDIT-'.uniqid(),
            'date' => self::AUDIT_DATE,
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'description' => 'Audit cash sync test',
            'total_amount' => 500000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_payment_lines')->insert([
            'payment_id' => $paymentId,
            'account_id' => $bankId,
            'description' => 'CIMB Niaga',
            'amount' => 500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalId = (int) DB::table('journals')->insertGetId([
            'date' => self::AUDIT_DATE,
            'description' => 'Post Purchase Payment #'.$paymentId,
            'source_type' => 'purchase_payment',
            'source_id' => $paymentId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalId,
                'account_id' => $apId,
                'debit' => 500000,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $cashId,
                'debit' => 0,
                'credit' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $paymentId;
    }

    private function seedSalesReceiptWithPostReverseRepostChain(): int
    {
        $bankId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $arId = (int) DB::table('accounts')->where('code', '1.1.2.01')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $amount = 99987000.0;

        $receiptId = (int) DB::table('sales_receipts')->insertGetId([
            'receipt_no' => '71260900001-AUDIT',
            'date' => self::AUDIT_DATE,
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => $amount,
            'total_amount_foreign' => $amount,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_receipt_lines')->insert([
            'receipt_id' => $receiptId,
            'account_id' => $bankId,
            'description' => 'Bank',
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalInitial = (int) DB::table('journals')->insertGetId([
            'date' => self::AUDIT_DATE,
            'description' => 'Post Sales Receipt #'.$receiptId,
            'source_type' => 'sales_receipt',
            'source_id' => $receiptId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalInitial,
                'account_id' => $cashId,
                'debit' => $amount,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalInitial,
                'account_id' => $arId,
                'debit' => 0,
                'credit' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $journalReverse = (int) DB::table('journals')->insertGetId([
            'date' => self::AUDIT_DATE,
            'description' => 'Reverse wrong cash post #'.$receiptId,
            'source_type' => 'sales_receipt',
            'source_id' => $receiptId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalReverse,
                'account_id' => $cashId,
                'debit' => 0,
                'credit' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalReverse,
                'account_id' => $arId,
                'debit' => $amount,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $journalRepost = (int) DB::table('journals')->insertGetId([
            'date' => self::AUDIT_DATE,
            'description' => 'Repost to bank #'.$receiptId,
            'source_type' => 'sales_receipt',
            'source_id' => $receiptId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalRepost,
                'account_id' => $bankId,
                'debit' => $amount,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalRepost,
                'account_id' => $arId,
                'debit' => 0,
                'credit' => $amount,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $receiptId;
    }

    private function seedCashExpenseWithJournal(string $cashAccountCode, string $journalCashAccountCode): int
    {
        $cashAccountId = (int) DB::table('accounts')->where('code', $cashAccountCode)->value('id');
        $journalCashId = (int) DB::table('accounts')->where('code', $journalCashAccountCode)->value('id');
        $expenseAccountId = (int) DB::table('accounts')->where('code', '6.2.5')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $this->assertGreaterThan(0, $cashAccountId);
        $this->assertGreaterThan(0, $journalCashId);
        $this->assertGreaterThan(0, $expenseAccountId);

        $expenseId = (int) DB::table('cash_expenses')->insertGetId([
            'expense_no' => 'CE-AUDIT-'.uniqid(),
            'date' => self::AUDIT_DATE,
            'description' => 'Audit cash expense sync',
            'cash_account_id' => $cashAccountId,
            'total_amount' => 250000,
            'status' => 'posted',
            'created_by' => $userId,
            'company_entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cash_expense_lines')->insert([
            'cash_expense_id' => $expenseId,
            'account_id' => $expenseAccountId,
            'description' => 'Biaya perjalanan',
            'amount' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalId = (int) DB::table('journals')->insertGetId([
            'date' => self::AUDIT_DATE,
            'description' => 'Post Cash Expense #'.$expenseId,
            'source_type' => 'cash_expense',
            'source_id' => $expenseId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalId,
                'account_id' => $expenseAccountId,
                'debit' => 250000,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $journalCashId,
                'debit' => 0,
                'credit' => 250000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $expenseId;
    }
}
