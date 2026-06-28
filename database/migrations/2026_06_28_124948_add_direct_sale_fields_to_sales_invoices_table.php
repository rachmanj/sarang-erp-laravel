<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->boolean('is_direct_sale')->default(false)->after('is_opening_balance');
            $table->string('payment_method', 20)->nullable()->after('is_direct_sale');
            $table->foreignId('cash_account_id')->nullable()->after('payment_method')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_account_id');
            $table->dropColumn(['is_direct_sale', 'payment_method']);
        });
    }
};
