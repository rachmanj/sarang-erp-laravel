<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_items')
            ->where('valuation_method', 'lifo')
            ->update(['valuation_method' => 'weighted_average']);

        DB::table('inventory_valuations')
            ->where('valuation_method', 'lifo')
            ->update(['valuation_method' => 'weighted_average']);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->enum('valuation_method', ['fifo', 'weighted_average'])->default('fifo')->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->enum('valuation_method', ['fifo', 'lifo', 'weighted_average'])->default('fifo')->change();
        });
    }
};
