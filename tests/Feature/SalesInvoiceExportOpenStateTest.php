<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class SalesInvoiceExportOpenStateTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_DATE = '2026-01-15';

    private const PREFIX = 'CLK181-SI-';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ap.invoices.view']);
        $this->actingAs($user);
    }

    public function test_sales_invoice_export_without_open_state_includes_open_and_closed(): void
    {
        $ctx = $this->seedSalesInvoicePair();

        $response = $this->get('/sales-invoices/export?'.http_build_query([
            'from' => self::TEST_DATE,
            'to' => self::TEST_DATE,
            'q' => self::PREFIX,
        ]));

        $response->assertOk();
        $invoiceNos = $this->exportSalesInvoiceNumbers($response);

        $this->assertContains($ctx['openInvoiceNo'], $invoiceNos);
        $this->assertContains($ctx['closedInvoiceNo'], $invoiceNos);
        $this->assertCount(2, $invoiceNos);
    }

    public function test_sales_invoice_export_with_open_state_open_excludes_fully_receipted(): void
    {
        $ctx = $this->seedSalesInvoicePair();

        $response = $this->get('/sales-invoices/export?'.http_build_query([
            'from' => self::TEST_DATE,
            'to' => self::TEST_DATE,
            'q' => self::PREFIX,
            'open_state' => 'open',
        ]));

        $response->assertOk();
        $invoiceNos = $this->exportSalesInvoiceNumbers($response);

        $this->assertSame([$ctx['openInvoiceNo']], array_values($invoiceNos));
    }

    public function test_sales_invoice_export_with_open_state_all_includes_both(): void
    {
        $ctx = $this->seedSalesInvoicePair();

        $response = $this->get('/sales-invoices/export?'.http_build_query([
            'from' => self::TEST_DATE,
            'to' => self::TEST_DATE,
            'q' => self::PREFIX,
            'open_state' => 'all',
        ]));

        $response->assertOk();
        $invoiceNos = $this->exportSalesInvoiceNumbers($response);

        $this->assertContains($ctx['openInvoiceNo'], $invoiceNos);
        $this->assertContains($ctx['closedInvoiceNo'], $invoiceNos);
        $this->assertCount(2, $invoiceNos);
    }

    public function test_purchase_invoice_export_without_open_state_includes_open_and_closed(): void
    {
        $ctx = $this->seedPurchaseInvoicePair();

        $response = $this->get('/purchase-invoices/export?'.http_build_query([
            'from' => self::TEST_DATE,
            'to' => self::TEST_DATE,
            'q' => self::PREFIX,
        ]));

        $response->assertOk();
        $invoiceNos = $this->exportPurchaseInvoiceNumbers($response);

        $this->assertContains($ctx['openInvoiceNo'], $invoiceNos);
        $this->assertContains($ctx['closedInvoiceNo'], $invoiceNos);
        $this->assertCount(2, $invoiceNos);
    }

    /**
     * @return array{openInvoiceNo: string, closedInvoiceNo: string}
     */
    private function seedSalesInvoicePair(): array
    {
        $openNo = self::PREFIX.'OPEN-'.uniqid();
        $closedNo = self::PREFIX.'CLOSED-'.uniqid();

        $this->createPostedSalesInvoice($openNo, 500_000, allocated: 0);
        $this->createPostedSalesInvoice($closedNo, 300_000, allocated: 300_000);

        return ['openInvoiceNo' => $openNo, 'closedInvoiceNo' => $closedNo];
    }

    /**
     * @return array{openInvoiceNo: string, closedInvoiceNo: string}
     */
    private function seedPurchaseInvoicePair(): array
    {
        $openNo = self::PREFIX.'PI-OPEN-'.uniqid();
        $closedNo = self::PREFIX.'PI-CLOSED-'.uniqid();

        $this->createPostedPurchaseInvoice($openNo, 400_000, allocated: 0);
        $this->createPostedPurchaseInvoice($closedNo, 250_000, allocated: 250_000);

        return ['openInvoiceNo' => $openNo, 'closedInvoiceNo' => $closedNo];
    }

    private function createPostedSalesInvoice(string $invoiceNo, float $totalAmount, float $allocated): void
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoiceId = (int) DB::table('sales_invoices')->insertGetId([
            'invoice_no' => $invoiceNo,
            'date' => self::TEST_DATE,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => $totalAmount,
            'total_amount_foreign' => $totalAmount,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'item_code' => 'CLK181',
            'item_name' => 'Export open state',
            'qty' => 1,
            'unit_price' => $totalAmount,
            'amount' => $totalAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($allocated > 0) {
            $receiptId = (int) DB::table('sales_receipts')->insertGetId([
                'receipt_no' => 'SR-CLK181-'.uniqid(),
                'date' => self::TEST_DATE,
                'business_partner_id' => $customerId,
                'company_entity_id' => $entityId,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'total_amount' => $allocated,
                'status' => 'posted',
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('sales_receipt_allocations')->insert([
                'receipt_id' => $receiptId,
                'invoice_id' => $invoiceId,
                'amount' => $allocated,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createPostedPurchaseInvoice(string $invoiceNo, float $totalAmount, float $allocated): void
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
            'invoice_no' => $invoiceNo,
            'date' => self::TEST_DATE,
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => $totalAmount,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'description' => 'CLK181 export open state',
            'qty' => 1,
            'unit_price' => $totalAmount,
            'amount' => $totalAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($allocated > 0) {
            $paymentId = (int) DB::table('purchase_payments')->insertGetId([
                'payment_no' => 'PP-CLK181-'.uniqid(),
                'date' => self::TEST_DATE,
                'business_partner_id' => $vendorId,
                'company_entity_id' => $entityId,
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'total_amount' => $allocated,
                'status' => 'posted',
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('purchase_payment_allocations')->insert([
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'amount' => $allocated,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function exportSalesInvoiceNumbers(\Illuminate\Testing\TestResponse $response): array
    {
        $rows = $this->loadExportRows($response);
        $numbers = [];
        foreach (array_slice($rows, 1) as $row) {
            $invoiceNo = trim((string) ($row[1] ?? ''));
            if ($invoiceNo === '' || $invoiceNo === 'Totals (filtered)') {
                continue;
            }
            $numbers[] = $invoiceNo;
        }

        return $numbers;
    }

    /**
     * @return array<int, string>
     */
    private function exportPurchaseInvoiceNumbers(\Illuminate\Testing\TestResponse $response): array
    {
        $rows = $this->loadExportRows($response);
        $numbers = [];
        foreach (array_slice($rows, 1) as $row) {
            if (trim((string) ($row[2] ?? '')) === 'Totals (filtered)') {
                continue;
            }
            $invoiceNo = trim((string) ($row[1] ?? ''));
            if ($invoiceNo === '') {
                continue;
            }
            $numbers[] = $invoiceNo;
        }

        return $numbers;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function loadExportRows(\Illuminate\Testing\TestResponse $response): array
    {
        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);
        $spreadsheet = IOFactory::load($baseResponse->getFile()->getPathname());

        return $spreadsheet->getActiveSheet()->toArray();
    }
}
