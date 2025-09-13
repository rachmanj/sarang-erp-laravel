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
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->date('movement_date');
            $table->enum('movement_type', ['transfer', 'relocation', 'custodian_change', 'maintenance', 'other']);
            $table->string('from_location')->nullable(); // Previous location
            $table->string('to_location')->nullable(); // New location
            $table->string('from_custodian')->nullable(); // Previous custodian
            $table->string('to_custodian')->nullable(); // New custodian
            $table->text('movement_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable(); // Document reference
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();

            // Indexes
            $table->index(['asset_id', 'movement_date']);
            $table->index(['movement_date', 'status']);
            $table->index('movement_type');
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
