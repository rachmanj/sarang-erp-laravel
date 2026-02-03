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
            // Business Partners (unified customers & suppliers)
            'business_partners.view',
            'business_partners.manage',
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
            // ERP Parameters
            'manage-erp-parameters',
            'manage-company-info',
            'reports.view',
            'reports.open-items',
            // AR/AP
            'ar.invoices.view',
            'ar.invoices.create',
            'ar.invoices.post',
            'ar.receipts.view',
            'ar.receipts.create',
            'ar.receipts.post',
            'ar.quotations.view',
            'ar.quotations.create',
            'ar.quotations.update',
            'ar.quotations.delete',
            'ar.quotations.approve',
            'ar.quotations.convert',
            'ap.invoices.view',
            'ap.invoices.create',
            'ap.invoices.post',
            'ap.payments.view',
            'ap.payments.create',
            'ap.payments.post',
            // Fixed Assets
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.delete',
            'asset_categories.view',
            'asset_categories.manage',
            'assets.depreciation.run',
            'assets.depreciation.reverse',
            'assets.disposal.view',
            'assets.disposal.create',
            'assets.disposal.update',
            'assets.disposal.delete',
            'assets.disposal.post',
            'assets.disposal.reverse',
            'assets.movement.view',
            'assets.movement.create',
            'assets.movement.update',
            'assets.movement.delete',
            'assets.movement.approve',
            'assets.reports.view',
            // Inventory permissions
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.reports',
            // Warehouse permissions
            'warehouse.view',
            'warehouse.create',
            'warehouse.update',
            'warehouse.delete',
            'warehouse.transfer',

            // GR/GI Management
            'gr-gi.view',
            'gr-gi.create',
            'gr-gi.update',
            'gr-gi.delete',
            'gr-gi.approve',
            // Purchase Orders
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.update',
            'purchase-orders.delete',
            'purchase-orders.approve',
            // Sales Orders
            'sales-orders.view',
            'sales-orders.create',
            'sales-orders.update',
            'sales-orders.delete',
            'sales-orders.approve',
            // Unit of Measure permissions
            'view_unit_of_measure',
            'create_unit_of_measure',
            'update_unit_of_measure',
            'delete_unit_of_measure',
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
                'manage-company-info',
                'reports.view',
                'reports.open-items',
                // AR/AP create/view
                'ar.invoices.view',
                'ar.invoices.create',
                'ap.invoices.view',
                'ap.invoices.create',
                'ar.receipts.view',
                'ar.receipts.create',
                'ar.quotations.view',
                'ar.quotations.create',
                'ar.quotations.update',
                'ar.quotations.convert',
                'ap.payments.view',
                'ap.payments.create',
                // Fixed Assets
                'assets.view',
                'asset_categories.view',
                'assets.reports.view',
            ],
            'approver' => [
                'reports.view',
                'reports.open-items',
                // Posting permissions
                'journals.post',
                'ar.invoices.post',
                'ap.invoices.post',
                'ar.receipts.post',
                'ar.quotations.view',
                'ar.quotations.approve',
                'ap.payments.post',
                // Fixed Assets posting
                'assets.depreciation.run',
                'assets.depreciation.reverse',
                'assets.disposal.view',
                'assets.disposal.create',
                'assets.disposal.update',
                'assets.disposal.delete',
                'assets.disposal.post',
                'assets.disposal.reverse',
                'assets.movement.view',
                'assets.movement.create',
                'assets.movement.update',
                'assets.movement.delete',
                'assets.movement.approve',
            ],
            'cashier' => [
                'reports.view',
                'journals.create',
                // Frontline cash operations (optional)
                'ar.receipts.create',
                'ap.payments.create',
            ],
            'auditor' => ['reports.view', 'reports.open-items'],
            'logistic' => [
                // Inventory management
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'inventory.transfer',
                'inventory.reports',
                // Warehouse management
                'warehouse.view',
                'warehouse.create',
                'warehouse.update',
                'warehouse.transfer',
                // GR/GI Management
                'gr-gi.view',
                'gr-gi.create',
                'gr-gi.update',
                'gr-gi.approve',
                // Purchase Orders (for tracking incoming goods)
                'purchase-orders.view',
                'purchase-orders.create',
                'purchase-orders.update',
                // Vendors (to view vendor information)
                'vendors.view',
                // Projects and departments (for cost tracking)
                'projects.view',
                'departments.view',
            ],
            'marketing' => [
                // Sales Orders
                'sales-orders.view',
                'sales-orders.create',
                'sales-orders.update',
                // Sales Quotations
                'ar.quotations.view',
                'ar.quotations.create',
                'ar.quotations.update',
                'ar.quotations.convert',
                // Customers
                'customers.view',
                'customers.manage',
                // Business Partners (vendors view for reference)
                'vendors.view',
                // Projects (for tracking sales by project)
                'projects.view',
                'departments.view',
                // Reports
                'reports.view',
            ],
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
