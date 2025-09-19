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
            $table->unsignedBigInteger('account_id')->nullable()->after('status');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_partners', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropIndex(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
