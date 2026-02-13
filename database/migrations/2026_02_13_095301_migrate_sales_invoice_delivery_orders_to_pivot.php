<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('sales_invoices', 'delivery_order_id')) {
            return;
        }

        $invoices = DB::table('sales_invoices')
            ->whereNotNull('delivery_order_id')
            ->get(['id', 'delivery_order_id']);

        foreach ($invoices as $inv) {
            DB::table('delivery_order_sales_invoice')->insertOrIgnore([
                'delivery_order_id' => $inv->delivery_order_id,
                'sales_invoice_id' => $inv->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn('delivery_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_order_id')->nullable()->after('sales_order_id');
            $table->foreign('delivery_order_id')->references('id')->on('delivery_orders')->onDelete('set null');
        });

        $pivots = DB::table('delivery_order_sales_invoice')->get();
        foreach ($pivots as $p) {
            DB::table('sales_invoices')
                ->where('id', $p->sales_invoice_id)
                ->update(['delivery_order_id' => $p->delivery_order_id]);
        }

        DB::table('delivery_order_sales_invoice')->truncate();
    }
};
