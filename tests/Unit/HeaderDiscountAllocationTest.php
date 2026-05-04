<?php

namespace Tests\Unit;

use App\Services\Accounting\HeaderDiscountAllocation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HeaderDiscountAllocationTest extends TestCase
{
    #[Test]
    public function payable_scale_matches_header_percent_on_inclusive_total(): void
    {
        $sumPayables = 111.0;
        $header = 38.85;
        $s = HeaderDiscountAllocation::payableScale($sumPayables, $header);
        $this->assertEqualsWithDelta(0.65, $s, 0.000001);
    }

    #[Test]
    public function purchase_order_scaling_matches_line_discount_equivalence_for_single_line(): void
    {
        $lines = collect([
            (object) ['net_amount' => 100.0, 'vat_rate' => 11.0, 'wtax_rate' => 0.0],
        ]);
        $preHeader = HeaderDiscountAllocation::purchaseOrderLineScaled($lines, 0);
        $this->assertEqualsWithDelta(111.0, $preHeader[0]['payable'], 0.02);

        $header = 111.0 * 0.35;
        $scaled = HeaderDiscountAllocation::purchaseOrderLineScaled($lines, $header);
        $this->assertEqualsWithDelta(65.0, $scaled[0]['dpp'], 0.02);
        $this->assertEqualsWithDelta(72.15, $scaled[0]['payable'], 0.02);
    }

    #[Test]
    public function two_lines_mixed_vat_match_equivalence_of_line_discounts(): void
    {
        $lines = collect([
            (object) ['net_amount' => 100.0, 'vat_rate' => 11.0, 'wtax_rate' => 0.0],
            (object) ['net_amount' => 200.0, 'vat_rate' => 0.0, 'wtax_rate' => 0.0],
        ]);
        $t = 111.0 + 200.0;
        $header = $t * 0.35;
        $scaled = HeaderDiscountAllocation::purchaseOrderLineScaled($lines, $header);

        $line35 = collect([
            (object) ['net_amount' => 65.0, 'vat_rate' => 11.0, 'wtax_rate' => 0.0],
            (object) ['net_amount' => 130.0, 'vat_rate' => 0.0, 'wtax_rate' => 0.0],
        ]);
        $reference = HeaderDiscountAllocation::purchaseOrderLineScaled($line35, 0);

        $this->assertEqualsWithDelta($reference[0]['payable'], $scaled[0]['payable'], 0.02);
        $this->assertEqualsWithDelta($reference[1]['payable'], $scaled[1]['payable'], 0.02);
        $this->assertEqualsWithDelta(
            $reference[0]['payable'] + $reference[1]['payable'],
            $scaled[0]['payable'] + $scaled[1]['payable'],
            0.05
        );
    }

    #[Test]
    public function split_header_shares_sum_to_header(): void
    {
        $payables = [111.0, 222.0];
        $shares = HeaderDiscountAllocation::splitHeaderAcrossPayables($payables, 100.0);
        $this->assertEqualsWithDelta(100.0, array_sum($shares), 0.02);
    }
}
