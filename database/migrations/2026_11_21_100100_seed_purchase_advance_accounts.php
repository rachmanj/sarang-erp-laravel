<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PARENT_CODE = '1.1.6';

    private const CHILD_CODE = '1.1.6.01';

    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $ref = DB::table('accounts')->where('code', '1.1.5')->first(['id', 'parent_id', 'report_group']);
        $grandparentId = $ref?->parent_id;
        $reportGroup = $ref?->report_group ?? 'current_assets';

        $now = now();

        DB::table('accounts')->updateOrInsert(
            ['code' => self::PARENT_CODE],
            [
                'name' => 'Uang Muka Pembelian',
                'type' => 'asset',
                'is_postable' => false,
                'normal_balance' => 'debit',
                'parent_id' => $grandparentId,
                'report_group' => $reportGroup,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $parentId = (int) DB::table('accounts')->where('code', self::PARENT_CODE)->value('id');

        DB::table('accounts')->updateOrInsert(
            ['code' => self::CHILD_CODE],
            [
                'name' => 'Uang Muka Pembelian',
                'type' => 'asset',
                'is_postable' => true,
                'normal_balance' => 'debit',
                'parent_id' => $parentId,
                'report_group' => $reportGroup,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')->whereIn('code', [self::CHILD_CODE, self::PARENT_CODE])->delete();
    }
};
