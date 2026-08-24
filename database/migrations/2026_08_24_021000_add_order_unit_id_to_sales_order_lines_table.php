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
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('order_unit_id')->nullable()->after('item_name');

            $table->foreign('order_unit_id')->references('id')->on('units_of_measure')->onDelete('set null');
            $table->index('order_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropForeign(['order_unit_id']);
            $table->dropIndex(['order_unit_id']);
            $table->dropColumn('order_unit_id');
        });
    }
};
