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
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no', 50)->unique();
            $table->string('reference_no', 100)->nullable();
            $table->date('date');
            $table->date('valid_until_date');
            $table->unsignedBigInteger('business_partner_id');
            $table->unsignedBigInteger('company_entity_id');
            $table->unsignedBigInteger('currency_id')->default(1);
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('delivery_method', 100)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_amount_foreign', 15, 2)->default(0);
            $table->decimal('freight_cost', 15, 2)->default(0);
            $table->decimal('handling_cost', 15, 2)->default(0);
            $table->decimal('insurance_cost', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->enum('order_type', ['item', 'service'])->default('item');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('converted_to_sales_order_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('restrict');
            $table->foreign('company_entity_id')->references('id')->on('company_entities')->onDelete('restrict');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('converted_to_sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index('quotation_no');
            $table->index('business_partner_id');
            $table->index('status');
            $table->index('valid_until_date');
            $table->index('company_entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_quotations');
    }
};
