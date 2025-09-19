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
            $accountStatementPermissions = [
                'account_statements.view',
                'account_statements.create',
                'account_statements.update',
                'account_statements.delete',
            ];

            foreach ($accountStatementPermissions as $permissionName) {
                if (!DB::table('permissions')->where('name', $permissionName)->exists()) {
                    DB::table('permissions')->insert([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Grant all permissions to admin role if exists
            $adminRole = DB::table('roles')->where('name', 'admin')->first();
            if ($adminRole) {
                foreach ($accountStatementPermissions as $permissionName) {
                    $permission = DB::table('permissions')->where('name', $permissionName)->first();
                    if ($permission && !DB::table('role_has_permissions')
                        ->where('role_id', $adminRole->id)
                        ->where('permission_id', $permission->id)
                        ->exists()) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $adminRole->id,
                            'permission_id' => $permission->id,
                        ]);
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
        if (Schema::hasTable('permissions')) {
            $accountStatementPermissions = [
                'account_statements.view',
                'account_statements.create',
                'account_statements.update',
                'account_statements.delete',
            ];

            foreach ($accountStatementPermissions as $permissionName) {
                DB::table('permissions')->where('name', $permissionName)->delete();
            }
        }
    }
};
