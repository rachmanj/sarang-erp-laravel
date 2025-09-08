<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('journal_no', 32)->nullable()->after('id');
        });

        // Backfill for existing rows
        DB::table('journals')->orderBy('id')->select('id', 'date')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $prefix = 'JNL-' . date('Ym', strtotime($row->date));
                $no = sprintf('%s-%06d', $prefix, $row->id);
                DB::table('journals')->where('id', $row->id)->update(['journal_no' => $no]);
            }
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->string('journal_no', 32)->nullable(false)->change();
            $table->unique('journal_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropUnique(['journal_no']);
            $table->dropColumn('journal_no');
        });
    }
};
