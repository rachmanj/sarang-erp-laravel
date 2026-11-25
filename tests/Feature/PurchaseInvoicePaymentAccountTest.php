<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class PurchaseInvoicePaymentAccountTest extends TestCase
{
    use RefreshDatabase;

    private const PREFIX = 'CLK177-PA-';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('ap.invoices.view');
        $this->actingAs($user);
    }

    public function test_cash_invoice_json_includes_cash_account_as_payment_accounts(): void
    {
        $invoiceNo = $this->insertCashInvoice();

        $row = $this->fetchInvoiceRow($invoiceNo);

        $this->assertSame('cash', $row['payment_method']);
        $this->assertStringContainsString('1.1.1.01', (string) ($row['payment_accounts'] ?? ''));
        $this->assertStringContainsString('—', (string) ($row['payment_accounts'] ?? ''));
    }

    public function test_credit_with_posted_payment_shows_bank_account(): void
    {
        $invoiceNo = $this->insertCreditInvoiceWithPostedBankPayment();

        $row = $this->fetchInvoiceRow($invoiceNo);

        $this->assertSame('credit', $row['payment_method']);
        $this->assertStringContainsString('1.1.1.02', (string) ($row['payment_accounts'] ?? ''));
        $this->assertStringContainsString('Kas di Bank', (string) ($row['payment_accounts'] ?? ''));
    }

    public function test_unpaid_credit_has_no_payment_accounts(): void
    {
        $invoiceNo = $this->insertCreditInvoice();

        $row = $this->fetchInvoiceRow($invoiceNo);

        $this->assertSame('credit', $row['payment_method']);
        $this->assertSame('', trim((string) ($row['payment_accounts'] ?? '')));
    }

    public function test_credit_paid_only_via_purchase_advance_does_not_show_dp_account(): void
    {
        $invoiceNo = $this->insertCreditInvoiceWithPostedDpOnlyPayment();

        $row = $this->fetchInvoiceRow($invoiceNo);

        $this->assertSame('credit', $row['payment_method']);
        $paymentAccounts = (string) ($row['payment_accounts'] ?? '');
        $this->assertSame('', trim($paymentAccounts));
        $this->assertStringNotContainsString('Uang Muka', $paymentAccounts);
        $this->assertStringNotContainsString('1.1.6.01', $paymentAccounts);
    }

    public function test_export_has_payment_account_column_and_aligned_totals(): void
    {
        $cashNo = $this->insertCashInvoice();
        $creditPaidNo = $this->insertCreditInvoiceWithPostedBankPayment();

        $response = $this->get('/purchase-invoices/export?'.http_build_query([
            'q' => self::PREFIX,
        ]));

        $response->assertOk();

        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);
        $spreadsheet = IOFactory::load($baseResponse->getFile()->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();
        $header = array_map(fn ($cell) => trim((string) $cell), $rows[0]);

        $paymentMethodIndex = array_search('Payment Method', $header, true);
        $paymentAccountIndex = array_search('Payment Account', $header, true);
        $statusIndex = array_search('Status', $header, true);
        $totalIndex = array_search('Total', $header, true);
        $vatIndex = array_search('VAT', $header, true);
        $amountAfterVatIndex = array_search('Amount After VAT', $header, true);

        $this->assertSame($paymentMethodIndex + 1, $paymentAccountIndex);
        $this->assertSame($paymentAccountIndex + 1, $statusIndex);

        $byInvoiceNo = [];
        foreach (array_slice($rows, 1) as $row) {
            $invoiceNo = trim((string) ($row[1] ?? ''));
            if ($invoiceNo !== '') {
                $byInvoiceNo[$invoiceNo] = $row;
            }
        }

        $this->assertStringContainsString('1.1.1.01', (string) $byInvoiceNo[$cashNo][$paymentAccountIndex]);
        $this->assertStringContainsString('1.1.1.02', (string) $byInvoiceNo[$creditPaidNo][$paymentAccountIndex]);

        $totalsRow = null;
        foreach ($rows as $row) {
            if (trim((string) ($row[2] ?? '')) === 'Totals (filtered)') {
                $totalsRow = $row;
                break;
            }
        }
        $this->assertNotNull($totalsRow);
        $this->assertSame(200000.0, (float) $totalsRow[$totalIndex]);
        $this->assertSame(0.0, (float) $totalsRow[$vatIndex]);
        $this->assertSame(200000.0, (float) $totalsRow[$amountAfterVatIndex]);
        $this->assertSame('', trim((string) ($totalsRow[$paymentMethodIndex] ?? '')));
        $this->assertSame('', trim((string) ($totalsRow[$paymentAccountIndex] ?? '')));
        $this->assertSame('', trim((string) ($totalsRow[$statusIndex] ?? '')));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchInvoiceRow(string $invoiceNo): array
    {
        $response = $this->getJson('/purchase-invoices/data?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'q' => $invoiceNo,
            'order' => [['column' => 0, 'dir' => 'desc']],
            'columns' => [
                ['data' => 'date', 'name' => 'pi.date', 'searchable' => true, 'orderable' => true],
                ['data' => 'invoice_no', 'name' => 'pi.invoice_no', 'searchable' => true, 'orderable' => true],
                ['data' => 'vendor', 'name' => 'v.name', 'searchable' => true, 'orderable' => false],
                ['data' => 'total_amount', 'name' => 'pi.total_amount', 'searchable' => false, 'orderable' => false],
                ['data' => 'total_vat', 'name' => 'total_vat', 'searchable' => false, 'orderable' => false],
                ['data' => 'total_amount_after_vat', 'name' => 'total_amount_after_vat', 'searchable' => false, 'orderable' => false],
                ['data' => 'payment_method', 'name' => 'pi.payment_method', 'searchable' => false, 'orderable' => false],
                ['data' => 'status', 'name' => 'pi.status', 'searchable' => true, 'orderable' => true],
                ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
            ],
        ]));

        $response->assertOk();

        $row = collect($response->json('data'))->firstWhere('invoice_no', $invoiceNo);
        $this->assertNotNull($row);

        return $row;
    }

    private function insertCashInvoice(): string
    {
        $invoiceNo = self::PREFIX.'CASH-'.uniqid();
        $this->insertInvoice($invoiceNo, 'cash', (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'));

        return $invoiceNo;
    }

    private function insertCreditInvoice(): string
    {
        $invoiceNo = self::PREFIX.'CREDIT-'.uniqid();
        $this->insertInvoice($invoiceNo, 'credit', null);

        return $invoiceNo;
    }

    private function insertCreditInvoiceWithPostedBankPayment(): string
    {
        $invoiceNo = $this->insertCreditInvoice();
        $invoiceId = (int) DB::table('purchase_invoices')->where('invoice_no', $invoiceNo)->value('id');
        $bankAccountId = (int) DB::table('accounts')->where('code', '1.1.1.02')->value('id');
        $this->insertPostedPaymentForInvoice($invoiceId, $bankAccountId);

        return $invoiceNo;
    }

    private function insertCreditInvoiceWithPostedDpOnlyPayment(): string
    {
        $invoiceNo = $this->insertCreditInvoice();
        $invoiceId = (int) DB::table('purchase_invoices')->where('invoice_no', $invoiceNo)->value('id');
        $dpAccountId = (int) DB::table('accounts')->where('code', '1.1.6.01')->value('id');
        $this->assertGreaterThan(0, $dpAccountId);
        $this->insertPostedPaymentForInvoice($invoiceId, $dpAccountId);

        return $invoiceNo;
    }

    private function insertInvoice(string $invoiceNo, string $paymentMethod, ?int $cashAccountId): void
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');

        DB::table('purchase_invoices')->insert([
            'invoice_no' => $invoiceNo,
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'currency_id' => $currencyId,
            'company_entity_id' => $entityId,
            'total_amount' => 100000,
            'payment_method' => $paymentMethod,
            'cash_account_id' => $cashAccountId,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPostedPaymentForInvoice(int $invoiceId, int $accountId): void
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');

        $paymentId = (int) DB::table('purchase_payments')->insertGetId([
            'payment_no' => self::PREFIX.'PP-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_payment_lines')->insert([
            'payment_id' => $paymentId,
            'account_id' => $accountId,
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_payment_allocations')->insert([
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
