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
        Schema::create('document_relationships', function (Blueprint $table) {
            $table->id();
            $table->string('source_document_type', 50)->index();
            $table->unsignedBigInteger('source_document_id')->index();
            $table->string('target_document_type', 50)->index();
            $table->unsignedBigInteger('target_document_id')->index();
            $table->enum('relationship_type', ['base', 'target', 'related'])->default('related');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Composite indexes for efficient querying
            $table->index(['source_document_type', 'source_document_id'], 'idx_source_document');
            $table->index(['target_document_type', 'target_document_id'], 'idx_target_document');
            $table->index(['relationship_type'], 'idx_relationship_type');
            
            // Unique constraint to prevent duplicate relationships
            $table->unique([
                'source_document_type', 
                'source_document_id', 
                'target_document_type', 
                'target_document_id', 
                'relationship_type'
            ], 'unique_document_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_relationships');
    }
};