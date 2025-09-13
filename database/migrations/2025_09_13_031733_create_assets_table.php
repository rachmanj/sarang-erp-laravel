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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();

            // Category and financial data
            $table->unsignedBigInteger('category_id');
            $table->decimal('acquisition_cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->decimal('current_book_value', 15, 2); // Computed field
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);

            // Depreciation settings
            $table->enum('method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->integer('life_months');
            $table->date('placed_in_service_date');

            // Status and tracking
            $table->enum('status', ['active', 'retired', 'disposed'])->default('active');
            $table->date('disposal_date')->nullable();

            // Dimensions (optional overrides)
            $table->unsignedBigInteger('fund_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();

            // Source tracking
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('purchase_invoice_id')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('category_id')->references('id')->on('asset_categories');
            $table->foreign('fund_id')->references('id')->on('funds');
            $table->foreign('project_id')->references('id')->on('projects');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
