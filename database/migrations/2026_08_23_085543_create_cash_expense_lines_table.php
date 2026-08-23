<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_expense_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 18, 2);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('dept_id')->nullable();
            $table->timestamps();

            $table->foreign('cash_expense_id')->references('id')->on('cash_expenses')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('dept_id')->references('id')->on('departments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_expense_lines');
    }
};
