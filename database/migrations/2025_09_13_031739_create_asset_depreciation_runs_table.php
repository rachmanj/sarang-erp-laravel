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
        Schema::create('asset_depreciation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7); // Format: YYYY-MM
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->decimal('total_depreciation', 15, 2)->default(0);
            $table->integer('asset_count')->default(0);
            $table->unsignedBigInteger('journal_id')->nullable(); // Link to journal when posted
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('journal_id')->references('id')->on('journals');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('posted_by')->references('id')->on('users');

            // Unique constraint to prevent duplicate runs per period
            $table->unique('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_runs');
    }
};
