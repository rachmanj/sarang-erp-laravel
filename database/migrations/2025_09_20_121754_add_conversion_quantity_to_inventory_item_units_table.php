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
        Schema::table('inventory_item_units', function (Blueprint $table) {
            $table->decimal('conversion_quantity', 10, 2)->default(1)->after('is_base_unit')->comment('Quantity of base units this unit represents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_item_units', function (Blueprint $table) {
            $table->dropColumn('conversion_quantity');
        });
    }
};
