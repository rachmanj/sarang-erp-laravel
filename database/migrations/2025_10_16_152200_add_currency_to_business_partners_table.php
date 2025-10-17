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
        Schema::table('business_partners', function (Blueprint $table) {
            $table->foreignId('default_currency_id')->nullable()->after('partner_type')
                ->constrained('currencies')->onDelete('set null');

            $table->index(['default_currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_partners', function (Blueprint $table) {
            $table->dropForeign(['default_currency_id']);
            $table->dropIndex(['default_currency_id']);
            $table->dropColumn('default_currency_id');
        });
    }
};
