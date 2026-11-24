<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class PurchaseInvoiceListPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_HEADERS = [
        'Date',
        'Invoice No',
        'Vendor',
        'Total',
        'VAT',
        'Amount After VAT',
        'Payment Method',
        'Status',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('ap.invoices.view');
        $this->actingAs($user);
    }

    public function test_data_json_includes_payment_method_values(): void
    {
        $ctx = $this->insertTestInvoices();

        $response = $this->getJson('/purchase-invoices/data?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'q' => 'CLK175-PM-',
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

        $byInvoiceNo = collect($response->json('data'))->keyBy('invoice_no');

        $this->assertSame('cash', $byInvoiceNo->get($ctx['cashInvoiceNo'])['payment_method'] ?? null);
        $this->assertSame('credit', $byInvoiceNo->get($ctx['creditInvoiceNo'])['payment_method'] ?? null);
        $this->assertSame('1.1.1.01', $byInvoiceNo->get($ctx['cashInvoiceNo'])['cash_account_code'] ?? null);
    }

    public function test_export_includes_payment_method_column_and_aligned_totals(): void
    {
        $ctx = $this->insertTestInvoices();

        $response = $this->get('/purchase-invoices/export?'.http_build_query([
            'q' => 'CLK175-PM-',
        ]));

        $response->assertOk();

        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);
        $filePath = $baseResponse->getFile()->getPathname();

        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $header = array_map(fn ($cell) => trim((string) $cell), $rows[0]);
        $this->assertSame(self::EXPECTED_HEADERS, $header);

        $paymentMethodIndex = array_search('Payment Method', $header, true);
        $amountAfterVatIndex = array_search('Amount After VAT', $header, true);
        $statusIndex = array_search('Status', $header, true);

        $this->assertSame($amountAfterVatIndex + 1, $paymentMethodIndex);
        $this->assertSame($paymentMethodIndex + 1, $statusIndex);

        $dataByInvoiceNo = [];
        foreach (array_slice($rows, 1) as $row) {
            $invoiceNo = trim((string) ($row[1] ?? ''));
            if ($invoiceNo === '') {
                continue;
            }
            $dataByInvoiceNo[$invoiceNo] = $row;
        }

        $this->assertSame('Cash', trim((string) $dataByInvoiceNo[$ctx['cashInvoiceNo']][$paymentMethodIndex]));
        $this->assertSame('Kredit', trim((string) $dataByInvoiceNo[$ctx['creditInvoiceNo']][$paymentMethodIndex]));

        $totalsRow = null;
        foreach ($rows as $row) {
            if (trim((string) ($row[2] ?? '')) === 'Totals (filtered)') {
                $totalsRow = $row;
                break;
            }
        }
        $this->assertNotNull($totalsRow);

        $totalIndex = array_search('Total', $header, true);
        $vatIndex = array_search('VAT', $header, true);

        $this->assertSame(200000.0, (float) $totalsRow[$totalIndex]);
        $this->assertSame(0.0, (float) $totalsRow[$vatIndex]);
        $this->assertSame(200000.0, (float) $totalsRow[$amountAfterVatIndex]);
        $this->assertSame('', trim((string) ($totalsRow[$paymentMethodIndex] ?? '')));
        $this->assertSame('', trim((string) ($totalsRow[$statusIndex] ?? '')));
    }

    /**
     * @return array{cashInvoiceNo: string, creditInvoiceNo: string, cashAccountId: int}
     */
    private function insertTestInvoices(): array
    {
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');

        $cashInvoiceNo = 'CLK175-PM-CASH-'.uniqid();
        $creditInvoiceNo = 'CLK175-PM-CREDIT-'.uniqid();

        $base = [
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'currency_id' => $currencyId,
            'company_entity_id' => $entityId,
            'total_amount' => 100000,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('purchase_invoices')->insert([
            ...$base,
            'invoice_no' => $cashInvoiceNo,
            'payment_method' => 'cash',
            'cash_account_id' => $cashAccountId,
        ]);

        DB::table('purchase_invoices')->insert([
            ...$base,
            'invoice_no' => $creditInvoiceNo,
            'payment_method' => 'credit',
            'cash_account_id' => null,
        ]);

        return [
            'cashInvoiceNo' => $cashInvoiceNo,
            'creditInvoiceNo' => $creditInvoiceNo,
            'cashAccountId' => $cashAccountId,
        ];
    }
}
