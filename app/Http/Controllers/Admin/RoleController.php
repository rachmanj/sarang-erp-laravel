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
        $query = Role::query()->select(['id', 'name']);
        return DataTables::of($query)
            ->addColumn('permissions', function (Role $role) {
                return $role->permissions
                    ->pluck('name')
                    ->map(function ($name) {
                        return '<span class="badge badge-secondary mr-1">' . e($name) . '</span>';
                    })
                    ->join(' ');
            })
            ->addColumn('actions', function (Role $role) {
                $editUrl = route('admin.roles.edit', $role);
                $edit = '<a href="' . $editUrl . '" class="btn btn-xs btn-primary">Edit</a>';
                $del = '<button type="button" class="btn btn-xs btn-danger delete-role" data-id="' . $role->id . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['permissions', 'actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $this->authorize('roles.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['array']
        ]);
        $role = Role::create(['name' => $data['name']]);
        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
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
            'permissions' => ['array']
        ]);
        $role->name = $data['name'];
        $role->save();
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
        return redirect()->route('admin.roles.index')->with('success', 'Role updated');
    }

    public function destroy(Role $role)
    {
        $this->authorize('roles.delete');
        if ($role->name === 'admin') {
            return back()->with('error', 'Cannot delete admin role');
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
