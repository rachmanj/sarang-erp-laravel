<?php

namespace Tests\Unit;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\Accounting\PurchaseOrderFooterMath;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchaseOrderFooterMathTest extends TestCase
{
    #[Test]
    public function footer_totals_match_single_line_with_vat(): void
    {
        $order = new PurchaseOrder([
            'discount_amount' => 0,
            'total_amount' => 36337066.56,
        ]);
        $order->setRelation('lines', collect([
            new PurchaseOrderLine([
                'id' => 1,
                'qty' => 16,
                'unit_price' => 2046006,
                'net_amount' => 32736096,
                'vat_rate' => 11,
                'wtax_rate' => 0,
                'amount' => 36337066.56,
            ]),
        ]));

        $footer = PurchaseOrderFooterMath::orderFooterTotals($order);

        $this->assertEqualsWithDelta(32736096.0, $footer['exclusive_subtotal'], 0.02);
        $this->assertEqualsWithDelta(3600970.56, $footer['total_vat'], 0.02);
        $this->assertEqualsWithDelta(0.0, $footer['total_wtax'], 0.01);
        $this->assertEqualsWithDelta(36337066.56, $footer['amount_due'], 0.02);
    }

    #[Test]
    public function footer_totals_apply_header_discount_scale(): void
    {
        $order = new PurchaseOrder([
            'discount_amount' => 38.85,
            'total_amount' => 72.15,
        ]);
        $order->setRelation('lines', collect([
            new PurchaseOrderLine([
                'id' => 1,
                'qty' => 1,
                'unit_price' => 100,
                'net_amount' => 100,
                'vat_rate' => 11,
                'wtax_rate' => 0,
                'amount' => 72.15,
            ]),
        ]));

        $footer = PurchaseOrderFooterMath::orderFooterTotals($order);

        $this->assertEqualsWithDelta(65.0, $footer['exclusive_subtotal'], 0.02);
        $this->assertEqualsWithDelta(7.15, $footer['total_vat'], 0.02);
        $this->assertEqualsWithDelta(72.15, $footer['amount_due'], 0.02);
    }
}
