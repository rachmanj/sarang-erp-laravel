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
        Schema::create('business_partner_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_partner_id');
            $table->enum('address_type', ['billing', 'shipping', 'registered', 'warehouse', 'office'])->default('billing');
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100);
            $table->string('state_province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Indonesia');
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_partner_id')->references('id')->on('business_partners')->onDelete('cascade');
            $table->index(['business_partner_id', 'address_type'], 'bpa_bp_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partner_addresses');
    }
};
