<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationEntry;
use App\Models\AssetDepreciationRun;
use App\Models\AssetDisposal;
use App\Models\AssetMovement;
use App\Models\BusinessPartner;
use App\Models\CompanyEntity;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetReportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create([
            'email' => 'asset-demo@example.com',
            'username' => 'assetdemo',
        ]);

        $entityId = CompanyEntity::query()->value('id') ?? 1;
        $vendorId = BusinessPartner::query()->value('id');

        $categories = AssetCategory::query()->get()->keyBy('code');
        if ($categories->isEmpty()) {
            $this->call(AssetCategorySeeder::class);
            $categories = AssetCategory::query()->get()->keyBy('code');
        }

        $samples = [
            [
                'code' => 'RPT-IT-001',
                'name' => 'Dell PowerEdge Server',
                'category' => 'IT_EQUIPMENT',
                'cost' => 25000000,
                'life' => 36,
                'months_ago' => 6,
                'accum_ratio' => 0.15,
            ],
            [
                'code' => 'RPT-FUR-001',
                'name' => 'Premium Office Chair',
                'category' => 'FURNITURE',
                'cost' => 2500000,
                'life' => 36,
                'months_ago' => 3,
                'accum_ratio' => 0.08,
            ],
            [
                'code' => 'RPT-VEH-001',
                'name' => 'Toyota Avanza 2023',
                'category' => 'VEHICLES',
                'cost' => 200000000,
                'life' => 60,
                'months_ago' => 12,
                'accum_ratio' => 0.20,
            ],
            [
                'code' => 'RPT-EQ-001',
                'name' => 'Warehouse Forklift',
                'category' => 'EQUIPMENT',
                'cost' => 85000000,
                'life' => 48,
                'months_ago' => 18,
                'accum_ratio' => 0.35,
            ],
            [
                'code' => 'RPT-BLD-001',
                'name' => 'Office Building Wing A',
                'category' => 'BUILDINGS',
                'cost' => 5000000000,
                'life' => 240,
                'months_ago' => 60,
                'accum_ratio' => 0.25,
            ],
            [
                'code' => 'RPT-LND-001',
                'name' => 'Land Office Complex',
                'category' => 'LAND',
                'cost' => 2000000000,
                'life' => 0,
                'months_ago' => 36,
                'accum_ratio' => 0,
            ],
            [
                'code' => 'RPT-IT-002',
                'name' => 'Low Value Tablet',
                'category' => 'IT_EQUIPMENT',
                'cost' => 750000,
                'life' => 36,
                'months_ago' => 2,
                'accum_ratio' => 0.05,
            ],
            [
                'code' => 'RPT-FUR-002',
                'name' => 'Disposed Desk Set',
                'category' => 'FURNITURE',
                'cost' => 8000000,
                'life' => 36,
                'months_ago' => 24,
                'accum_ratio' => 0.60,
                'disposed' => true,
            ],
        ];

        $assets = [];
        foreach ($samples as $sample) {
            $category = $categories[$sample['category']] ?? $categories->first();
            $salvage = round($sample['cost'] * 0.1, 2);
            $accum = round(($sample['cost'] - $salvage) * $sample['accum_ratio'], 2);
            $isDisposed = ! empty($sample['disposed']);

            $assets[$sample['code']] = Asset::query()->updateOrCreate(
                ['code' => $sample['code']],
                [
                    'name' => $sample['name'],
                    'description' => 'Demo asset for report testing',
                    'serial_number' => 'SN-'.$sample['code'],
                    'category_id' => $category->id,
                    'acquisition_cost' => $sample['cost'],
                    'salvage_value' => $category->non_depreciable ? $sample['cost'] : $salvage,
                    'accumulated_depreciation' => $accum,
                    'current_book_value' => $sample['cost'] - $accum,
                    'method' => 'straight_line',
                    'life_months' => $sample['life'],
                    'placed_in_service_date' => now()->subMonths($sample['months_ago'])->startOfMonth()->toDateString(),
                    'status' => $isDisposed ? 'disposed' : 'active',
                    'disposal_date' => $isDisposed ? now()->subDays(20)->toDateString() : null,
                    'business_partner_id' => $vendorId,
                ]
            );
        }

        $period = now()->subMonth()->format('Y-m');
        $run = AssetDepreciationRun::query()->updateOrCreate(
            ['period' => $period],
            [
                'status' => 'posted',
                'total_depreciation' => 0,
                'asset_count' => 0,
                'created_by' => $user->id,
                'posted_by' => $user->id,
                'posted_at' => now()->subMonth()->endOfMonth(),
                'notes' => 'Demo depreciation run for asset reports',
            ]
        );

        $totalDep = 0;
        $assetCount = 0;
        foreach ($assets as $asset) {
            if ($asset->status !== 'active' || ! $asset->life_months || $asset->life_months <= 0) {
                continue;
            }

            $category = $asset->category;
            if ($category && $category->non_depreciable) {
                continue;
            }

            $amount = round(($asset->acquisition_cost - $asset->salvage_value) / $asset->life_months, 2);
            AssetDepreciationEntry::query()->updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'period' => $period,
                    'book' => 'financial',
                ],
                [
                    'amount' => $amount,
                    'journal_id' => null,
                ]
            );
            $totalDep += $amount;
            $assetCount++;
        }

        $run->update([
            'total_depreciation' => $totalDep,
            'asset_count' => $assetCount,
        ]);

        $disposedAsset = $assets['RPT-FUR-002'] ?? null;
        if ($disposedAsset) {
            AssetDisposal::query()->updateOrCreate(
                ['disposal_no' => 'DSP-RPT-001'],
                [
                    'asset_id' => $disposedAsset->id,
                    'company_entity_id' => $entityId,
                    'disposal_date' => $disposedAsset->disposal_date,
                    'disposal_type' => 'sale',
                    'disposal_proceeds' => 2500000,
                    'book_value_at_disposal' => $disposedAsset->current_book_value,
                    'gain_loss_amount' => abs(2500000 - $disposedAsset->current_book_value),
                    'gain_loss_type' => $disposedAsset->current_book_value <= 2500000 ? 'gain' : 'loss',
                    'disposal_reason' => 'Demo disposal for reports',
                    'created_by' => $user->id,
                    'posted_by' => $user->id,
                    'posted_at' => now()->subDays(19),
                    'status' => 'posted',
                ]
            );
        }

        $movingAsset = $assets['RPT-IT-001'] ?? null;
        if ($movingAsset) {
            AssetMovement::query()->updateOrCreate(
                ['reference_number' => 'MOV-RPT-001'],
                [
                    'asset_id' => $movingAsset->id,
                    'movement_date' => now()->subDays(15)->toDateString(),
                    'movement_type' => 'relocation',
                    'from_location' => 'Server Room A',
                    'to_location' => 'Server Room B',
                    'from_custodian' => 'IT Admin',
                    'to_custodian' => 'Network Lead',
                    'movement_reason' => 'Demo movement for reports',
                    'created_by' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now()->subDays(14),
                    'status' => 'completed',
                ]
            );
        }

        $this->command?->info('Asset report demo data seeded ('.count($assets).' assets).');
    }
}
