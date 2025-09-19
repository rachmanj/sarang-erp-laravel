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
        Schema::create('account_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_statement_id')->constrained('account_statements')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('reference_type', 50); // journal, sales_invoice, purchase_invoice, sales_receipt, purchase_payment, etc.
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_no', 100)->nullable(); // Document number for easy reference
            $table->text('description');
            $table->decimal('debit_amount', 18, 2)->default(0);
            $table->decimal('credit_amount', 18, 2)->default(0);
            $table->decimal('running_balance', 18, 2)->default(0);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('dept_id')->nullable();
            $table->string('memo', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('dept_id')->references('id')->on('departments')->onDelete('set null');

            // Indexes for performance
            $table->index(['account_statement_id', 'transaction_date'], 'asl_statement_date_idx');
            $table->index(['reference_type', 'reference_id'], 'asl_reference_idx');
            $table->index(['transaction_date', 'sort_order'], 'asl_date_sort_idx');
            $table->index(['project_id', 'dept_id'], 'asl_dimensions_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_statement_lines');
    }
};
