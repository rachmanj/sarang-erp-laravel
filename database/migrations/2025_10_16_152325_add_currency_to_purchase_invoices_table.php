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
            $table->foreignId('currency_id')->default(1)->after('business_partner_id') // Default to IDR
                ->constrained('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000)->after('currency_id');
            $table->decimal('total_amount_foreign', 15, 2)->default(0)->after('total_amount');

            $table->index(['currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropIndex(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'total_amount_foreign']);
        });
    }
};
