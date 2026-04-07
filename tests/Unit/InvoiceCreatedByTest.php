<?php

namespace Tests\Unit;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\SalesInvoice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class InvoiceCreatedByTest extends TestCase
{
    public function test_purchase_invoice_created_by_returns_belongs_to_relation(): void
    {
        $invoice = new PurchaseInvoice;
        $relation = $invoice->createdBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('created_by', $relation->getForeignKeyName());
    }

    public function test_sales_invoice_created_by_returns_belongs_to_relation(): void
    {
        $invoice = new SalesInvoice;
        $relation = $invoice->createdBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('created_by', $relation->getForeignKeyName());
    }
}
