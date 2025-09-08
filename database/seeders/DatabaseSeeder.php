<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        $this->call([
            CoASeeder::class,
            TaxCodeSeeder::class,
            FundProjectSeeder::class,
            RolePermissionSeeder::class,
            DemoJournalSeeder::class,
        ]);
    }
}
