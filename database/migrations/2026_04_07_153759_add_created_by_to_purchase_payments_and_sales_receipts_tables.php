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
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_payments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('company_entity_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('sales_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_receipts', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('company_entity_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_payments', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });

        Schema::table('sales_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('sales_receipts', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
