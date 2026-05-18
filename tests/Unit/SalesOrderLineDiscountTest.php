<?php

namespace Tests\Unit;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use PHPUnit\Framework\TestCase;

class SalesOrderLineDiscountTest extends TestCase
{
    public function test_compute_amount_from_pricing_applies_line_discount_before_vat(): void
    {
        $withoutDisc = SalesOrderLine::computeAmountFromPricing(10, 1000, 11, 0, 0);
        $this->assertEqualsWithDelta(11100.0, $withoutDisc, 0.01);

        $withDisc = SalesOrderLine::computeAmountFromPricing(10, 1000, 11, 0, 1000);
        $this->assertEqualsWithDelta(9990.0, $withDisc, 0.01);
    }

    public function test_resolve_line_dpp_discount_caps_at_gross_dpp(): void
    {
        [$amt, $pct, $gross] = SalesOrderLine::resolveLineDppDiscount(2, 100, null, 500);
        $this->assertEqualsWithDelta(200.0, $gross, 0.01);
        $this->assertEqualsWithDelta(200.0, $amt, 0.01);
        $this->assertEqualsWithDelta(100.0, $pct, 0.01);
    }

    public function test_resolve_header_discount_against_line_total_from_percentage(): void
    {
        [$dAmt, $dPct] = SalesOrder::resolveHeaderDiscountAgainstLineTotal(10000.0, 10.0, null);
        $this->assertEqualsWithDelta(1000.0, $dAmt, 0.01);
        $this->assertEqualsWithDelta(10.0, $dPct, 0.01);
    }

    public function test_resolve_header_discount_against_line_total_from_amount(): void
    {
        [$dAmt, $dPct] = SalesOrder::resolveHeaderDiscountAgainstLineTotal(10000.0, null, 2500.0);
        $this->assertEqualsWithDelta(2500.0, $dAmt, 0.01);
        $this->assertEqualsWithDelta(25.0, $dPct, 0.01);
    }
}
