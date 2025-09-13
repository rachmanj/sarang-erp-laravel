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
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('life_months_default')->nullable(); // Useful life in months
            $table->enum('method_default', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->decimal('salvage_value_policy', 15, 2)->default(0); // Default salvage value percentage or fixed amount
            $table->boolean('non_depreciable')->default(false); // For assets like land

            // Account mappings
            $table->unsignedBigInteger('asset_account_id'); // e.g., 1.2.1 Fixed Assets - Equipment
            $table->unsignedBigInteger('accumulated_depreciation_account_id'); // e.g., 1.2.3 Accumulated Depreciation - Equipment
            $table->unsignedBigInteger('depreciation_expense_account_id'); // e.g., 5.2.6 Depreciation Expense
            $table->unsignedBigInteger('gain_on_disposal_account_id')->nullable(); // e.g., 4.2.1 Other Income
            $table->unsignedBigInteger('loss_on_disposal_account_id')->nullable(); // e.g., 5.3.1 Other Expenses

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('asset_account_id')->references('id')->on('accounts');
            $table->foreign('accumulated_depreciation_account_id')->references('id')->on('accounts');
            $table->foreign('depreciation_expense_account_id')->references('id')->on('accounts');
            $table->foreign('gain_on_disposal_account_id')->references('id')->on('accounts');
            $table->foreign('loss_on_disposal_account_id')->references('id')->on('accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
