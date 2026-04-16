<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_credit_memos', function (Blueprint $table) {
            $table->id();
            $table->string('memo_no')->nullable()->unique();
            $table->date('date');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->unique('sales_invoice_id');
            $table->unsignedBigInteger('business_partner_id');
            $table->unsignedBigInteger('business_partner_project_id')->nullable();
            $table->unsignedBigInteger('company_entity_id');
            $table->string('description')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'date']);
            $table->index('company_entity_id');
        });

        Schema::create('sales_credit_memo_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_memo_id')->constrained('sales_credit_memos')->cascadeOnDelete();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('delivery_order_line_id')->nullable();
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->string('description')->nullable();
            $table->decimal('qty', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedBigInteger('tax_code_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('dept_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_credit_memo_lines');
        Schema::dropIfExists('sales_credit_memos');
    }
};
