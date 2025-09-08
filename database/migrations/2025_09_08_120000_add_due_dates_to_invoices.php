<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_invoices')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_invoices', 'due_date')) {
                    $table->date('due_date')->nullable()->after('date');
                }
                if (!Schema::hasColumn('sales_invoices', 'terms_days')) {
                    $table->integer('terms_days')->nullable()->after('due_date');
                }
            });
        }
        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_invoices', 'due_date')) {
                    $table->date('due_date')->nullable()->after('date');
                }
                if (!Schema::hasColumn('purchase_invoices', 'terms_days')) {
                    $table->integer('terms_days')->nullable()->after('due_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_invoices')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('sales_invoices', 'terms_days')) {
                    $table->dropColumn('terms_days');
                }
                if (Schema::hasColumn('sales_invoices', 'due_date')) {
                    $table->dropColumn('due_date');
                }
            });
        }
        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_invoices', 'terms_days')) {
                    $table->dropColumn('terms_days');
                }
                if (Schema::hasColumn('purchase_invoices', 'due_date')) {
                    $table->dropColumn('due_date');
                }
            });
        }
    }
};
