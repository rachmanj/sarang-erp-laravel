<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained('bank_statements')->cascadeOnDelete();
            $table->date('posting_date');
            $table->date('value_date')->nullable();
            $table->text('description')->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('direction', 10);
            $table->decimal('running_balance', 18, 2)->nullable();
            $table->string('match_status', 20)->default('unmatched');
            $table->string('line_hash', 64);
            $table->json('ai_meta')->nullable();
            $table->timestamps();

            $table->unique(['bank_statement_id', 'line_hash']);
            $table->index(['bank_statement_id', 'match_status']);
            $table->index('posting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
