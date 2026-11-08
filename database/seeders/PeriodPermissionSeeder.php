<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PeriodPermissionSeeder extends Seeder
{
    /**
     * Add period close permissions to roles (additive, idempotent).
     */
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $permissionNames = [
            'periods.view',
            'periods.close',
        ];

        $newlyCreated = [];

        foreach ($permissionNames as $name) {
            $permission = Permission::findOrCreate($name, $guard);
            if ($permission->wasRecentlyCreated) {
                $newlyCreated[] = $name;
            }
        }

        $roleAssignments = [
            'accountant' => ['periods.view', 'periods.close'],
            'auditor' => ['periods.view'],
        ];

        $assignmentSummary = [];

        foreach ($roleAssignments as $roleName => $perms) {
            try {
                $role = Role::findByName($roleName, $guard);
            } catch (\Throwable) {
                $this->command?->info("Role \"{$roleName}\" not found (guard: {$guard}); skipping period permissions.");

                continue;
            }

            foreach ($perms as $perm) {
                $role->givePermissionTo($perm);
            }

            $assignmentSummary[$roleName] = $perms;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($newlyCreated !== []) {
            $this->command?->info('New permissions created: '.implode(', ', $newlyCreated));
        } else {
            $this->command?->info('No new permissions created (periods.view, periods.close already exist).');
        }

        foreach ($assignmentSummary as $roleName => $perms) {
            $this->command?->info("Role \"{$roleName}\" granted: ".implode(', ', $perms));
        }
    }
}
