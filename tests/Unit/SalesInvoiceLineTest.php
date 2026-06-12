<?php

namespace Tests\Unit;

use App\Models\Accounting\SalesInvoiceLine;
use PHPUnit\Framework\TestCase;

class SalesInvoiceLineTest extends TestCase
{
    public function test_amount_from_qty_times_unit_price_ignores_stored_inclusive_amount(): void
    {
        $line = new SalesInvoiceLine([
            'qty' => 2,
            'unit_price' => 30000,
            'amount' => 66600,
        ]);

        $this->assertSame(60000.0, $line->amountFromQtyTimesUnitPrice());
    }

    public function test_exclusive_amount_after_discount_subtracts_line_discount(): void
    {
        $line = new SalesInvoiceLine([
            'qty' => 10,
            'unit_price' => 96000,
            'amount' => 1065600,
            'discount_amount' => 60000,
        ]);

        $this->assertSame(900000.0, $line->exclusiveAmountAfterDiscount());
    }
}
