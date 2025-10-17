<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users with different roles
        $officer = User::create([
            'name' => 'John Officer',
            'username' => 'officer',
            'email' => 'officer@example.com',
            'password' => Hash::make('password'),
        ]);

        $supervisor = User::create([
            'name' => 'Jane Supervisor',
            'username' => 'supervisor',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('password'),
        ]);

        $manager = User::create([
            'name' => 'Bob Manager',
            'username' => 'manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
        ]);

        // Assign roles
        UserRole::create([
            'user_id' => $officer->id,
            'role_name' => 'officer',
            'is_active' => true,
        ]);

        UserRole::create([
            'user_id' => $supervisor->id,
            'role_name' => 'supervisor',
            'is_active' => true,
        ]);

        UserRole::create([
            'user_id' => $manager->id,
            'role_name' => 'manager',
            'is_active' => true,
        ]);

        // Give supervisor and manager multiple roles for testing
        UserRole::create([
            'user_id' => $supervisor->id,
            'role_name' => 'officer',
            'is_active' => true,
        ]);

        UserRole::create([
            'user_id' => $manager->id,
            'role_name' => 'supervisor',
            'is_active' => true,
        ]);

        UserRole::create([
            'user_id' => $manager->id,
            'role_name' => 'officer',
            'is_active' => true,
        ]);
    }
}
