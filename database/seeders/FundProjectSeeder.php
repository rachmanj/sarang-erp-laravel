<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dimensions\Project;

class FundProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create projects without fund references (funds dimension removed)
        Project::updateOrCreate(
            ['code' => 'PRJ-OPS'],
            [
                'name' => 'Operasional Yayasan',
                'budget_total' => 0,
                'status' => 'active',
            ]
        );

        Project::updateOrCreate(
            ['code' => 'PRJ-GURU-2025'],
            [
                'name' => 'Pelatihan Guru 2025',
                'budget_total' => 200000000,
                'status' => 'active',
            ]
        );

        Project::updateOrCreate(
            ['code' => 'PRJ-TRADING'],
            [
                'name' => 'Trading Operations',
                'budget_total' => 0,
                'status' => 'active',
            ]
        );
    }
}
