<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\AssetCategory;

class TrainingAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get asset categories
        $categories = AssetCategory::all()->keyBy('code');

        // Create sample assets for training scenarios
        $assets = [
            [
                'code' => 'AST-001',
                'name' => 'Office Server Dell PowerEdge',
                'description' => 'Dell PowerEdge R740 Server with Intel Xeon processors',
                'category_id' => $categories['IT_EQUIPMENT']->id,
                'acquisition_date' => now()->subMonths(6),
                'acquisition_cost' => 25000000,
                'useful_life_months' => 36,
                'depreciation_method' => 'straight_line',
                'salvage_value' => 2500000,
                'location' => 'Server Room',
                'status' => 'in_use',
            ],
            [
                'code' => 'AST-002',
                'name' => 'Office Chair Premium Model A',
                'description' => 'Premium office chair with leather upholstery',
                'category_id' => $categories['FURNITURE']->id,
                'acquisition_date' => now()->subMonths(3),
                'acquisition_cost' => 2500000,
                'useful_life_months' => 36,
                'depreciation_method' => 'straight_line',
                'salvage_value' => 250000,
                'location' => 'Office Floor 1',
                'status' => 'in_use',
            ],
            [
                'code' => 'AST-003',
                'name' => 'Executive Desk Mahogany',
                'description' => 'Executive desk made from mahogany wood',
                'category_id' => $categories['FURNITURE']->id,
                'acquisition_date' => now()->subMonths(2),
                'acquisition_cost' => 10000000,
                'useful_life_months' => 36,
                'depreciation_method' => 'straight_line',
                'salvage_value' => 1000000,
                'location' => 'CEO Office',
                'status' => 'in_use',
            ],
            [
                'code' => 'AST-004',
                'name' => 'Company Vehicle Toyota Avanza',
                'description' => 'Toyota Avanza 2023 for company transportation',
                'category_id' => $categories['VEHICLES']->id,
                'acquisition_date' => now()->subMonths(12),
                'acquisition_cost' => 200000000,
                'useful_life_months' => 60,
                'depreciation_method' => 'straight_line',
                'salvage_value' => 20000000,
                'location' => 'Company Garage',
                'status' => 'in_use',
            ],
            [
                'code' => 'AST-005',
                'name' => 'Office Building',
                'description' => '3-story office building in Jakarta',
                'category_id' => $categories['BUILDINGS']->id,
                'acquisition_date' => now()->subYears(5),
                'acquisition_cost' => 5000000000,
                'useful_life_months' => 240,
                'depreciation_method' => 'straight_line',
                'salvage_value' => 500000000,
                'location' => 'Jl. Sudirman No. 100, Jakarta',
                'status' => 'in_use',
            ],
            [
                'code' => 'AST-006',
                'name' => 'Land Office Complex',
                'description' => 'Land for office complex development',
                'category_id' => $categories['LAND']->id,
                'acquisition_date' => now()->subYears(3),
                'acquisition_cost' => 2000000000,
                'useful_life_months' => null, // Land is non-depreciable
                'depreciation_method' => 'straight_line',
                'salvage_value' => 2000000000, // Land typically doesn't depreciate
                'location' => 'Jl. Sudirman No. 100, Jakarta',
                'status' => 'in_use',
            ],
        ];

        foreach ($assets as $assetData) {
            Asset::updateOrCreate(
                ['code' => $assetData['code']],
                $assetData
            );
        }
    }
}
