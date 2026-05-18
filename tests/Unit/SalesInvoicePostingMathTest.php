<?php

namespace Tests\Unit;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class SalesInvoicePostingMathTest extends TestCase
{
    public function test_split_line_matches_amount_from_pricing_with_eleven_percent_vat(): void
    {
        $taxCode = (object) ['rate' => 11.0];
        $line = new SalesInvoiceLine([
            'qty' => 2,
            'unit_price' => 30000,
            'amount' => 66600,
            'tax_code_id' => 1,
            'wtax_rate' => 0,
            'account_id' => 10,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $parts = SalesInvoicePostingMath::splitLineFromTaxExclusivePricing($line);
        $this->assertEqualsWithDelta(60000.0, $parts['dpp'], 0.01);
        $this->assertEqualsWithDelta(6600.0, $parts['output_vat'], 0.01);
        $this->assertEqualsWithDelta(0.0, $parts['wtax'], 0.01);
        $this->assertEqualsWithDelta(66600.0, $parts['gross'], 0.01);
    }

    public function test_invoice_footer_totals_use_qty_times_unit_price_as_dpp_without_tax(): void
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
        $this->assertEqualsWithDelta(60000.0, $footer['exclusive_subtotal'], 0.01);
        $this->assertEqualsWithDelta(60000.0, $footer['gross_total'], 0.01);
        $this->assertEqualsWithDelta(0.0, $footer['total_vat'], 0.01);
        $this->assertEqualsWithDelta(60000.0, $footer['amount_due'], 0.01);
    }

    public function test_invoice_footer_totals_adds_vat_on_top_of_unit_price_base_when_tax_code_loaded(): void
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

    public function test_invoice_footer_when_unit_price_is_exclusive_base_full_line_not_inclusive_of_vat(): void
    {
        $taxCode = (object) ['rate' => 11.0];
        $line = new SalesInvoiceLine([
            'qty' => 1,
            'unit_price' => 3369000,
            'amount' => 3369000,
            'tax_code_id' => 1,
            'wtax_rate' => 0,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $invoice = new SalesInvoice;
        $invoice->setRelation('lines', new Collection([$line]));

        $footer = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $this->assertEqualsWithDelta(3369000.0, $footer['exclusive_subtotal'], 0.01);
        $this->assertEqualsWithDelta(370590.0, $footer['total_vat'], 0.01);
        $this->assertEqualsWithDelta(3739590.0, $footer['gross_total'], 0.01);
        $this->assertEqualsWithDelta(3739590.0, $footer['amount_due'], 0.01);
    }

    public function test_invoice_footer_large_exclusive_base_adds_eleven_percent(): void
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
        $this->assertEqualsWithDelta(133427400.0, $footer['exclusive_subtotal'], 0.02);
        $this->assertEqualsWithDelta(14677014.0, $footer['total_vat'], 0.02);
        $this->assertEqualsWithDelta(148104414.0, $footer['gross_total'], 0.02);
        $this->assertEqualsWithDelta(
            round($footer['amount_due'], 2),
            round($footer['exclusive_subtotal'] + $footer['total_vat'] - $footer['total_wtax'], 2),
            0.001,
        );
    }

    public function test_computed_gross_matches_amount_from_pricing_for_manual_tax_line(): void
    {
        $taxCode = (object) ['rate' => 11.0];
        $line = new SalesInvoiceLine([
            'qty' => 2,
            'unit_price' => 30000,
            'tax_code_id' => 1,
            'wtax_rate' => 0,
            'delivery_order_line_id' => null,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $gross = SalesInvoicePostingMath::computedGrossAmountForLine($line);
        $this->assertEqualsWithDelta(66600.0, $gross, 0.01);
    }

    public function test_split_line_reduces_dpp_when_line_discount_present(): void
    {
        $taxCode = (object) ['rate' => 11.0];
        $line = new SalesInvoiceLine([
            'qty' => 10,
            'unit_price' => 1000,
            'discount_amount' => 1000,
            'tax_code_id' => 1,
            'wtax_rate' => 0,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $parts = SalesInvoicePostingMath::splitLineFromTaxExclusivePricing($line);
        $this->assertEqualsWithDelta(9000.0, $parts['dpp'], 0.01);
        $this->assertEqualsWithDelta(1000.0, $parts['line_discount'], 0.01);
        $this->assertEqualsWithDelta(990.0, $parts['output_vat'], 0.01);
        $this->assertEqualsWithDelta(9990.0, $parts['gross'], 0.01);
    }

    public function test_invoice_footer_applies_header_discount_to_amount_due(): void
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

        $invoice = new SalesInvoice([
            'discount_amount' => 6600,
        ]);
        $invoice->setRelation('lines', new Collection([$line]));

        $footer = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $this->assertEqualsWithDelta(66600.0, $footer['gross_total'], 0.01);
        $this->assertEqualsWithDelta(6600.0, $footer['header_discount_total'], 0.01);
        $this->assertEqualsWithDelta(60000.0, $footer['amount_due'], 0.01);
        $this->assertEqualsWithDelta(0.0, $footer['line_discount_total'], 0.01);
    }
}
