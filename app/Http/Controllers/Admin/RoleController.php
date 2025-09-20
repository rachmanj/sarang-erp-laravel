<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('roles.view');
        $permissions = Permission::orderBy('name')->pluck('name');
        return view('admin.roles.index', compact('permissions'));
    }

    public function create()
    {
        $this->authorize('roles.create');
        $permissions = Permission::orderBy('name')->get(['id', 'name']);
        $groupedPermissions = $this->groupPermissionsByFeature($permissions);
        return view('admin.roles.create', compact('permissions', 'groupedPermissions'));
    }

    public function data(Request $request)
    {
        $this->authorize('roles.view');
        $query = Role::query()->withCount('users')->select(['id', 'name']);
        return DataTables::of($query)
            ->addColumn('permissions', function (Role $role) {
                $permissions = $role->permissions->pluck('name');
                if ($permissions->isEmpty()) {
                    return '<span class="text-muted">No permissions</span>';
                }

                // Show first 3 permissions as badges
                $displayPermissions = $permissions->take(3);
                $html = $displayPermissions->map(function ($name) {
                    return '<span class="badge badge-info mr-1 mb-1">' . e($name) . '</span>';
                })->join('');

                // Add "+X more" if there are more than 3 permissions
                if ($permissions->count() > 3) {
                    $remaining = $permissions->count() - 3;
                    $html .= '<span class="badge badge-secondary">+' . $remaining . ' more</span>';
                }

                return $html;
            })
            ->addColumn('users_count', function (Role $role) {
                return $role->users_count;
            })
            ->addColumn('actions', function (Role $role) {
                $html = '';

                // View button
                $html .= '<button class="btn btn-sm btn-info mr-1 view-role" data-id="' . $role->id . '" title="View"><i class="fas fa-eye"></i></button>';

                // Edit button
                $editUrl = route('admin.roles.edit', $role);
                $html .= '<a href="' . $editUrl . '" class="btn btn-sm btn-warning mr-1" title="Edit"><i class="fas fa-edit"></i></a>';

                // Delete button (not for superadmin)
                if ($role->name !== 'superadmin') {
                    $html .= '<button type="button" class="btn btn-sm btn-danger delete-role" data-id="' . $role->id . '" title="Delete"><i class="fas fa-trash"></i></button>';
                }

                return $html;
            })
            ->rawColumns(['permissions', 'actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $this->authorize('roles.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id']
        ]);
        $role = Role::create(['name' => $data['name']]);
        if (!empty($data['permissions'])) {
            $perms = collect($data['permissions'])->map(fn($id) => (int) $id)->all();
            $role->syncPermissions($perms);
        }
        return redirect()->route('admin.roles.index')->with('success', 'Role created');
    }

    public function edit(Role $role)
    {
        $this->authorize('roles.update');
        $permissions = Permission::orderBy('name')->get(['id', 'name']);
        $groupedPermissions = $this->groupPermissionsByFeature($permissions);
        return view('admin.roles.edit', compact('role', 'permissions', 'groupedPermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('roles.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id']
        ]);
        $role->name = $data['name'];
        $role->save();
        if (isset($data['permissions'])) {
            $perms = collect($data['permissions'])->map(fn($id) => (int) $id)->all();
            $role->syncPermissions($perms);
        }
        return redirect()->route('admin.roles.index')->with('success', 'Role updated');
    }

    public function destroy(Role $role)
    {
        $this->authorize('roles.delete');
        if ($role->name === 'superadmin') {
            return back()->with('error', 'Cannot delete superadmin role');
        }
        $role->delete();
        return back()->with('success', 'Role deleted');
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $this->authorize('roles.assign');
        $perms = $request->input('permissions', []);
        $role->syncPermissions($perms);
        return back()->with('success', 'Permissions updated');
    }

    /**
     * Group permissions by feature/module for better organization
     */
    private function groupPermissionsByFeature($permissions)
    {
        $groups = [
            'System Administration' => [
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
                'view-admin',
                'manage-erp-parameters'
            ],
            'Master Data Management' => [
                'customers.view',
                'customers.manage',
                'vendors.view',
                'vendors.manage',
                'accounts.view',
                'accounts.manage',
                'taxcodes.view',
                'taxcodes.manage',
                'departments.view',
                'departments.manage',
                'projects.view',
                'projects.manage',
                'funds.view',
                'funds.manage',
                'asset_categories.view',
                'asset_categories.manage'
            ],
            'Inventory Management' => [
                'inventory.view',
                'inventory.create',
                'inventory.update',
                'inventory.delete',
                'inventory.adjust',
                'inventory.transfer',
                'inventory.reports'
            ],
            'Fixed Asset Management' => [
                'assets.view',
                'assets.create',
                'assets.update',
                'assets.delete',
                'assets.movement.view',
                'assets.movement.create',
                'assets.movement.update',
                'assets.movement.delete',
                'assets.movement.approve',
                'assets.disposal.view',
                'assets.disposal.create',
                'assets.disposal.update',
                'assets.disposal.delete',
                'assets.disposal.post',
                'assets.disposal.reverse',
                'assets.depreciation.run',
                'assets.depreciation.reverse',
                'assets.reports.view'
            ],
            'Purchase Management' => [
                'ap.invoices.view',
                'ap.invoices.create',
                'ap.invoices.post',
                'ap.payments.view',
                'ap.payments.create',
                'ap.payments.post'
            ],
            'Sales Management' => [
                'ar.invoices.view',
                'ar.invoices.create',
                'ar.invoices.post',
                'ar.receipts.view',
                'ar.receipts.create',
                'ar.receipts.post'
            ],
            'Accounting & Finance' => [
                'journals.view',
                'journals.create',
                'journals.post',
                'journals.reverse',
                'periods.view',
                'periods.close',
                'account_statements.view',
                'account_statements.create',
                'account_statements.update',
                'account_statements.delete'
            ],
            'Reports & Analytics' => [
                'reports.view',
                'reports.open-items'
            ]
        ];

        $groupedPermissions = [];

        foreach ($groups as $groupName => $permissionNames) {
            $groupPermissions = $permissions->filter(function ($permission) use ($permissionNames) {
                return in_array($permission->name, $permissionNames);
            });

            if ($groupPermissions->isNotEmpty()) {
                $groupedPermissions[$groupName] = $groupPermissions;
            }
        }

        // Add any remaining permissions that don't fit into the defined groups
        $assignedPermissions = collect($groups)->flatten()->toArray();
        $remainingPermissions = $permissions->filter(function ($permission) use ($assignedPermissions) {
            return !in_array($permission->name, $assignedPermissions);
        });

        if ($remainingPermissions->isNotEmpty()) {
            $groupedPermissions['Other Permissions'] = $remainingPermissions;
        }

        return $groupedPermissions;
    }
}
