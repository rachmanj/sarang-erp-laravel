<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->nullOnDelete();
            $table->string('branch', 150)->nullable()->after('account_number');
            $table->boolean('is_active')->default(true)->after('is_restricted');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropColumn(['branch', 'is_active']);
        });
    }
};
