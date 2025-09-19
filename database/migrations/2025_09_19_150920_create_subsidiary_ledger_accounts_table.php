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
        Schema::create('subsidiary_ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_account_id')->constrained('control_accounts')->onDelete('cascade');
            $table->enum('subsidiary_type', ['business_partner', 'inventory_item', 'fixed_asset'])->comment('Type of subsidiary entity');
            $table->unsignedBigInteger('subsidiary_id')->comment('ID of the subsidiary entity');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['control_account_id', 'subsidiary_type', 'subsidiary_id'], 'subsidiary_unique');
            $table->index(['subsidiary_type', 'subsidiary_id']);
            $table->index(['control_account_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidiary_ledger_accounts');
    }
};
