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
        Schema::table('inventory_items', function (Blueprint $table) {
            // Add default warehouse
            $table->unsignedBigInteger('default_warehouse_id')->nullable()->after('category_id');

            // Add price levels
            $table->decimal('selling_price_level_2', 15, 2)->nullable()->after('selling_price');
            $table->decimal('selling_price_level_3', 15, 2)->nullable()->after('selling_price_level_2');

            // Add percentage calculations for price levels
            $table->decimal('price_level_2_percentage', 5, 2)->nullable()->after('selling_price_level_3');
            $table->decimal('price_level_3_percentage', 5, 2)->nullable()->after('price_level_2_percentage');

            // Foreign key constraint for default warehouse
            $table->foreign('default_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');

            // Index
            $table->index('default_warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['default_warehouse_id']);
            $table->dropIndex(['default_warehouse_id']);
            $table->dropColumn([
                'default_warehouse_id',
                'selling_price_level_2',
                'selling_price_level_3',
                'price_level_2_percentage',
                'price_level_3_percentage'
            ]);
        });
    }
};
