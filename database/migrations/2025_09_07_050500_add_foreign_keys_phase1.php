<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasTable('periods')) {
                try {
                    $table->foreign('period_id')->references('id')->on('periods')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            if (Schema::hasTable('projects')) {
                try {
                    $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
            if (Schema::hasTable('funds')) {
                try {
                    $table->foreign('fund_id')->references('id')->on('funds')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
            if (Schema::hasTable('departments')) {
                try {
                    $table->foreign('dept_id')->references('id')->on('departments')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasTable('funds')) {
                $table->foreign('fund_id')->references('id')->on('funds')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['fund_id']);
        });
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['fund_id']);
            $table->dropForeign(['dept_id']);
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
        });
    }
};
