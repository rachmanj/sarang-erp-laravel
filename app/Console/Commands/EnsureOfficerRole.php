<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

class EnsureOfficerRole extends Command
{
    protected $signature = 'role:ensure-officer 
                            {--user= : Specific username to assign officer role to}
                            {--all-admins : Assign officer role to all admin users}
                            {--list : List all users with officer role}
                            {--create-spatie : Create officer role in Spatie Permission system}';
    
    protected $description = 'Ensure the officer role exists and is assigned to at least one user for approvals';

    public function handle()
    {
        // Ensure Spatie Permission role exists
        $this->ensureSpatieRole();
        
        if ($this->option('list')) {
            return $this->listOfficerUsers();
        }
        
        $usersWithOfficerRole = UserRole::where('role_name', 'officer')
            ->where('is_active', true)
            ->count();
            
        $this->info("Current users with 'officer' role: {$usersWithOfficerRole}");
        
        if ($usersWithOfficerRole === 0) {
            $this->warn("⚠ No users have the 'officer' role. This will prevent Sales Order approvals.");
            
            if (!$this->option('user') && !$this->option('all-admins')) {
                $this->info("\nAssigning 'officer' role to superadmin (user_id=1) by default...");
                $this->assignOfficerRole(1);
            }
        }
        
        if ($this->option('user')) {
            $username = $this->option('user');
            $user = User::where('username', $username)->first();
            
            if (!$user) {
                $this->error("User '{$username}' not found");
                return 1;
            }
            
            $this->assignOfficerRole($user->id);
        }
        
        if ($this->option('all-admins')) {
            $adminUsers = User::where('username', 'like', '%admin%')
                ->orWhere('name', 'like', '%admin%')
                ->get();
                
            if ($adminUsers->isEmpty()) {
                $this->warn("No admin users found. Assigning to superadmin instead...");
                $this->assignOfficerRole(1);
            } else {
                foreach ($adminUsers as $user) {
                    $this->assignOfficerRole($user->id);
                }
            }
        }
        
        // Show final status
        $this->listOfficerUsers();
        
        return 0;
    }
    
    private function ensureSpatieRole()
    {
        $spatieRole = SpatieRole::where('name', 'officer')->first();
        
        if (!$spatieRole) {
            $this->info("Creating 'officer' role in Spatie Permission system...");
            SpatieRole::create([
                'name' => 'officer',
                'guard_name' => 'web',
            ]);
            $this->info("  ✓ Created 'officer' role in Spatie Permission system");
        } else {
            if ($this->option('create-spatie')) {
                $this->info("  ✓ 'officer' role already exists in Spatie Permission system");
            }
        }
    }
    
    private function assignOfficerRole(int $userId)
    {
        $user = User::findOrFail($userId);
        
        $existingRole = UserRole::where('user_id', $userId)
            ->where('role_name', 'officer')
            ->first();
            
        if ($existingRole) {
            if ($existingRole->is_active) {
                $this->info("  ✓ {$user->username} ({$user->name}) already has 'officer' role");
            } else {
                $existingRole->update(['is_active' => true]);
                $this->info("  ✓ Activated 'officer' role for {$user->username} ({$user->name})");
            }
        } else {
            UserRole::create([
                'user_id' => $userId,
                'role_name' => 'officer',
                'is_active' => true,
            ]);
            $this->info("  ✓ Assigned 'officer' role to {$user->username} ({$user->name})");
        }
    }
    
    private function listOfficerUsers()
    {
        $officers = UserRole::where('role_name', 'officer')
            ->where('is_active', true)
            ->with('user')
            ->get();
            
        if ($officers->isEmpty()) {
            $this->warn("⚠ No users have the 'officer' role assigned!");
            $this->line("Run: php artisan role:ensure-officer --user=superadmin");
            return 1;
        }
        
        $this->info("\nUsers with 'officer' role:");
        $this->table(
            ['ID', 'Username', 'Name', 'Status'],
            $officers->map(function ($role) {
                return [
                    $role->user_id,
                    $role->user->username,
                    $role->user->name,
                    $role->is_active ? '✓ Active' : '✗ Inactive',
                ];
            })->toArray()
        );
        
        return 0;
    }
}
