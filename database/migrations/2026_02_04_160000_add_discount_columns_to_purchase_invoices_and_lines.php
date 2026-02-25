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
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('net_amount', 15, 2)->default(0)->after('discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_percentage']);
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_percentage', 'net_amount']);
        });
    }
};
