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
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('order_unit_id')->nullable()->after('unit_of_measure');
            $table->decimal('base_quantity', 15, 2)->default(0)->after('qty');
            $table->decimal('unit_conversion_factor', 15, 5)->default(1)->after('base_quantity');
            
            $table->foreign('order_unit_id')->references('id')->on('units_of_measure')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropForeign(['order_unit_id']);
            $table->dropColumn(['order_unit_id', 'base_quantity', 'unit_conversion_factor']);
        });
    }
};
