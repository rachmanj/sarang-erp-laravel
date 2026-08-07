<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_receipts', function (Blueprint $table) {
            $table->decimal('rounding_amount', 15, 2)->default(0)->after('total_amount');
            $table->unsignedBigInteger('rounding_account_id')->nullable()->after('rounding_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_receipts', function (Blueprint $table) {
            $table->dropColumn(['rounding_amount', 'rounding_account_id']);
        });
    }
};
