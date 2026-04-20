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
}
