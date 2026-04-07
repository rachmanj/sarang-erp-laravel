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
        Schema::table('goods_receipt_po', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('company_entity_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipt_po', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
