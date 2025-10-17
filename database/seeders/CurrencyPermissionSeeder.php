<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CurrencyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create currency permissions
        $permissions = [
            'currencies.view',
            'currencies.create',
            'currencies.update',
            'currencies.delete',
            'exchange-rates.view',
            'exchange-rates.create',
            'exchange-rates.update',
            'exchange-rates.delete',
            'currency-revaluations.view',
            'currency-revaluations.create',
            'currency-revaluations.post',
            'currency-revaluations.reverse',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create or get admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Assign all permissions to admin role
        $adminRole->givePermissionTo($permissions);

        // Assign admin role to manager user
        $manager = User::where('username', 'manager')->first();
        if ($manager) {
            $manager->assignRole('admin');
            $this->command->info('Manager user assigned admin role with currency permissions');
        }
    }
}
