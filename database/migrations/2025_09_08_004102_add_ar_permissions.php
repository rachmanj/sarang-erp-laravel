<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            $now = now();
            $perms = [
                'ar.invoices.view',
                'ar.invoices.create',
                'ar.invoices.post',
            ];
            foreach ($perms as $name) {
                if (!DB::table('permissions')->where('name', $name)->exists()) {
                    DB::table('permissions')->insert([
                        'name' => $name,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
            if ($adminRoleId) {
                foreach ($perms as $name) {
                    $pid = DB::table('permissions')->where('name', $name)->value('id');
                    if ($pid && !DB::table('role_has_permissions')->where(['role_id' => $adminRoleId, 'permission_id' => $pid])->exists()) {
                        DB::table('role_has_permissions')->insert(['role_id' => $adminRoleId, 'permission_id' => $pid]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // keep permissions to avoid accidental privilege loss
    }
};
