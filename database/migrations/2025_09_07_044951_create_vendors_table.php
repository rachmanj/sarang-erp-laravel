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
        Schema::create('business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->enum('partner_type', ['customer', 'supplier', 'both'])->default('customer');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('registration_number', 30)->nullable()->comment('NPWP for Indonesian companies');
            $table->string('tax_id', 50)->nullable()->comment('Additional tax identifiers');
            $table->string('website', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partners');
    }
};
