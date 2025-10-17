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
        Schema::table('journals', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('description') // Nullable for mixed currency journals
                ->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('currency_id'); // Nullable for mixed currency journals

            $table->index(['currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropIndex(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate']);
        });
    }
};
