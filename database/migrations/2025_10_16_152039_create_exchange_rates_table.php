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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('to_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 12, 6); // High precision for exchange rates
            $table->date('effective_date');
            $table->enum('rate_type', ['daily', 'manual', 'custom'])->default('daily');
            $table->enum('source', ['manual', 'api', 'central_bank'])->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Unique constraint to prevent duplicate rates for same currency pair and date
            $table->unique(['from_currency_id', 'to_currency_id', 'effective_date'], 'unique_currency_date_rate');

            // Index for efficient rate lookups
            $table->index(['from_currency_id', 'to_currency_id', 'effective_date'], 'idx_exchange_rates_lookup');
            $table->index(['effective_date', 'rate_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
