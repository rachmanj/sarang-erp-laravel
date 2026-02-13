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
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_order_line_id')->nullable()->after('invoice_id');
            $table->foreign('delivery_order_line_id')->references('id')->on('delivery_order_lines')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_line_id']);
            $table->dropColumn('delivery_order_line_id');
        });
    }
};
