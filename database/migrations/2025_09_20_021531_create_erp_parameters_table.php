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
        Schema::create('erp_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->index(); // Document closure, System settings, etc.
            $table->string('parameter_key', 100)->index(); // po_overdue_days, grpo_overdue_days, etc.
            $table->string('parameter_name', 200); // Human readable name
            $table->text('parameter_value'); // Value (can be JSON for complex data)
            $table->string('data_type', 50)->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable(); // Help text
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['category', 'parameter_key']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_parameters');
    }
};
