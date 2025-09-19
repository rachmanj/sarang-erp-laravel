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
        Schema::create('business_partner_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_partner_id');
            $table->enum('contact_type', ['primary', 'billing', 'shipping', 'technical', 'sales', 'support'])->default('primary');
            $table->string('name', 150);
            $table->string('position', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('cascade');
            $table->index(['business_partner_id', 'contact_type'], 'bpc_bp_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partner_contacts');
    }
};
