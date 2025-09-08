<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index()
    {
        $this->authorize('permissions.view');
        return view('admin.permissions.index');
    }

    public function data()
    {
        $this->authorize('permissions.view');
        return DataTables::of(Permission::query()->select(['id', 'name']))
            ->addColumn('actions', function (Permission $p) {
                $edit = '<button class="btn btn-xs btn-primary edit-perm" data-id="' . $p->id . '">Edit</button>';
                $del = '<button class="btn btn-xs btn-danger delete-perm" data-id="' . $p->id . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $this->authorize('permissions.create');
        $data = $request->validate(['name' => ['required', 'string', 'max:150', 'unique:permissions,name']]);
        Permission::create(['name' => $data['name']]);
        return back()->with('success', 'Permission created');
    }

    public function update(Request $request, Permission $permission)
    {
        $this->authorize('permissions.update');
        $data = $request->validate(['name' => ['required', 'string', 'max:150', 'unique:permissions,name,' . $permission->id]]);
        $permission->name = $data['name'];
        $permission->save();
        return back()->with('success', 'Permission updated');
    }

    public function destroy(Permission $permission)
    {
        $this->authorize('permissions.delete');
        $permission->delete();
        return back()->with('success', 'Permission deleted');
    }
}
