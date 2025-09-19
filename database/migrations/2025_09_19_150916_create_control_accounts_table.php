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
        Schema::create('control_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->enum('control_type', ['ar', 'ap', 'inventory', 'fixed_assets'])->comment('Type of control account');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['account_id', 'control_type']);
            $table->index(['control_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_accounts');
    }
};
