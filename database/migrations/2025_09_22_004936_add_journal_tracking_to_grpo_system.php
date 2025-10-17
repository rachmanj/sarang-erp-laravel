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
        // Add journal tracking fields to goods_receipt_po table
        Schema::table('goods_receipt_po', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamp('journal_posted_at')->nullable();
            $table->foreignId('journal_posted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        // Create GRPO journal entries tracking table
        Schema::create('grpo_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grpo_id')->constrained('goods_receipt_po')->cascadeOnDelete();
            $table->foreignId('grpo_line_id')->constrained('goods_receipt_po_lines')->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->constrained('journal_lines')->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('account_type', 20); // 'inventory' or 'liability'
            $table->timestamps();

            $table->index(['grpo_id', 'journal_id']);
            $table->index(['grpo_line_id', 'journal_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grpo_journal_entries');

        Schema::table('goods_receipt_po', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
            $table->dropForeign(['journal_posted_by']);
            $table->dropColumn(['journal_id', 'journal_posted_at', 'journal_posted_by']);
        });
    }
};
