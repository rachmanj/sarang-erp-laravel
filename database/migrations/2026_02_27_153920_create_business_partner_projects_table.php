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
        Schema::create('business_partner_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_partner_id')->constrained('business_partners')->onDelete('cascade');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['business_partner_id', 'code'], 'bpp_bp_code_unique');
            $table->index('business_partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partner_projects');
    }
};
