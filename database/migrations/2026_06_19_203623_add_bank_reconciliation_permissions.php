<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissions = [
            'bank_accounts.view',
            'bank_accounts.manage',
            'bank_reconciliation.view',
            'bank_reconciliation.import',
            'bank_reconciliation.reconcile',
            'bank_reconciliation.finalize',
        ];

        foreach ($permissions as $permissionName) {
            if (! DB::table('permissions')->where('name', $permissionName)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (['superadmin', 'admin', 'accountant'] as $roleName) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            foreach ($permissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if ($permission && ! DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists()) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            'bank_accounts.view',
            'bank_accounts.manage',
            'bank_reconciliation.view',
            'bank_reconciliation.import',
            'bank_reconciliation.reconcile',
            'bank_reconciliation.finalize',
        ];

        foreach ($permissions as $permissionName) {
            DB::table('permissions')->where('name', $permissionName)->delete();
        }
    }
};
