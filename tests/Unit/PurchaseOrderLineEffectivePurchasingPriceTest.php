<?php

namespace Tests\Unit;

use App\Models\PurchaseOrderLine;
use PHPUnit\Framework\TestCase;

class PurchaseOrderLineEffectivePurchasingPriceTest extends TestCase
{
    public function test_uses_net_amount_per_qty_when_net_positive(): void
    {
        $line = new PurchaseOrderLine([
            'qty' => 10,
            'unit_price' => 6000,
            'net_amount' => 54000,
        ]);

        $this->assertSame(5400.0, $line->effectivePurchasingUnitPrice());
    }

    public function test_falls_back_to_unit_price_when_net_amount_zero(): void
    {
        $line = new PurchaseOrderLine([
            'qty' => 10,
            'unit_price' => 6000,
            'net_amount' => 0,
        ]);

        $this->assertSame(6000.0, $line->effectivePurchasingUnitPrice());
    }
}
