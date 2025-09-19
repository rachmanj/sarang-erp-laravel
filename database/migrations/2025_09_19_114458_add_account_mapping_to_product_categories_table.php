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
        Schema::table('product_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_account_id')->nullable()->after('parent_id');
            $table->unsignedBigInteger('cogs_account_id')->nullable()->after('inventory_account_id');
            $table->unsignedBigInteger('sales_account_id')->nullable()->after('cogs_account_id');

            // Foreign key constraints
            $table->foreign('inventory_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('cogs_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('sales_account_id')->references('id')->on('accounts')->onDelete('set null');

            // Indexes
            $table->index('inventory_account_id');
            $table->index('cogs_account_id');
            $table->index('sales_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['inventory_account_id']);
            $table->dropForeign(['cogs_account_id']);
            $table->dropForeign(['sales_account_id']);

            $table->dropIndex(['inventory_account_id']);
            $table->dropIndex(['cogs_account_id']);
            $table->dropIndex(['sales_account_id']);

            $table->dropColumn(['inventory_account_id', 'cogs_account_id', 'sales_account_id']);
        });
    }
};
