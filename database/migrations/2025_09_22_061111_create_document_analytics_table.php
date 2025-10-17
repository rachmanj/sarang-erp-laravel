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
        Schema::create('document_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->string('action'); // 'navigation_view', 'base_document_click', 'target_document_click', 'preview_journal_click'
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            // Indexes for performance
            $table->index(['document_type', 'document_id']);
            $table->index(['user_id']);
            $table->index(['timestamp']);
            $table->index(['action']);
            $table->index(['document_type', 'action', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_analytics');
    }
};
