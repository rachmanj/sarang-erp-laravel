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
        // Add permissions for period management
        if (Schema::hasTable('permissions')) {
            $now = now();
            $perms = [
                ['name' => 'periods.view', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'periods.close', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now],
            ];
            foreach ($perms as $p) {
                $exists = DB::table('permissions')->where('name', $p['name'])->exists();
                if (!$exists) {
                    DB::table('permissions')->insert($p);
                }
            }

            // Grant to admin role if exists
            $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
            if ($adminRoleId) {
                foreach ($perms as $p) {
                    $permId = DB::table('permissions')->where('name', $p['name'])->value('id');
                    if ($permId) {
                        $exists = DB::table('role_has_permissions')
                            ->where('role_id', $adminRoleId)
                            ->where('permission_id', $permId)
                            ->exists();
                        if (!$exists) {
                            DB::table('role_has_permissions')->insert([
                                'role_id' => $adminRoleId,
                                'permission_id' => $permId,
                            ]);
                        }
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
        // We do not delete permissions on down to avoid accidental privilege loss
    }
};
