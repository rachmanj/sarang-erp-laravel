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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, SGD, EUR, etc.
            $table->string('name', 100); // US Dollar, Singapore Dollar, etc.
            $table->string('symbol', 10)->nullable(); // $, €, ¥, etc.
            $table->tinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_base_currency')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'is_base_currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
