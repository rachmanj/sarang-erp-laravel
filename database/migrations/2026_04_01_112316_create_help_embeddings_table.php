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
        Schema::create('help_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('chunk_key', 64)->unique();
            $table->string('source_path', 512);
            $table->string('heading', 512)->nullable();
            $table->string('locale', 8);
            $table->longText('content');
            $table->json('embedding');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_embeddings');
    }
};
