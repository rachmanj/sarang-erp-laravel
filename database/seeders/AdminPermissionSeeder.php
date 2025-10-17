<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get the superadmin user
        $admin = User::where('username', 'superadmin')->first();

        if (!$admin) {
            $this->command->error('Superadmin user not found');
            return;
        }

        // Get all permissions
        $allPermissions = Permission::all();

        // Assign all permissions to superadmin
        $admin->givePermissionTo($allPermissions);

        $this->command->info('All permissions assigned to superadmin user');
    }
}
