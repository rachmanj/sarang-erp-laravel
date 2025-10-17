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
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('account_id') // Nullable, defaults to IDR
                ->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('currency_id');
            $table->decimal('debit_foreign', 15, 2)->default(0)->after('debit');
            $table->decimal('credit_foreign', 15, 2)->default(0)->after('credit');

            $table->index(['currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropIndex(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'debit_foreign', 'credit_foreign']);
        });
    }
};
