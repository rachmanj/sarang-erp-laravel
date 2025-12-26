<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('invoice_id');
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('inventory_item_id');
            
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            
            $table->index(['inventory_item_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['inventory_item_id', 'warehouse_id']);
            $table->dropColumn(['inventory_item_id', 'warehouse_id']);
        });
    }
};
