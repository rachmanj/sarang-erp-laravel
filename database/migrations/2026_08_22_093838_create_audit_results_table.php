<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_run_id')->constrained('audit_runs')->cascadeOnDelete();
            $table->string('check_key', 64);
            $table->string('check_name', 128);
            $table->string('status', 16);
            $table->unsignedInteger('issue_count')->default(0);
            $table->longText('details')->nullable();
            $table->timestamps();

            $table->index(['audit_run_id', 'check_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_results');
    }
};
