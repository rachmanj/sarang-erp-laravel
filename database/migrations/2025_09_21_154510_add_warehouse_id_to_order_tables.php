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
        // Add warehouse_id to purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('business_partner_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });

        // Add warehouse_id to goods_receipt_po table
        Schema::table('goods_receipt_po', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('business_partner_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });

        // Add warehouse_id to sales_orders table
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('business_partner_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });

        // Add warehouse_id to delivery_orders table
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('business_partner_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove warehouse_id from purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        // Remove warehouse_id from goods_receipt_po table
        Schema::table('goods_receipt_po', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        // Remove warehouse_id from sales_orders table
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        // Remove warehouse_id from delivery_orders table
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
