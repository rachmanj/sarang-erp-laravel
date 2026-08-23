<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_account_id')->nullable()->after('description');
            $table->decimal('total_amount', 18, 2)->default(0)->after('cash_account_id');

            $table->foreign('cash_account_id')->references('id')->on('accounts');
        });

        $this->backfillCashExpenses();

        Schema::table('cash_expenses', function (Blueprint $table) {
            $table->dropColumn(['account_id', 'amount']);
        });
    }

    private function backfillCashExpenses(): void
    {
        $cashExpenses = DB::table('cash_expenses')->get(['id', 'account_id', 'amount', 'description', 'created_at', 'updated_at']);

        foreach ($cashExpenses as $cashExpense) {
            $cashAccountId = DB::table('journals as j')
                ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
                ->where('j.source_type', 'cash_expense')
                ->where('j.source_id', $cashExpense->id)
                ->where('jl.credit', '>', 0)
                ->value('jl.account_id');

            DB::table('cash_expense_lines')->insert([
                'cash_expense_id' => $cashExpense->id,
                'account_id' => $cashExpense->account_id,
                'amount' => $cashExpense->amount,
                'description' => $cashExpense->description,
                'project_id' => null,
                'dept_id' => null,
                'created_at' => $cashExpense->created_at ?? now(),
                'updated_at' => $cashExpense->updated_at ?? now(),
            ]);

            DB::table('cash_expenses')
                ->where('id', $cashExpense->id)
                ->update([
                    'cash_account_id' => $cashAccountId,
                    'total_amount' => $cashExpense->amount,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('cash_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('description');
            $table->decimal('amount', 18, 2)->default(0)->after('account_id');
        });

        $cashExpenses = DB::table('cash_expenses')->get(['id', 'total_amount', 'cash_account_id']);

        foreach ($cashExpenses as $cashExpense) {
            $line = DB::table('cash_expense_lines')
                ->where('cash_expense_id', $cashExpense->id)
                ->orderBy('id')
                ->first(['account_id', 'amount', 'description']);

            DB::table('cash_expenses')
                ->where('id', $cashExpense->id)
                ->update([
                    'account_id' => $line?->account_id,
                    'amount' => $line?->amount ?? $cashExpense->total_amount,
                    'description' => $line?->description ?? DB::table('cash_expenses')->where('id', $cashExpense->id)->value('description'),
                ]);
        }

        Schema::table('cash_expenses', function (Blueprint $table) {
            $table->dropForeign(['cash_account_id']);
            $table->dropColumn(['cash_account_id', 'total_amount']);
        });
    }
};
