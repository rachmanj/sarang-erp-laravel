<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_reconciliation_matches')) {
            return;
        }

        DB::table('bank_reconciliation_matches')->orderBy('id')->each(function (object $match) {
            $bankLine = DB::table('bank_statement_lines')->where('id', $match->bank_statement_line_id)->first();
            if (! $bankLine) {
                return;
            }

            $journalLine = $match->journal_line_id
                ? DB::table('journal_lines as jl')
                    ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                    ->where('jl.id', $match->journal_line_id)
                    ->select(['jl.*', 'j.date', 'j.description as journal_description', 'j.source_type', 'j.source_id'])
                    ->first()
                : null;

            $bookLineId = null;
            if ($journalLine) {
                $bookLineId = DB::table('bank_book_lines')->insertGetId([
                    'bank_reconciliation_id' => $match->bank_reconciliation_id,
                    'journal_line_id' => $journalLine->id,
                    'doc_date' => $journalLine->date,
                    'posting_date' => $journalLine->date,
                    'doc_num' => (string) ($journalLine->journal_id ?? ''),
                    'ref_doc_num' => null,
                    'transaction_id' => null,
                    'description' => trim(($journalLine->journal_description ?? '').' '.($journalLine->memo ?? '')),
                    'project_code' => null,
                    'debit' => $journalLine->debit,
                    'credit' => $journalLine->credit,
                    'match_status' => $match->match_type === 'manual' ? 'manual' : 'matched',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $bankNet = (float) $bankLine->debit - (float) $bankLine->credit;
            $bookNet = $journalLine ? (float) $journalLine->debit - (float) $journalLine->credit : 0;

            $matchType = match ($match->match_type) {
                'manual' => 'manual',
                'ai' => 'auto_fuzzy',
                'adjustment' => 'manual',
                default => 'auto_exact',
            };

            $groupId = DB::table('reconciliation_match_groups')->insertGetId([
                'bank_reconciliation_id' => $match->bank_reconciliation_id,
                'match_type' => $matchType,
                'confidence_score' => $match->confidence,
                'bank_total' => $bankNet,
                'book_total' => $bookNet,
                'difference' => round($bankNet + $bookNet, 2),
                'notes' => null,
                'created_by' => $match->created_by,
                'created_at' => $match->created_at ?? now(),
                'updated_at' => $match->updated_at ?? now(),
            ]);

            DB::table('match_group_bank_lines')->insert([
                'reconciliation_match_group_id' => $groupId,
                'bank_statement_line_id' => $match->bank_statement_line_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($bookLineId) {
                DB::table('match_group_book_lines')->insert([
                    'reconciliation_match_group_id' => $groupId,
                    'bank_book_line_id' => $bookLineId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::dropIfExists('bank_reconciliation_matches');
    }

    public function down(): void
    {
        Schema::create('bank_reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->constrained('bank_statement_lines')->cascadeOnDelete();
            $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->string('match_type', 20);
            $table->decimal('amount', 18, 2);
            $table->decimal('confidence', 5, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('bank_statement_line_id');
            $table->index(['bank_reconciliation_id', 'match_type'], 'br_matches_recon_match_type_idx');
            $table->index('journal_line_id');
        });
    }
};
