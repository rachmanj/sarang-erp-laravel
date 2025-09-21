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
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_unit_id');
            $table->unsignedBigInteger('to_unit_id');
            $table->decimal('conversion_factor', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('from_unit_id')->references('id')->on('units_of_measure')->onDelete('cascade');
            $table->foreign('to_unit_id')->references('id')->on('units_of_measure')->onDelete('cascade');

            // Unique constraint to prevent duplicate conversions
            $table->unique(['from_unit_id', 'to_unit_id'], 'unique_unit_conversion');

            // Indexes
            $table->index('from_unit_id');
            $table->index('to_unit_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_conversions');
    }
};
