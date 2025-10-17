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
            $table->foreignId('purchase_price_currency_id')->nullable()->after('purchase_price') // For last purchase price tracking
                ->constrained('currencies')->onDelete('set null');
            $table->decimal('last_purchase_exchange_rate', 12, 6)->nullable()->after('purchase_price_currency_id');

            $table->index(['purchase_price_currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_price_currency_id']);
            $table->dropIndex(['purchase_price_currency_id']);
            $table->dropColumn(['purchase_price_currency_id', 'last_purchase_exchange_rate']);
        });
    }
};
