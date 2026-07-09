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

        $viewPermission = DB::table('permissions')->where('name', 'bank_reconciliation.view')->first();
        if (! $viewPermission) {
            return;
        }

        $roleNamesWithBankAccess = DB::table('roles')
            ->whereIn('name', ['superadmin', 'accountant', 'approver'])
            ->pluck('id', 'name');

        foreach ($roleNamesWithBankAccess as $roleId) {
            $this->attachPermission((int) $roleId, (int) $viewPermission->id);
        }

        $bankAccountPermissionIds = DB::table('permissions')
            ->whereIn('name', ['bank_accounts.view', 'bank_accounts.manage'])
            ->pluck('id');

        if ($bankAccountPermissionIds->isEmpty()) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $roleIdsWithBankAccounts = DB::table('role_has_permissions')
            ->whereIn('permission_id', $bankAccountPermissionIds)
            ->distinct()
            ->pluck('role_id');

        foreach ($roleIdsWithBankAccounts as $roleId) {
            $this->attachPermission((int) $roleId, (int) $viewPermission->id);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permissions are shared; do not revoke on rollback.
    }

    private function attachPermission(int $roleId, int $permissionId): void
    {
        if (DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists()) {
            return;
        }

        DB::table('role_has_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }
};
