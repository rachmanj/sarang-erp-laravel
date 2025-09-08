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
            if (!Schema::hasColumn('journal_lines', 'account_id')) {
                return;
            }
            try {
                $table->dropForeign(['account_id']);
            } catch (\Throwable $e) {
                // ignore if not exists
            }
            $table->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'fund_id')) {
                try {
                    $table->dropForeign(['fund_id']);
                } catch (\Throwable $e) {
                    // ignore if not exists
                }
                $table->foreign('fund_id')->references('id')->on('funds')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            try {
                $table->dropForeign(['account_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            try {
                $table->dropForeign(['fund_id']);
            } catch (\Throwable $e) {
            }
        });
    }
};
