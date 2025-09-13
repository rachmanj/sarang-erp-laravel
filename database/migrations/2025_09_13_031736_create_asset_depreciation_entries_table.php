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
        Schema::create('asset_depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('period', 7); // Format: YYYY-MM
            $table->decimal('amount', 15, 2);
            $table->enum('book', ['financial', 'tax'])->default('financial'); // For future multi-book support
            $table->unsignedBigInteger('journal_id')->nullable(); // Link to journal when posted

            // Dimension snapshot at time of posting
            $table->unsignedBigInteger('fund_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('journal_id')->references('id')->on('journals');
            $table->foreign('fund_id')->references('id')->on('funds');
            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('department_id')->references('id')->on('departments');

            // Unique constraint to prevent duplicate entries
            $table->unique(['asset_id', 'period', 'book']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_entries');
    }
};
