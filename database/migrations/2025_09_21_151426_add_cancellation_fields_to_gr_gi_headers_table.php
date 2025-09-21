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
        Schema::table('gr_gi_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('approved_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            
            $table->foreign('cancelled_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gr_gi_headers', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancelled_at']);
        });
    }
};
