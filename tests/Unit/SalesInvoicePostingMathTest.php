<?php

namespace Tests\Unit;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class SalesInvoicePostingMathTest extends TestCase
{
    public function test_split_matches_so_line_inclusive_example(): void
    {
        $parts = SalesInvoicePostingMath::splitTaxInclusiveLineAmount(66600.0, 11.0, 0.0);
        $this->assertEqualsWithDelta(60000.0, $parts['dpp'], 0.01);
        $this->assertEqualsWithDelta(6600.0, $parts['output_vat'], 0.01);
        $this->assertEqualsWithDelta(0.0, $parts['wtax'], 0.01);
        $this->assertEqualsWithDelta(66600.0, $parts['dpp'] + $parts['output_vat'] - $parts['wtax'], 0.005);
        $this->assertEqualsWithDelta(6600.0, SalesInvoicePostingMath::computeOutputVatFromTaxInclusiveLineAmount(66600.0, 11.0, 0.0), 0.01);
    }

    public function test_invoice_footer_totals_match_gross_for_single_line(): void
    {
        $line = new SalesInvoiceLine([
            'qty' => 2,
            'unit_price' => 30000,
            'amount' => 66600,
            'tax_code_id' => null,
            'wtax_rate' => 0,
        ]);
        $line->setRelation('taxCode', null);

        $invoice = new SalesInvoice;
        $invoice->setRelation('lines', new Collection([$line]));

        $footer = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $this->assertEqualsWithDelta(66600.0, $footer['exclusive_subtotal'], 0.01);
        $this->assertEqualsWithDelta(66600.0, $footer['gross_total'], 0.01);
        $this->assertEqualsWithDelta(0.0, $footer['total_vat'], 0.01);
        $this->assertEqualsWithDelta(66600.0, $footer['amount_due'], 0.01);
    }

    public function test_invoice_footer_totals_extracts_vat_when_tax_code_loaded(): void
    {
        $taxCode = (object) ['rate' => 11.0];
        $line = new SalesInvoiceLine([
            'qty' => 2,
            'unit_price' => 30000,
            'amount' => 66600,
            'tax_code_id' => 1,
            'wtax_rate' => 0,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $invoice = new SalesInvoice;
        $invoice->setRelation('lines', new Collection([$line]));

        $footer = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $this->assertEqualsWithDelta(60000.0, $footer['exclusive_subtotal'], 0.01);
        $this->assertEqualsWithDelta(66600.0, $footer['gross_total'], 0.01);
        $this->assertEqualsWithDelta(6600.0, $footer['total_vat'], 0.01);
        $this->assertEqualsWithDelta(66600.0, $footer['amount_due'], 0.01);
        $this->assertEqualsWithDelta(
            round($footer['amount_due'], 2),
            round($footer['exclusive_subtotal'] + $footer['total_vat'] - $footer['total_wtax'], 2),
            0.001,
        );
    }

    public function test_invoice_footer_subtotal_is_dpp_when_unit_price_equals_inclusive_amount(): void
    {
        $taxCode = (object) ['rate' => 11.0];
        $line = new SalesInvoiceLine([
            'qty' => 1,
            'unit_price' => 133427400,
            'amount' => 133427400,
            'tax_code_id' => 1,
            'wtax_rate' => 0,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $invoice = new SalesInvoice;
        $invoice->setRelation('lines', new Collection([$line]));

        $footer = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $this->assertEqualsWithDelta(120204864.86, $footer['exclusive_subtotal'], 0.02);
        $this->assertEqualsWithDelta(13222535.14, $footer['total_vat'], 0.02);
        $this->assertEqualsWithDelta(133427400.0, $footer['gross_total'], 0.01);
        $this->assertEqualsWithDelta(
            round($footer['amount_due'], 2),
            round($footer['exclusive_subtotal'] + $footer['total_vat'] - $footer['total_wtax'], 2),
            0.001,
        );
    }
}
