<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('tax_codes')->where('code', 'PPN12_OUT')->doesntExist()) {
            DB::table('tax_codes')->insert([
                'code' => 'PPN12_OUT',
                'name' => 'PPN Keluaran 12%',
                'type' => 'ppn_output',
                'rate' => 12.00,
                'calculation_method' => 'percentage',
                'reporting_frequency' => 'monthly',
                'is_mandatory' => 1,
                'is_active' => 1,
                'effective_date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('tax_codes')->where('code', 'PPN12_OUT')->delete();
    }
};
