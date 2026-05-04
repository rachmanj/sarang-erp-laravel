<?php

namespace Tests\Unit;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchaseInvoiceLine;
use App\Services\Accounting\PurchaseInvoiceFooterMath;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseInvoiceFooterMathTest extends TestCase
{
    #[Test]
    public function test_footer_additive_ppn_on_net_amount_single_line(): void
    {
        $taxCode = (object) ['rate' => 11.0, 'type' => 'ppn_input', 'name' => 'PPN Masukan'];
        $line = new PurchaseInvoiceLine([
            'net_amount' => 3369000,
            'amount_after_vat' => 3739590,
            'amount' => 3369000,
            'vat_amount' => 370590,
            'discount_amount' => 0,
            'tax_code_id' => 1,
        ]);
        $line->setRelation('taxCode', $taxCode);

        $invoice = new PurchaseInvoice([
            'total_amount' => 3739590,
            'discount_amount' => 0,
        ]);
        $invoice->setRelation('lines', new Collection([$line]));

        $footer = PurchaseInvoiceFooterMath::invoiceFooterTotals($invoice);
        $this->assertEqualsWithDelta(3369000.0, $footer['exclusive_subtotal'], 0.01);
        $this->assertEqualsWithDelta(370590.0, $footer['total_vat'], 0.01);
        $this->assertEqualsWithDelta(0.0, $footer['total_wtax'], 0.01);
        $this->assertEqualsWithDelta(3739590.0, $footer['amount_due'], 0.01);
    }
}
