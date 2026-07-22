<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_book_lines', function (Blueprint $table) {
            $table->boolean('is_carried_forward')->default(false)->after('line_notes');
            $table->foreignId('carried_from_book_line_id')
                ->nullable()
                ->after('is_carried_forward')
                ->constrained('bank_book_lines')
                ->nullOnDelete();
            $table->foreignId('origin_reconciliation_id')
                ->nullable()
                ->after('carried_from_book_line_id')
                ->constrained('bank_reconciliations')
                ->nullOnDelete();
            $table->decimal('debit_foreign', 18, 2)->nullable()->after('credit');
            $table->decimal('credit_foreign', 18, 2)->nullable()->after('debit_foreign');
            $table->string('currency_code', 3)->nullable()->after('credit_foreign');
            $table->boolean('is_stale')->default(false)->after('currency_code');
            $table->string('stale_reason')->nullable()->after('is_stale');
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('adjusting_journal_id')->nullable()->after('ai_meta');
            $table->boolean('is_carried_forward')->default(false)->after('adjusting_journal_id');
            $table->foreignId('carried_from_bank_line_id')
                ->nullable()
                ->after('is_carried_forward')
                ->constrained('bank_statement_lines')
                ->nullOnDelete();
            $table->foreignId('origin_reconciliation_id')
                ->nullable()
                ->after('carried_from_bank_line_id')
                ->constrained('bank_reconciliations')
                ->nullOnDelete();
            $table->index('adjusting_journal_id', 'bsl_adjusting_journal_idx');
        });

        Schema::create('reconciliation_match_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->unsignedBigInteger('reconciliation_match_group_id')->nullable();
            $table->string('action');
            $table->string('match_type')->nullable();
            $table->decimal('bank_total', 18, 2)->nullable();
            $table->decimal('book_total', 18, 2)->nullable();
            $table->json('bank_line_ids')->nullable();
            $table->json('book_line_ids')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bank_reconciliation_id', 'action'], 'rma_recon_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_match_audits');

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropIndex('bsl_adjusting_journal_idx');
            $table->dropConstrainedForeignId('carried_from_bank_line_id');
            $table->dropConstrainedForeignId('origin_reconciliation_id');
            $table->dropColumn(['adjusting_journal_id', 'is_carried_forward']);
        });

        Schema::table('bank_book_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carried_from_book_line_id');
            $table->dropConstrainedForeignId('origin_reconciliation_id');
            $table->dropColumn([
                'is_carried_forward',
                'debit_foreign',
                'credit_foreign',
                'currency_code',
                'is_stale',
                'stale_reason',
            ]);
        });
    }
};
