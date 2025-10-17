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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('freight_cost_foreign', 15, 2)->default(0)->after('freight_cost');
            $table->decimal('handling_cost_foreign', 15, 2)->default(0)->after('handling_cost');
            $table->decimal('insurance_cost_foreign', 15, 2)->default(0)->after('insurance_cost');
            $table->decimal('total_cost_foreign', 15, 2)->default(0)->after('total_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['freight_cost_foreign', 'handling_cost_foreign', 'insurance_cost_foreign', 'total_cost_foreign']);
        });
    }
};
