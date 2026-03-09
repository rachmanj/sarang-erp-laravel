<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('inventory_item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('inventory_item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });

        Schema::table('delivery_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('inventory_item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });

        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('inventory_item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });

        Schema::table('sales_quotation_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('inventory_item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('inventory_item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });

        Schema::table('gr_gi_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_number_id')->nullable()->after('item_id');
            $table->foreign('part_number_id')->references('id')->on('inventory_item_part_numbers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });

        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });

        Schema::table('delivery_order_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });

        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });

        Schema::table('sales_quotation_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });

        Schema::table('gr_gi_lines', function (Blueprint $table) {
            $table->dropForeign(['part_number_id']);
            $table->dropColumn('part_number_id');
        });
    }
};
