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
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_code_id');
            $table->enum('transaction_type', ['purchase', 'sale', 'payroll', 'other']);
            $table->decimal('base_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'paid', 'reported'])->default('pending');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->onDelete('cascade');

            // Indexes
            $table->index(['tax_code_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['status', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
    }
};
