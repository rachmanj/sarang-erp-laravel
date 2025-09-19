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
        Schema::create('business_partner_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_partner_id');
            $table->enum('section_type', ['taxation', 'terms', 'banking', 'financial', 'preferences', 'custom'])->default('custom');
            $table->string('field_name', 100);
            $table->text('field_value')->nullable();
            $table->string('field_type', 50)->default('text')->comment('text, number, date, boolean, json');
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('cascade');
            $table->index(['business_partner_id', 'section_type'], 'bpd_bp_section_idx');
            $table->unique(['business_partner_id', 'section_type', 'field_name'], 'bpd_bp_section_field_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partner_details');
    }
};
