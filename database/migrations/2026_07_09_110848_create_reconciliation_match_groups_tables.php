<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_match_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->string('match_type', 32);
            $table->float('confidence_score')->nullable();
            $table->decimal('bank_total', 18, 2)->default(0);
            $table->decimal('book_total', 18, 2)->default(0);
            $table->decimal('difference', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bank_reconciliation_id', 'match_type'], 'rmg_recon_type_idx');
        });

        Schema::create('match_group_bank_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_match_group_id')->constrained('reconciliation_match_groups')->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->constrained('bank_statement_lines')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('bank_statement_line_id', 'mgbl_statement_line_unique');
        });

        Schema::create('match_group_book_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_match_group_id')->constrained('reconciliation_match_groups')->cascadeOnDelete();
            $table->foreignId('bank_book_line_id')->constrained('bank_book_lines')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('bank_book_line_id', 'mgbook_book_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_group_book_lines');
        Schema::dropIfExists('match_group_bank_lines');
        Schema::dropIfExists('reconciliation_match_groups');
    }
};
