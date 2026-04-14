<?php

namespace Tests\Unit;

use App\Models\Accounting\PurchaseInvoiceLine;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryTransaction;
use App\Models\SalesOrderLine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryTransactionDocumentUnitPriceTest extends TestCase
{
    #[Test]
    public function purchase_uses_net_amount_per_unit_when_net_amount_positive(): void
    {
        $tx = new InventoryTransaction([
            'transaction_type' => 'purchase',
            'item_id' => 1,
        ]);
        $line = new PurchaseInvoiceLine([
            'unit_price' => 1000,
            'net_amount' => 900,
            'qty' => 10,
        ]);
        $tx->setRelation('purchaseInvoiceLine', $line);

        $this->assertSame(90.0, $tx->documentUnitPrice());
    }

    #[Test]
    public function purchase_falls_back_to_unit_price_when_net_not_used(): void
    {
        $tx = new InventoryTransaction([
            'transaction_type' => 'purchase',
            'item_id' => 1,
        ]);
        $line = new PurchaseInvoiceLine([
            'unit_price' => 26576,
            'net_amount' => 0,
            'qty' => 82,
        ]);
        $tx->setRelation('purchaseInvoiceLine', $line);

        $this->assertSame(26576.0, $tx->documentUnitPrice());
    }

    #[Test]
    public function sale_uses_delivery_order_line_unit_price(): void
    {
        $tx = new InventoryTransaction([
            'transaction_type' => 'sale',
            'item_id' => 61,
            'reference_type' => 'delivery_order_line',
            'reference_id' => 875,
        ]);
        $doLine = new DeliveryOrderLine([
            'unit_price' => 35000.50,
        ]);
        $tx->setRelation('saleDeliveryOrderLine', $doLine);

        $this->assertSame(35000.5, $tx->documentUnitPrice());
    }

    #[Test]
    public function sale_uses_sales_order_line_unit_price(): void
    {
        $tx = new InventoryTransaction([
            'transaction_type' => 'sale',
            'item_id' => 5,
            'reference_type' => 'sales_order',
            'reference_id' => 100,
        ]);
        $soLine = new SalesOrderLine([
            'unit_price' => 12500.25,
        ]);
        $tx->setRelation('salesOrderLine', $soLine);

        $this->assertSame(12500.25, $tx->documentUnitPrice());
    }

    #[Test]
    public function non_purchase_sale_returns_null(): void
    {
        $tx = new InventoryTransaction([
            'transaction_type' => 'adjustment',
            'item_id' => 1,
        ]);

        $this->assertNull($tx->documentUnitPrice());
    }
}
