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
        $this->assertEqualsWithDelta(60000.0, $footer['exclusive_subtotal'], 0.01);
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
    }
}
