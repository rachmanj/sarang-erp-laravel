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
        Schema::create('account_statements', function (Blueprint $table) {
            $table->id();
            $table->string('statement_no', 50)->unique();
            $table->enum('statement_type', ['gl_account', 'business_partner']);
            $table->unsignedBigInteger('account_id')->nullable(); // For GL accounts
            $table->unsignedBigInteger('business_partner_id')->nullable(); // For business partners
            $table->date('statement_date');
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->decimal('total_debits', 18, 2)->default(0);
            $table->decimal('total_credits', 18, 2)->default(0);
            $table->enum('status', ['draft', 'finalized', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('finalized_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['statement_type', 'account_id']);
            $table->index(['statement_type', 'business_partner_id']);
            $table->index(['statement_date', 'from_date', 'to_date']);
            $table->index(['status', 'created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_statements');
    }
};
