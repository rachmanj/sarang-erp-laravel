<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that require the company_entity_id column.
     *
     * @var array<string, string> table => column_after
     */
    private array $tables = [
        'purchase_orders' => 'business_partner_id',
        'goods_receipt_po' => 'business_partner_id',
        'purchase_invoices' => 'business_partner_id',
        'purchase_payments' => 'business_partner_id',
        'sales_orders' => 'business_partner_id',
        'sales_invoices' => 'business_partner_id',
        'sales_receipts' => 'business_partner_id',
        'delivery_orders' => 'business_partner_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName => $afterColumn) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_entity_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($afterColumn) {
                $table->foreignId('company_entity_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->constrained('company_entities')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName => $afterColumn) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'company_entity_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['company_entity_id']);
                $table->dropColumn('company_entity_id');
            });
        }
    }
};
