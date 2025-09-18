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
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->index();
            $table->string('year_month', 6)->index(); // YYYYMM format
            $table->integer('last_sequence')->default(0);
            $table->timestamps();

            // Unique constraint to ensure one sequence per document type per month
            $table->unique(['document_type', 'year_month'], 'unique_type_month');

            // Indexes for performance
            $table->index(['document_type', 'year_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
