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
        Schema::create('currency_revaluations', function (Blueprint $table) {
            $table->id();
            $table->string('revaluation_no', 50)->unique(); // REV-YYYYMM-######
            $table->date('revaluation_date');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('reference_rate_id')->constrained('exchange_rates')->onDelete('cascade');
            $table->decimal('total_unrealized_gain', 15, 2)->default(0);
            $table->decimal('total_unrealized_loss', 15, 2)->default(0);
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('revalued_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['currency_id', 'revaluation_date']);
            $table->index(['status', 'revaluation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_revaluations');
    }
};
