<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('faktur_pajak_no', 50)->nullable()->after('invoice_no');
            $table->string('faktur_transaction_code', 10)->nullable()->after('faktur_pajak_no');
            $table->boolean('is_pkp')->default(true)->after('faktur_transaction_code');
            $table->decimal('dpp_nilai_lain', 18, 2)->nullable()->after('is_pkp');
            $table->decimal('ppnbm_amount', 18, 2)->nullable()->after('dpp_nilai_lain');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'faktur_pajak_no',
                'faktur_transaction_code',
                'is_pkp',
                'dpp_nilai_lain',
                'ppnbm_amount',
            ]);
        });
    }
};
