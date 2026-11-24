<?php

namespace Tests\Feature;

use App\Models\Accounting\SalesInvoice;
use App\Models\BusinessPartner;
use App\Models\CompanyEntity;
use App\Models\ErpParameter;
use App\Services\Accounting\SalesInvoicePostingMath;
use Database\Seeders\CompanyInfoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesInvoicePrintLetterheadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(CompanyInfoSeeder::class);
    }

    private function createMinimalInvoice(int $companyEntityId): SalesInvoice
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-SI-LH-'.uniqid(),
            'name' => 'Letterhead Test Customer',
            'partner_type' => 'customer',
        ]);

        $currencyId = (int) DB::table('currencies')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoice = SalesInvoice::query()->create([
            'invoice_no' => 'T-SI-LH-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $partner->id,
            'company_entity_id' => $companyEntityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoice->id,
            'account_id' => $accountId,
            'item_name' => 'Letterhead test line',
            'description' => 'Test',
            'qty' => 1,
            'unit_price' => 100000,
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $invoice->load([
            'lines.account',
            'lines.taxCode',
            'lines.inventoryItem',
            'businessPartner.primaryAddress',
            'businessPartner.addresses',
            'companyEntity',
        ]);
    }

    private function csjEntityId(): int
    {
        $id = CompanyEntity::query()->where('code', CompanyEntity::DEFAULT_CODE)->value('id');

        $this->assertNotNull($id);

        return (int) $id;
    }

    private function cvEntityId(): int
    {
        $id = CompanyEntity::query()->where('code', '72')->value('id');

        $this->assertNotNull($id);

        return (int) $id;
    }

    private function renderStandardPrint(SalesInvoice $invoice): string
    {
        $invoiceFooter = SalesInvoicePostingMath::invoiceFooterTotals($invoice);

        return view('sales_invoices.print', [
            'invoice' => $invoice,
            'invoiceFooter' => $invoiceFooter,
            'showItemCode' => true,
        ])->render();
    }

    private function renderDotmatrixPrint(SalesInvoice $invoice): string
    {
        $invoiceFooter = SalesInvoicePostingMath::invoiceFooterTotals($invoice);

        return view('sales_invoices.print_dotmatrix', [
            'invoice' => $invoice,
            'invoiceFooter' => $invoiceFooter,
            'showItemCode' => true,
        ])->render();
    }

    private function assertCsjLetterheadMarkers(string $html): void
    {
        $this->assertStringContainsString('76114', $html);
        $this->assertStringContainsString('12560', $html);
        $this->assertStringContainsString('Branch Office', $html);
        $this->assertStringContainsString('Head Office', $html);
        $this->assertStringContainsString('info@cahayasarangejaya.co.id', $html);
        $legacyAddress = ErpParameter::get('company_address', '');
        if ($legacyAddress !== '') {
            $this->assertStringNotContainsString($legacyAddress, $html);
        }
        $this->assertSame(1, substr_count($html, 'class="company-header"'));
    }

    private function assertLegacyLetterheadMarkers(string $html): void
    {
        $companyAddress = ErpParameter::get('company_address', '');
        $companyName = ErpParameter::get('company_name', 'Company Name');

        $this->assertStringNotContainsString('76114', $html);
        $this->assertStringNotContainsString('Branch Office', $html);
        $this->assertStringNotContainsString('info@cahayasarangejaya.co.id', $html);
        $this->assertStringContainsString($companyName, $html);
        if ($companyAddress !== '') {
            $this->assertStringContainsString($companyAddress, $html);
        }
        $this->assertSame(1, substr_count($html, 'class="company-header"'));
    }

    public function test_standard_print_uses_csj_letterhead_for_csj_entity(): void
    {
        $invoice = $this->createMinimalInvoice($this->csjEntityId());

        $this->assertCsjLetterheadMarkers($this->renderStandardPrint($invoice));
    }

    public function test_standard_print_keeps_legacy_letterhead_for_cv_entity(): void
    {
        $invoice = $this->createMinimalInvoice($this->cvEntityId());

        $this->assertLegacyLetterheadMarkers($this->renderStandardPrint($invoice));
    }

    public function test_dotmatrix_print_uses_csj_letterhead_for_csj_entity(): void
    {
        $invoice = $this->createMinimalInvoice($this->csjEntityId());

        $this->assertCsjLetterheadMarkers($this->renderDotmatrixPrint($invoice));
    }

    public function test_dotmatrix_print_keeps_legacy_letterhead_for_cv_entity(): void
    {
        $invoice = $this->createMinimalInvoice($this->cvEntityId());

        $this->assertLegacyLetterheadMarkers($this->renderDotmatrixPrint($invoice));
    }
}
