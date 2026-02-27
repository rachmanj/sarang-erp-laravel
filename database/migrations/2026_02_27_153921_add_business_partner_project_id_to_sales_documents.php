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
        $tables = ['sales_quotations', 'sales_orders', 'delivery_orders', 'sales_invoices'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('business_partner_project_id')->nullable()
                    ->after('business_partner_id')
                    ->constrained('business_partner_projects')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['sales_quotations', 'sales_orders', 'delivery_orders', 'sales_invoices'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['business_partner_project_id']);
            });
        }
    }
};
