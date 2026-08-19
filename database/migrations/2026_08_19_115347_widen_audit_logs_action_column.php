<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 64)->index()->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('action', 20)->index()->nullable(false)->change();
        });
    }
};
