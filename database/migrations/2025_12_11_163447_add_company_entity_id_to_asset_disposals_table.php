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
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->foreignId('company_entity_id')
                ->nullable()
                ->after('asset_id')
                ->constrained('company_entities')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_disposals', function (Blueprint $table) {
            $table->dropForeign(['company_entity_id']);
            $table->dropColumn('company_entity_id');
        });
    }
};
