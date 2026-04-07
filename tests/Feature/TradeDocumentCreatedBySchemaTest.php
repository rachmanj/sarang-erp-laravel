<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TradeDocumentCreatedBySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_trade_document_tables_have_created_by_column(): void
    {
        $tables = [
            'purchase_orders',
            'sales_orders',
            'goods_receipt_po',
            'purchase_invoices',
            'sales_invoices',
            'delivery_orders',
            'purchase_payments',
            'sales_receipts',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'created_by'),
                "Table [{$table}] must have created_by for document creator tracking."
            );
        }
    }

    public function test_gr_gi_headers_has_created_by_column(): void
    {
        $this->assertTrue(Schema::hasColumn('gr_gi_headers', 'created_by'));
    }
}
