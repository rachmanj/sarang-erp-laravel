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
            // Periods
            'periods.view',
            'periods.close',
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
            // AR/AP
            'ar.invoices.view',
            'ar.invoices.create',
            'ar.invoices.post',
            'ar.receipts.view',
            'ar.receipts.create',
            'ar.receipts.post',
            'ap.invoices.view',
            'ap.invoices.create',
            'ap.invoices.post',
            'ap.payments.view',
            'ap.payments.create',
            'ap.payments.post',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm);
        }

        $roles = [
            'superadmin' => $permissions,
            'accountant' => [
                'accounts.view',
                'journals.view',
                'journals.create',
                'projects.view',
                'funds.view',
                'departments.view',
                'customers.view',
                'vendors.view',
                'taxcodes.view',
                'reports.view',
                // AR/AP create/view
                'ar.invoices.view',
                'ar.invoices.create',
                'ap.invoices.view',
                'ap.invoices.create',
                'ar.receipts.view',
                'ar.receipts.create',
                'ap.payments.view',
                'ap.payments.create',
            ],
            'approver' => [
                'reports.view',
                // Posting permissions
                'journals.post',
                'ar.invoices.post',
                'ap.invoices.post',
                'ar.receipts.post',
                'ap.payments.post',
            ],
            'cashier' => [
                'reports.view',
                'journals.create',
                // Frontline cash operations (optional)
                'ar.receipts.create',
                'ap.payments.create',
            ],
            'auditor' => ['reports.view'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }

        if ($admin = User::first()) {
            $admin->assignRole('superadmin');
        }
    }
}
