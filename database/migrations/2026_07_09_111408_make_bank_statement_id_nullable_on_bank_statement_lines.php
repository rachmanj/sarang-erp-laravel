<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->foreignId('bank_statement_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->foreignId('bank_statement_id')->nullable(false)->change();
        });
    }
};
