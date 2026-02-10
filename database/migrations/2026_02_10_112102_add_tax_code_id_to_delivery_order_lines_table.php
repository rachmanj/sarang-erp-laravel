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
        Schema::table('delivery_order_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_code_id')->nullable()->after('amount');
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_order_lines', function (Blueprint $table) {
            $table->dropForeign(['tax_code_id']);
            $table->dropColumn('tax_code_id');
        });
    }
};
