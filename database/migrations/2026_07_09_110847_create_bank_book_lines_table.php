<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_book_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->date('doc_date')->nullable();
            $table->date('posting_date')->nullable();
            $table->string('doc_num', 191)->nullable();
            $table->string('ref_doc_num', 191)->nullable();
            $table->string('transaction_id', 191)->nullable();
            $table->text('description')->nullable();
            $table->string('project_code', 64)->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('match_status', 32)->default('unmatched');
            $table->string('exclude_reason', 500)->nullable();
            $table->string('line_notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['bank_reconciliation_id', 'journal_line_id'], 'bbl_recon_journal_unique');
            $table->index(['bank_reconciliation_id', 'match_status'], 'bbl_recon_match_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_book_lines');
    }
};
