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
        Schema::create('control_account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_account_id')->constrained('control_accounts')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('dept_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->decimal('balance', 18, 2)->default(0.00)->comment('Current balance of the control account');
            $table->timestamp('last_reconciled_at')->nullable()->comment('Last reconciliation timestamp');
            $table->decimal('reconciled_balance', 18, 2)->nullable()->comment('Balance at last reconciliation');
            $table->timestamps();
            
            $table->unique(['control_account_id', 'project_id', 'dept_id'], 'unique_control_balance');
            $table->index(['control_account_id', 'last_reconciled_at'], 'control_reconciled_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_account_balances');
    }
};
