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
            $table->string('item_code', 50)->nullable()->after('invoice_id');
            $table->string('item_name')->nullable()->after('item_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['item_code', 'item_name']);
        });
    }
};
