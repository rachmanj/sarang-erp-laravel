<?php

namespace Tests\Feature;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Services\Accounting\JournalBuilders\PurchaseInvoiceJournalBuilder;
use App\Services\Accounting\JournalBuilders\SalesInvoiceJournalBuilder;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountingStartDateGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['accounting.start_date' => '2026-01-01']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function purchaseInvoiceAttributes(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-02-01',
            'business_partner_id' => (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id'),
            'company_entity_id' => (int) DB::table('company_entities')->where('code', '71')->value('id'),
            'currency_id' => (int) DB::table('currencies')->orderBy('id')->value('id'),
            'total_amount' => 1000,
            'status' => 'draft',
        ], $overrides);
    }

    private function accountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function salesInvoiceAttributes(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-02-01',
            'business_partner_id' => (int) DB::table('business_partners')->where('partner_type', 'customer')->orderBy('id')->value('id'),
            'company_entity_id' => (int) DB::table('company_entities')->where('code', '71')->value('id'),
            'currency_id' => (int) DB::table('currencies')->orderBy('id')->value('id'),
            'total_amount' => 1000,
            'status' => 'draft',
        ], $overrides);
    }

    private function postPurchaseInvoiceJournal(PurchaseInvoice $invoice): int
    {
        $draft = app(PurchaseInvoiceJournalBuilder::class)->build($invoice);
        $service = app(PostingService::class);

        return $service->postJournal([
            'date' => $draft->date ?? $invoice->date->toDateString(),
            'description' => $draft->description,
            'source_type' => 'purchase_invoice',
            'source_id' => $invoice->id,
            'lines' => $draft->lines,
        ]);
    }

    private function postSalesInvoiceJournal(SalesInvoice $invoice): int
    {
        $draft = app(SalesInvoiceJournalBuilder::class)->build($invoice);
        $service = app(PostingService::class);

        return $service->postJournal([
            'date' => $draft->date ?? $invoice->date->toDateString(),
            'description' => $draft->description,
            'source_type' => 'sales_invoice',
            'source_id' => $invoice->id,
            'lines' => $draft->lines,
        ]);
    }

    public function test_rejects_new_document_before_accounting_start_date(): void
    {
        $this->expectException(ValidationException::class);

        try {
            PurchaseInvoice::create($this->purchaseInvoiceAttributes([
                'date' => '2025-01-01',
            ]));
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date', $e->errors());
            $this->assertStringContainsString('Tanggal dokumen tidak boleh sebelum periode pembukuan', $e->errors()['date'][0]);
            $this->assertStringContainsString('1 Januari 2026', $e->errors()['date'][0]);

            throw $e;
        }
    }

    public function test_allows_opening_balance_and_manual_opening_journal_before_start_date(): void
    {
        $openingInvoice = PurchaseInvoice::create($this->purchaseInvoiceAttributes([
            'date' => '2025-12-31',
            'is_opening_balance' => true,
        ]));
        $this->assertSame('2025-12-31', $openingInvoice->fresh()->date->toDateString());

        $service = app(PostingService::class);
        $journalId = $service->postJournal([
            'date' => '2025-12-31',
            'description' => 'Saldo awal kas',
            'source_type' => 'manual_journal',
            'source_id' => 0,
            'lines' => [
                ['account_id' => $this->accountId('1.1.1.01'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('3.3.1'), 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $this->assertDatabaseHas('journals', [
            'id' => $journalId,
            'date' => '2025-12-31',
            'source_type' => 'manual_journal',
        ]);
    }

    public function test_allows_resaving_legacy_document_without_changing_date(): void
    {
        $invoice = PurchaseInvoice::withoutEvents(function () {
            return PurchaseInvoice::create($this->purchaseInvoiceAttributes([
                'date' => '2025-12-30',
                'status' => 'draft',
            ]));
        });

        $invoice->status = 'posted';
        $invoice->save();

        $this->assertSame('posted', $invoice->fresh()->status);
        $this->assertSame('2025-12-30', $invoice->fresh()->date->toDateString());
    }

    public function test_rejects_changing_document_date_before_start_date(): void
    {
        $invoice = PurchaseInvoice::withoutEvents(function () {
            return PurchaseInvoice::create($this->purchaseInvoiceAttributes([
                'date' => '2026-03-01',
                'status' => 'draft',
            ]));
        });

        $this->expectException(ValidationException::class);

        try {
            $invoice->date = '2025-06-01';
            $invoice->save();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date', $e->errors());

            throw $e;
        }
    }

    public function test_allows_document_on_or_after_start_date(): void
    {
        $onStart = PurchaseInvoice::create($this->purchaseInvoiceAttributes([
            'date' => '2026-01-01',
        ]));
        $afterStart = PurchaseInvoice::create($this->purchaseInvoiceAttributes([
            'date' => '2026-06-15',
        ]));

        $this->assertSame('2026-01-01', $onStart->fresh()->date->toDateString());
        $this->assertSame('2026-06-15', $afterStart->fresh()->date->toDateString());
    }

    public function test_post_journal_for_opening_balance_purchase_invoice_before_start_date_succeeds(): void
    {
        $invoice = PurchaseInvoice::create($this->purchaseInvoiceAttributes([
            'date' => '2025-12-30',
            'is_opening_balance' => true,
            'payment_method' => 'credit',
        ]));

        PurchaseInvoiceLine::create([
            'invoice_id' => $invoice->id,
            'account_id' => $this->accountId('5.1.01'),
            'description' => 'Saldo awal utang',
            'qty' => 1,
            'unit_price' => 1000,
            'amount' => 1000,
            'net_amount' => 1000,
        ]);

        $journalId = $this->postPurchaseInvoiceJournal($invoice->fresh(['lines']));

        $this->assertDatabaseHas('journals', [
            'id' => $journalId,
            'date' => '2025-12-30',
            'source_type' => 'purchase_invoice',
            'source_id' => $invoice->id,
        ]);
    }

    public function test_post_journal_for_opening_balance_sales_invoice_before_start_date_succeeds(): void
    {
        $invoice = SalesInvoice::create($this->salesInvoiceAttributes([
            'date' => '2025-12-30',
            'is_opening_balance' => true,
        ]));

        SalesInvoiceLine::create([
            'invoice_id' => $invoice->id,
            'account_id' => $this->accountId('4.1.1.01'),
            'description' => 'Saldo awal piutang',
            'qty' => 1,
            'unit_price' => 1000,
            'amount' => 1000,
            'net_amount' => 1000,
        ]);

        $journalId = $this->postSalesInvoiceJournal($invoice->fresh(['lines']));

        $this->assertDatabaseHas('journals', [
            'id' => $journalId,
            'date' => '2025-12-30',
            'source_type' => 'sales_invoice',
            'source_id' => $invoice->id,
        ]);
    }

    public function test_post_journal_rejects_non_opening_balance_purchase_invoice_before_start_date(): void
    {
        $invoice = PurchaseInvoice::withoutEvents(function () {
            $invoice = PurchaseInvoice::create($this->purchaseInvoiceAttributes([
                'date' => '2025-12-30',
                'is_opening_balance' => false,
                'payment_method' => 'credit',
            ]));

            PurchaseInvoiceLine::create([
                'invoice_id' => $invoice->id,
                'account_id' => $this->accountId('5.1.01'),
                'description' => 'Legacy AP',
                'qty' => 1,
                'unit_price' => 1000,
                'amount' => 1000,
                'net_amount' => 1000,
            ]);

            return $invoice;
        });

        $this->expectException(ValidationException::class);

        try {
            $this->postPurchaseInvoiceJournal($invoice->fresh(['lines']));
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date', $e->errors());
            $this->assertStringContainsString('Tanggal dokumen tidak boleh sebelum periode pembukuan', $e->errors()['date'][0]);

            throw $e;
        }
    }

    public function test_post_journal_payload_with_is_opening_balance_key_before_start_date_succeeds(): void
    {
        $service = app(PostingService::class);
        $journalId = $service->postJournal([
            'date' => '2025-12-30',
            'description' => 'Saldo awal via payload flag',
            'source_type' => 'test',
            'source_id' => 0,
            'is_opening_balance' => true,
            'lines' => [
                ['account_id' => $this->accountId('1.1.1.01'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('3.3.1'), 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $this->assertDatabaseHas('journals', [
            'id' => $journalId,
            'date' => '2025-12-30',
            'source_type' => 'test',
        ]);
    }
}
