<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dimensions\Fund;
use App\Models\Dimensions\Project;

class FundProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unrestricted = Fund::updateOrCreate(
            ['code' => 'F-UNRESTRICTED'],
            ['name' => 'Dana Tidak Terikat', 'is_restricted' => false]
        );

        $restricted = Fund::updateOrCreate(
            ['code' => 'F-RESTRICTED'],
            ['name' => 'Dana Terikat', 'is_restricted' => true]
        );

        Project::updateOrCreate(
            ['code' => 'PRJ-OPS'],
            [
                'name' => 'Operasional Yayasan',
                'fund_id' => $unrestricted->id,
                'budget_total' => 0,
                'status' => 'active',
            ]
        );

        Project::updateOrCreate(
            ['code' => 'PRJ-GURU-2025'],
            [
                'name' => 'Pelatihan Guru 2025',
                'fund_id' => $restricted->id,
                'budget_total' => 200000000,
                'status' => 'active',
            ]
        );
    }
}
