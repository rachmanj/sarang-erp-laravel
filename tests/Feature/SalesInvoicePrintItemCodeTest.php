<?php

namespace Tests\Feature;

use App\Models\Accounting\SalesInvoice;
use App\Models\BusinessPartner;
use App\Models\User;
use App\Services\Accounting\SalesInvoicePostingMath;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesInvoicePrintItemCodeTest extends TestCase
{
    use RefreshDatabase;

    private const ITEM_CODE = 'SI-PRINT-ITEM-001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $user = User::factory()->create();
        $user->givePermissionTo('ar.invoices.view');
        $this->actingAs($user);
    }

    /**
     * @return array{invoice: SalesInvoice, itemCode: string}
     */
    private function createInvoiceWithLine(): array
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-SI-PRINT-IC-'.uniqid(),
            'name' => 'Print Item Code Test Customer',
            'partner_type' => 'customer',
        ]);

        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $accountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $invoice = SalesInvoice::query()->create([
            'invoice_no' => 'T-SI-PRINT-IC-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $partner->id,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoice->id,
            'account_id' => $accountId,
            'item_code' => self::ITEM_CODE,
            'item_name' => 'Print Item Code Test Line',
            'description' => 'Test line',
            'qty' => 2,
            'unit_price' => 50000,
            'amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice->load([
            'lines.account',
            'lines.taxCode',
            'lines.inventoryItem',
            'lines.partNumber',
            'lines.deliveryOrderLine.partNumber',
            'businessPartner.primaryAddress',
            'businessPartner.addresses',
            'businessPartnerProject',
            'companyEntity',
            'deliveryOrders',
        ]);

        return [
            'invoice' => $invoice,
            'itemCode' => self::ITEM_CODE,
        ];
    }

    /**
     * @return array{headerCols: int, bodyCols: int, footerCols: int}
     */
    private function extractLineTableColumnCounts(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $targetTable = null;

        foreach ($xpath->query('//table') as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }

            if (str_contains($table->textContent, 'Part No.') && str_contains($table->textContent, 'Amount due')) {
                $targetTable = $table;
                break;
            }
        }

        $this->assertNotNull($targetTable, 'Line items table not found in rendered HTML.');

        $headerCols = $xpath->query('.//thead/tr[1]/th', $targetTable)->length;
        $bodyCols = $xpath->query('.//tbody/tr[1]/td', $targetTable)->length;

        $footerRows = $xpath->query('.//tfoot/tr', $targetTable);
        $lastFooter = $footerRows->item($footerRows->length - 1);
        $this->assertInstanceOf(DOMElement::class, $lastFooter);

        $footerTds = $xpath->query('./td', $lastFooter);
        $firstTd = $footerTds->item(0);
        $this->assertInstanceOf(DOMElement::class, $firstTd);

        $colspan = $firstTd->getAttribute('colspan') !== ''
            ? (int) $firstTd->getAttribute('colspan')
            : 1;
        $footerCols = $colspan + max(0, $footerTds->length - 1);

        return [
            'headerCols' => $headerCols,
            'bodyCols' => $bodyCols,
            'footerCols' => $footerCols,
        ];
    }

    private function assertItemCodeHeaderPresent(string $html, bool $expected): void
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $headers = $xpath->query('//table//thead//th');

        $hasItemCodeHeader = false;
        foreach ($headers as $header) {
            if (trim($header->textContent) === 'Item Code') {
                $hasItemCodeHeader = true;
                break;
            }
        }

        if ($expected) {
            $this->assertTrue($hasItemCodeHeader, 'Expected Item Code header to be present.');
        } else {
            $this->assertFalse($hasItemCodeHeader, 'Expected Item Code header to be hidden.');
        }
    }

    private function assertItemCodeInBody(string $html, string $itemCode, bool $expected): void
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $bodyRows = $xpath->query('//table//tbody/tr');

        $foundInBody = false;
        foreach ($bodyRows as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            foreach ($xpath->query('./td', $row) as $cell) {
                if (trim($cell->textContent) === $itemCode) {
                    $foundInBody = true;
                    break 2;
                }
            }
        }

        if ($expected) {
            $this->assertTrue($foundInBody, 'Expected item code to appear in line row.');
        } else {
            $this->assertFalse($foundInBody, 'Expected item code to be hidden from line row.');
        }
    }

    private function assertFooterTotalsPresent(string $html, array $invoiceFooter): void
    {
        $this->assertStringContainsString(
            number_format($invoiceFooter['exclusive_subtotal'], 2),
            $html
        );
        $this->assertStringContainsString(
            number_format($invoiceFooter['amount_due'], 2),
            $html
        );
    }

    /**
     * @param  array<string, string>  $layouts
     */
    private function assertPrintRouteItemCodeVisibility(array $layouts, bool $showItemCode): void
    {
        ['invoice' => $invoice, 'itemCode' => $itemCode] = $this->createInvoiceWithLine();
        $invoiceFooter = SalesInvoicePostingMath::invoiceFooterTotals($invoice);

        foreach ($layouts as $layout => $viewName) {
            $query = ['layout' => $layout];
            if (! $showItemCode) {
                $query['show_item_code'] = 0;
            }

            $response = $this->get(route('sales-invoices.print', ['id' => $invoice->id] + $query));
            $response->assertOk();

            $html = $response->getContent();
            $this->assertItemCodeHeaderPresent($html, $showItemCode);
            $this->assertItemCodeInBody($html, $itemCode, $showItemCode);
            $this->assertFooterTotalsPresent($html, $invoiceFooter);

            $counts = $this->extractLineTableColumnCounts($html);
            $this->assertSame(
                $counts['headerCols'],
                $counts['bodyCols'],
                "Header/body column mismatch for layout {$layout}."
            );
            $this->assertSame(
                $counts['headerCols'],
                $counts['footerCols'],
                "Header/footer column mismatch for layout {$layout}."
            );
        }
    }

    public function test_a4_print_views_show_item_code_by_default(): void
    {
        ['invoice' => $invoice, 'itemCode' => $itemCode] = $this->createInvoiceWithLine();
        $invoiceFooter = SalesInvoicePostingMath::invoiceFooterTotals($invoice);

        foreach (['sales_invoices.print', 'sales_invoices.print_pt_csj', 'sales_invoices.print_cv_saranghae'] as $viewName) {
            $html = view($viewName, [
                'invoice' => $invoice,
                'invoiceFooter' => $invoiceFooter,
                'entity' => null,
            ])->render();

            $this->assertItemCodeHeaderPresent($html, true);
            $this->assertItemCodeInBody($html, $itemCode, true);
            $this->assertFooterTotalsPresent($html, $invoiceFooter);

            $counts = $this->extractLineTableColumnCounts($html);
            $this->assertSame(8, $counts['headerCols'], "{$viewName} should render 8 columns with Item Code.");
            $this->assertSame($counts['headerCols'], $counts['bodyCols']);
            $this->assertSame($counts['headerCols'], $counts['footerCols']);
        }
    }

    public function test_a4_print_views_hide_item_code_when_flag_is_off(): void
    {
        ['invoice' => $invoice, 'itemCode' => $itemCode] = $this->createInvoiceWithLine();
        $invoiceFooter = SalesInvoicePostingMath::invoiceFooterTotals($invoice);

        foreach (['sales_invoices.print', 'sales_invoices.print_pt_csj', 'sales_invoices.print_cv_saranghae'] as $viewName) {
            $html = view($viewName, [
                'invoice' => $invoice,
                'invoiceFooter' => $invoiceFooter,
                'entity' => null,
                'showItemCode' => false,
            ])->render();

            $this->assertItemCodeHeaderPresent($html, false);
            $this->assertItemCodeInBody($html, $itemCode, false);
            $this->assertFooterTotalsPresent($html, $invoiceFooter);

            $counts = $this->extractLineTableColumnCounts($html);
            $this->assertSame(7, $counts['headerCols'], "{$viewName} should render 7 columns without Item Code.");
            $this->assertSame($counts['headerCols'], $counts['bodyCols']);
            $this->assertSame($counts['headerCols'], $counts['footerCols']);
        }
    }

    public function test_print_route_passes_show_item_code_query_param(): void
    {
        $layouts = [
            'standard' => 'sales_invoices.print',
            'pt_csj' => 'sales_invoices.print_pt_csj',
            'cv_saranghae' => 'sales_invoices.print_cv_saranghae',
        ];

        $this->assertPrintRouteItemCodeVisibility($layouts, true);
        $this->assertPrintRouteItemCodeVisibility($layouts, false);
    }
}
