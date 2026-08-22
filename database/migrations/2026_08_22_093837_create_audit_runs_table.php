<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 32)->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('triggered_by', 64)->nullable();
            $table->unsignedSmallInteger('total_checks')->default(0);
            $table->unsignedSmallInteger('passed_checks')->default(0);
            $table->unsignedSmallInteger('failed_checks')->default(0);
            $table->unsignedInteger('total_issues')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_runs');
    }
};
