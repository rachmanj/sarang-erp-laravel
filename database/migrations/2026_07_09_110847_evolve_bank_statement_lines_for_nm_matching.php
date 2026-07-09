<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->foreignId('bank_reconciliation_id')->nullable()->after('id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->decimal('debit', 18, 2)->default(0)->after('reference_no');
            $table->decimal('credit', 18, 2)->default(0)->after('debit');
            $table->string('exclude_reason', 500)->nullable()->after('match_status');
            $table->string('line_notes', 500)->nullable()->after('exclude_reason');
            $table->unsignedInteger('line_order')->nullable()->after('line_notes');
            $table->boolean('is_ai_extracted')->default(true)->after('line_order');
            $table->float('ai_confidence')->nullable()->after('is_ai_extracted');
        });

        DB::table('bank_statement_lines')->orderBy('id')->each(function (object $line) {
            $debit = $line->direction === 'debit' ? $line->amount : 0;
            $credit = $line->direction === 'credit' ? $line->amount : 0;

            $matchStatus = match ($line->match_status) {
                'ignored' => 'excluded',
                'adjustment', 'matched' => 'matched',
                'manual' => 'manual',
                default => 'unmatched',
            };

            $reconciliationId = DB::table('bank_reconciliations')
                ->where('bank_statement_id', $line->bank_statement_id)
                ->value('id');

            DB::table('bank_statement_lines')->where('id', $line->id)->update([
                'bank_reconciliation_id' => $reconciliationId,
                'debit' => $debit,
                'credit' => $credit,
                'match_status' => $matchStatus,
                'is_ai_extracted' => true,
            ]);
        });

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->index(['bank_reconciliation_id', 'match_status'], 'bsl_recon_match_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropIndex('bsl_recon_match_status_idx');
            $table->dropConstrainedForeignId('bank_reconciliation_id');
            $table->dropColumn([
                'debit',
                'credit',
                'exclude_reason',
                'line_notes',
                'line_order',
                'is_ai_extracted',
                'ai_confidence',
            ]);
        });
    }
};
