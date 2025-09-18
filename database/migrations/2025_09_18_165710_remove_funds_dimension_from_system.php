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
        // Drop foreign key constraints first
        Schema::table('journal_lines', function (Blueprint $table) {
            if (Schema::hasColumn('journal_lines', 'fund_id')) {
                $table->dropForeign(['fund_id']);
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoice_lines', 'fund_id')) {
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('sales_receipt_lines', function (Blueprint $table) {
            if (Schema::hasColumn('sales_receipt_lines', 'fund_id')) {
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_lines', 'fund_id')) {
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('purchase_payment_lines', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_payment_lines', 'fund_id')) {
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'fund_id')) {
                $table->dropForeign(['fund_id']);
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('asset_depreciation_entries', function (Blueprint $table) {
            if (Schema::hasColumn('asset_depreciation_entries', 'fund_id')) {
                $table->dropForeign(['fund_id']);
                $table->dropColumn('fund_id');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'fund_id')) {
                $table->dropForeign(['fund_id']);
                $table->dropColumn('fund_id');
            }
        });

        // Drop the funds table itself
        Schema::dropIfExists('funds');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate funds table
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->boolean('is_restricted')->default(false);
            $table->timestamps();
        });

        // Add fund_id columns back
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('sales_invoice_lines', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('sales_receipt_lines', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('purchase_invoice_lines', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('purchase_payment_lines', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('asset_depreciation_entries', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->constrained('funds');
        });
    }
};
