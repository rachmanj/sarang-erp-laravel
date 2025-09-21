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
        // Add unit conversion fields to goods receipt PO lines
        Schema::table('goods_receipt_po_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('order_unit_id')->nullable()->after('description');
            $table->decimal('base_quantity', 15, 2)->default(0)->after('qty');
            $table->decimal('unit_conversion_factor', 10, 2)->default(1)->after('base_quantity');

            $table->foreign('order_unit_id')->references('id')->on('units_of_measure')->onDelete('set null');
            $table->index('order_unit_id');
        });

        // Add unit conversion fields to delivery order lines
        Schema::table('delivery_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('order_unit_id')->nullable()->after('item_name');
            $table->decimal('base_ordered_qty', 15, 2)->default(0)->after('ordered_qty');
            $table->decimal('base_delivered_qty', 15, 2)->default(0)->after('delivered_qty');
            $table->decimal('unit_conversion_factor', 10, 2)->default(1)->after('unit_price');

            $table->foreign('order_unit_id')->references('id')->on('units_of_measure')->onDelete('set null');
            $table->index('order_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove unit conversion fields from delivery order lines
        Schema::table('delivery_order_lines', function (Blueprint $table) {
            $table->dropForeign(['order_unit_id']);
            $table->dropIndex(['order_unit_id']);
            $table->dropColumn(['order_unit_id', 'base_ordered_qty', 'base_delivered_qty', 'unit_conversion_factor']);
        });

        // Remove unit conversion fields from goods receipt PO lines
        Schema::table('goods_receipt_po_lines', function (Blueprint $table) {
            $table->dropForeign(['order_unit_id']);
            $table->dropIndex(['order_unit_id']);
            $table->dropColumn(['order_unit_id', 'base_quantity', 'unit_conversion_factor']);
        });
    }
};
