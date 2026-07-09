<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->date('periode')->nullable()->after('bank_statement_id');
            $table->string('source_mode', 16)->default('ai')->after('status');
            $table->decimal('opening_balance_bank', 18, 2)->nullable()->after('source_mode');
            $table->decimal('closing_balance_bank', 18, 2)->nullable()->after('opening_balance_bank');
            $table->decimal('opening_balance_book', 18, 2)->nullable()->after('closing_balance_bank');
            $table->decimal('closing_balance_book', 18, 2)->nullable()->after('opening_balance_book');
            $table->text('notes')->nullable()->after('closing_balance_book');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        DB::table('bank_reconciliations')->orderBy('id')->each(function (object $row) {
            $periode = $row->period_start
                ? date('Y-m-01', strtotime((string) $row->period_start))
                : date('Y-m-01');

            $status = match ($row->status) {
                'finalized' => 'completed',
                'open' => 'in_review',
                default => $row->status,
            };

            DB::table('bank_reconciliations')->where('id', $row->id)->update([
                'periode' => $periode,
                'source_mode' => 'ai',
                'opening_balance_bank' => $row->statement_opening,
                'closing_balance_bank' => $row->statement_closing,
                'opening_balance_book' => null,
                'closing_balance_book' => $row->book_balance,
                'status' => $status,
            ]);
        });

        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->date('periode')->nullable(false)->change();
            $table->foreignId('bank_statement_id')->nullable()->change();
            $table->unique(['bank_account_id', 'periode'], 'bank_reconciliations_account_periode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            $table->dropUnique('bank_reconciliations_account_periode_unique');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'periode',
                'source_mode',
                'opening_balance_bank',
                'closing_balance_bank',
                'opening_balance_book',
                'closing_balance_book',
                'notes',
            ]);
        });

        DB::table('bank_reconciliations')->where('status', 'completed')->update(['status' => 'finalized']);
        DB::table('bank_reconciliations')->where('status', 'in_review')->update(['status' => 'open']);
    }
};
