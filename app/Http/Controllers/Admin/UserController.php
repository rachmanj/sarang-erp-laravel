<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
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
        $projects = Project::query()->orderBy('code')->get(['code', 'name']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        return view('admin.users.create', compact('roles', 'projects', 'departments'));
    }

    public function data(Request $request)
    {
        $this->authorize('users.view');
        
        $sessionLifetime = config('session.lifetime', 120);
        $sessionTable = config('session.table', 'sessions');
        $onlineThreshold = now()->subMinutes($sessionLifetime)->timestamp;
        
        // Get latest session data per user using subquery selects
        $query = User::query()
            ->select([
                'users.id',
                'users.name', 
                'users.email', 
                'users.created_at',
                DB::raw('(SELECT MAX(last_activity) FROM ' . $sessionTable . ' WHERE user_id = users.id AND user_id IS NOT NULL) as last_activity'),
                DB::raw('(SELECT ip_address FROM ' . $sessionTable . ' s2 
                          WHERE s2.user_id = users.id 
                          AND s2.user_id IS NOT NULL 
                          AND s2.last_activity = (SELECT MAX(last_activity) FROM ' . $sessionTable . ' WHERE user_id = users.id) 
                          LIMIT 1) as ip_address')
            ]);
        
        // Filter by online status if requested
        if ($request->has('online_status') && $request->online_status !== '') {
            if ($request->online_status === 'online') {
                $query->whereRaw('(SELECT MAX(last_activity) FROM ' . $sessionTable . ' WHERE user_id = users.id AND user_id IS NOT NULL) >= ?', [$onlineThreshold]);
            } elseif ($request->online_status === 'offline') {
                $query->where(function($q) use ($sessionTable, $onlineThreshold) {
                    $q->whereRaw('(SELECT MAX(last_activity) FROM ' . $sessionTable . ' WHERE user_id = users.id AND user_id IS NOT NULL) IS NULL')
                      ->orWhereRaw('(SELECT MAX(last_activity) FROM ' . $sessionTable . ' WHERE user_id = users.id AND user_id IS NOT NULL) < ?', [$onlineThreshold]);
                });
            }
        }
        
        return DataTables::of($query)
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)
                    ->setTimezone(config('app.timezone'))
                    ->format('d-M-Y H:i');
            })
            ->addColumn('roles', function ($row) {
                $user = User::find($row->id);
                return e($user ? $user->getRoleNames()->join(', ') : '');
            })
            ->addColumn('online_status', function ($row) use ($onlineThreshold) {
                $lastActivity = $row->last_activity ?? 0;
                $isOnline = $lastActivity >= $onlineThreshold;
                
                if ($isOnline) {
                    $badge = '<span class="badge badge-success">Online</span>';
                    $tooltip = !empty($row->ip_address) ? ' title="IP: ' . e($row->ip_address) . '"' : '';
                    return '<span' . $tooltip . '>' . $badge . '</span>';
                } else {
                    return '<span class="badge badge-secondary">Offline</span>';
                }
            })
            ->addColumn('last_activity', function ($row) use ($onlineThreshold) {
                $lastActivity = $row->last_activity ?? null;
                
                if (!$lastActivity) {
                    return '<span class="text-muted">Never</span>';
                }
                
                $lastActivityTime = Carbon::createFromTimestamp($lastActivity)
                    ->setTimezone(config('app.timezone'));
                
                $diffInMinutes = now()->diffInMinutes($lastActivityTime);
                
                if ($lastActivity >= $onlineThreshold) {
                    // User is online
                    if ($diffInMinutes < 1) {
                        return '<span class="text-success">Just now</span>';
                    } elseif ($diffInMinutes < 60) {
                        return '<span class="text-success">' . $diffInMinutes . ' min ago</span>';
                    } else {
                        $diffInHours = floor($diffInMinutes / 60);
                        return '<span class="text-success">' . $diffInHours . ' hour' . ($diffInHours > 1 ? 's' : '') . ' ago</span>';
                    }
                } else {
                    // User is offline
                    return '<span class="text-muted">' . $lastActivityTime->format('d-M-Y H:i') . '</span>';
                }
            })
            ->addColumn('actions', function ($row) {
                $user = User::find($row->id);
                if (!$user) return '';
                
                $editUrl = route('admin.users.edit', $user);
                $edit = '<a href="' . $editUrl . '" class="btn btn-xs btn-primary">Edit</a>';
                $del = '<button type="button" class="btn btn-xs btn-danger delete-user" data-id="' . $user->id . '">Delete</button>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['online_status', 'last_activity', 'actions'])
            ->filterColumn('online_status', function($query, $keyword) use ($onlineThreshold) {
                if ($keyword === 'online') {
                    $query->where('sessions.last_activity', '>=', $onlineThreshold);
                } elseif ($keyword === 'offline') {
                    $query->where(function($q) use ($onlineThreshold) {
                        $q->whereNull('sessions.last_activity')
                          ->orWhere('sessions.last_activity', '<', $onlineThreshold);
                    });
                }
            })
            ->toJson();
    }

    public function store(Request $request)
    {
        $this->authorize('users.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:150', 'unique:users,username'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['array']
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (!empty($data['roles'])) {
            $roleNames = Role::whereIn('id', $data['roles'])->pluck('name')->toArray();
            $user->syncRoles($roleNames);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        $this->authorize('users.update');
        $roles = Role::orderBy('name')->get(['id', 'name']);
        $projects = Project::query()->orderBy('code')->get(['code', 'name']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        
        // Get approval roles for this user
        try {
            $approvalRoles = \App\Models\UserRole::where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('role_name')
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('Error loading approval roles: ' . $e->getMessage());
            $approvalRoles = [];
        }
        
        return view('admin.users.edit', compact('user', 'roles', 'projects', 'departments', 'approvalRoles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('users.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:150', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'approval_roles' => ['array'],
            'approval_roles.*' => ['in:officer,supervisor,manager']
        ]);
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->is_active = $data['is_active'] ?? true;
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        if (isset($data['roles'])) {
            $roleNames = Role::whereIn('id', $data['roles'])->pluck('name')->toArray();
            $user->syncRoles($roleNames);
        } else {
            $user->syncRoles([]);
        }
        
        // Sync approval roles (officer, supervisor, manager)
        if (isset($data['approval_roles'])) {
            $approvalRoleNames = $data['approval_roles'];
            // Deactivate all existing approval roles
            \App\Models\UserRole::where('user_id', $user->id)->update(['is_active' => false]);
            
            // Create/activate selected approval roles
            foreach ($approvalRoleNames as $roleName) {
                \App\Models\UserRole::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'role_name' => $roleName,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        } else {
            // If no approval roles selected, deactivate all
            \App\Models\UserRole::where('user_id', $user->id)->update(['is_active' => false]);
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
