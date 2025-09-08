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
        Schema::create('purchase_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('account_id');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('fund_id')->nullable();
            $table->unsignedBigInteger('dept_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payment_lines');
    }
};
