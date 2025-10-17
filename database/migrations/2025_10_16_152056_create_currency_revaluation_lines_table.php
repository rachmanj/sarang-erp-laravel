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
        Schema::create('currency_revaluation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revaluation_id')->constrained('currency_revaluations')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('business_partner_id')->nullable()->constrained('business_partners')->onDelete('cascade');
            $table->string('document_type')->nullable(); // purchase_invoice, sales_invoice, bank_account, etc.
            $table->unsignedBigInteger('document_id')->nullable(); // ID of the document
            $table->decimal('original_amount', 15, 2);
            $table->foreignId('original_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('original_exchange_rate', 12, 6);
            $table->decimal('revaluation_amount', 15, 2);
            $table->decimal('revaluation_exchange_rate', 12, 6);
            $table->decimal('unrealized_gain_loss', 15, 2); // Calculated field
            $table->timestamps();

            $table->index(['revaluation_id', 'account_id'], 'idx_reval_account');
            $table->index(['document_type', 'document_id'], 'idx_document_ref');
            $table->index(['business_partner_id', 'original_currency_id'], 'idx_partner_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_revaluation_lines');
    }
};
