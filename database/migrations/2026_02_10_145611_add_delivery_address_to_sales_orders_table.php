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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->after('delivery_method');
            $table->string('delivery_contact_person', 255)->nullable()->after('delivery_address');
            $table->string('delivery_phone', 50)->nullable()->after('delivery_contact_person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_address', 'delivery_contact_person', 'delivery_phone']);
        });
    }
};
