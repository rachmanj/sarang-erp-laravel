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
            $table->foreignId('purchase_currency_id')->nullable()->after('purchase_price')
                ->constrained('currencies')->nullOnDelete();
            $table->foreignId('selling_currency_id')->nullable()->after('purchase_currency_id')
                ->constrained('currencies')->nullOnDelete();
            $table->decimal('last_purchase_exchange_rate', 12, 6)->nullable()->after('selling_currency_id');

            $table->index('purchase_currency_id');
            $table->index('selling_currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_currency_id']);
            $table->dropForeign(['selling_currency_id']);
            $table->dropIndex(['purchase_currency_id']);
            $table->dropIndex(['selling_currency_id']);
            $table->dropColumn(['purchase_currency_id', 'selling_currency_id', 'last_purchase_exchange_rate']);
        });
    }
};
