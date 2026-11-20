<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_advances', function (Blueprint $table) {
            $table->id();
            $table->string('advance_no')->nullable()->unique();
            $table->date('date');
            $table->unsignedBigInteger('business_partner_id');
            $table->unsignedBigInteger('company_entity_id');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('business_partner_id');
            $table->index('status');
            $table->index('date');
        });

        Schema::create('purchase_advance_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advance_id');
            $table->unsignedBigInteger('account_id');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index('advance_id');
        });

        Schema::create('purchase_advance_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advance_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['advance_id', 'invoice_id']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_advance_applications');
        Schema::dropIfExists('purchase_advance_lines');
        Schema::dropIfExists('purchase_advances');
    }
};
