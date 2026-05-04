<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->decimal('header_discount_allocated', 18, 2)
                ->after('discount_percentage')->default(0);
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->decimal('header_discount_allocated', 18, 2)
                ->after('discount_percentage')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropColumn('header_discount_allocated');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn('header_discount_allocated');
        });
    }
};
