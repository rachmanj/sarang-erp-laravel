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
        // Create GR/GI Purposes table
        Schema::create('gr_gi_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('type', ['goods_receipt', 'goods_issue']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create GR/GI Headers table
        Schema::create('gr_gi_headers', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 50)->unique();
            $table->enum('document_type', ['goods_receipt', 'goods_issue']);
            $table->unsignedBigInteger('purpose_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->date('transaction_date');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('purpose_id')->references('id')->on('gr_gi_purposes');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['document_type', 'status']);
            $table->index(['transaction_date']);
            $table->index(['warehouse_id']);
        });

        // Create GR/GI Lines table
        Schema::create('gr_gi_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('header_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('header_id')->references('id')->on('gr_gi_headers')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items');

            $table->index(['header_id']);
            $table->index(['item_id']);
        });

        // Create GR/GI Account Mappings table
        Schema::create('gr_gi_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purpose_id');
            $table->unsignedBigInteger('item_category_id');
            $table->unsignedBigInteger('debit_account_id')->nullable(); // For GI: manual selection
            $table->unsignedBigInteger('credit_account_id')->nullable(); // For GR: manual selection
            $table->timestamps();

            $table->foreign('purpose_id')->references('id')->on('gr_gi_purposes');
            $table->foreign('item_category_id')->references('id')->on('product_categories');
            $table->foreign('debit_account_id')->references('id')->on('accounts');
            $table->foreign('credit_account_id')->references('id')->on('accounts');

            $table->unique(['purpose_id', 'item_category_id']);
        });

        // Create GR/GI Journal Entries table
        Schema::create('gr_gi_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('header_id');
            $table->unsignedBigInteger('line_id')->nullable();
            $table->enum('gr_gi_type', ['goods_receipt', 'goods_issue']);
            $table->unsignedBigInteger('journal_entry_id');
            $table->timestamps();

            $table->foreign('header_id')->references('id')->on('gr_gi_headers')->onDelete('cascade');
            $table->foreign('line_id')->references('id')->on('gr_gi_lines')->onDelete('cascade');
            $table->foreign('journal_entry_id')->references('id')->on('journals');

            $table->index(['header_id']);
            $table->index(['journal_entry_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gr_gi_journal_entries');
        Schema::dropIfExists('gr_gi_account_mappings');
        Schema::dropIfExists('gr_gi_lines');
        Schema::dropIfExists('gr_gi_headers');
        Schema::dropIfExists('gr_gi_purposes');
    }
};
