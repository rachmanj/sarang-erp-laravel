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
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->enum('type', ['ppn_output', 'ppn_input', 'withholding']);
            $table->decimal('rate', 6, 4)->default(0);
            $table->enum('calculation_method', ['percentage', 'fixed_amount', 'tiered'])->default('percentage');
            $table->enum('reporting_frequency', ['monthly', 'quarterly', 'annually'])->default('monthly');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->date('effective_date');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['type', 'is_active']);
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};
