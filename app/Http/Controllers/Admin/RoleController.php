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
        return view('admin.roles.create', compact('permissions'));
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
        return view('admin.roles.edit', compact('role', 'permissions'));
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
}
