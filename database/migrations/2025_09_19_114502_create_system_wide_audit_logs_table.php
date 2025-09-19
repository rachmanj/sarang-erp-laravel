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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50)->index(); // 'inventory_item', 'sales_order', 'business_partner', etc.
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('action', 20)->index(); // 'created', 'updated', 'deleted', 'approved', 'rejected'
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Composite index for entity lookups
            $table->index(['entity_type', 'entity_id']);
            $table->index(['entity_type', 'action']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
