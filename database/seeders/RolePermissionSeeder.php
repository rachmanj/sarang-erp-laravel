<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view-admin',
            'accounts.view',
            'accounts.manage',
            'journals.view',
            'journals.create',
            'journals.post',
            'journals.reverse',
            'projects.view',
            'projects.manage',
            'funds.view',
            'funds.manage',
            'departments.view',
            'departments.manage',
            'customers.view',
            'customers.manage',
            'vendors.view',
            'vendors.manage',
            'taxcodes.view',
            'taxcodes.manage',
            // Admin RBAC management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'reports.view',
            'period.close',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        $roles = [
            'admin' => $permissions,
            'accountant' => [
                'accounts.view',
                'journals.view',
                'journals.create',
                'journals.post',
                'projects.view',
                'funds.view',
                'departments.view',
                'customers.view',
                'vendors.view',
                'taxcodes.view',
                'reports.view',
            ],
            'approver' => ['journals.post', 'reports.view'],
            'cashier' => ['journals.create', 'reports.view'],
            'auditor' => ['reports.view'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }

        if ($admin = User::first()) {
            $admin->assignRole('admin');
        }
    }
}
