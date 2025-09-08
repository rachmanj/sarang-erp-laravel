<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Dimensions\Project;
use App\Models\Dimensions\Department;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('users.view');
        $roles = Role::orderBy('name')->get(['id', 'name']);
        return view('admin.users.index', compact('roles'));
    }

    public function create()
    {
        $this->authorize('users.create');
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $projects = Project::query()->orderBy('code')->get(['code', 'owner']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        return view('admin.users.create', compact('roles', 'projects', 'departments'));
    }

    public function data(Request $request)
    {
        $this->authorize('users.view');
        $query = User::query()->select(['id', 'name', 'email', 'created_at']);
        return DataTables::of($query)
            ->addColumn('roles', function (User $user) {
                return e($user->getRoleNames()->join(', '));
            })
            ->addColumn('actions', function (User $user) {
                $editUrl = route('admin.users.edit', $user);
                $edit = '<a href="' . $editUrl . '" class="btn btn-xs btn-primary">Edit</a>';
                $del = '<button type="button" class="btn btn-xs btn-danger delete-user" data-id="' . $user->id . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        $this->authorize('users.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'roles' => ['array']
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        $this->authorize('users.update');
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $projects = Project::query()->orderBy('code')->get(['code', 'owner']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        return view('admin.users.edit', compact('user', 'roles', 'projects', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('users.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'roles' => ['array']
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
        return redirect()->route('admin.users.index')->with('success', 'User updated');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('users.delete');
        if ($request->user() && $request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete yourself');
        }
        // prevent removing last admin
        if ($user->hasRole('admin') && Role::where('name', 'admin')->first()?->users()->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin');
        }
        $user->delete();
        return back()->with('success', 'User deleted');
    }

    public function syncRoles(Request $request, User $user)
    {
        $this->authorize('users.assign');
        $roles = $request->input('roles', []);
        $user->syncRoles($roles);
        return back()->with('success', 'Roles updated');
    }
}
