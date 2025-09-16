<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AssetCategory;
use App\Models\Accounting\Account;

class AssetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get account IDs
        $accounts = Account::whereIn('code', [
            '1.2.1.01',
            '1.2.1.02',
            '1.2.1.03',
            '1.2.1.05',
            '1.2.1.07',
            '6.2.9',
            '7.1.1',
            '7.2.3'
        ])->get()->keyBy('code');

        $assetCategories = [
            [
                'code' => 'LAND',
                'name' => 'Land',
                'description' => 'Land and land improvements (non-depreciable)',
                'life_months_default' => null,
                'method_default' => 'straight_line',
                'salvage_value_policy' => 0,
                'non_depreciable' => true,
                'asset_account_id' => $accounts['1.2.1.01']->id, // Tanah
                'accumulated_depreciation_account_id' => $accounts['1.2.1.03']->id, // Akumulasi Penyusutan Bangunan
                'depreciation_expense_account_id' => $accounts['6.2.9']->id, // Biaya Penyusutan
                'gain_on_disposal_account_id' => $accounts['7.1.1']->id, // Pendapatan Sewa
                'loss_on_disposal_account_id' => $accounts['7.2.3']->id, // Kerugian Penjualan Aset
                'is_active' => true,
            ],
            [
                'code' => 'BUILDINGS',
                'name' => 'Buildings',
                'description' => 'Buildings and building improvements',
                'life_months_default' => 240, // 20 years
                'method_default' => 'straight_line',
                'salvage_value_policy' => 0,
                'non_depreciable' => false,
                'asset_account_id' => $accounts['1.2.1.02']->id, // Bangunan
                'accumulated_depreciation_account_id' => $accounts['1.2.1.03']->id, // Akumulasi Penyusutan Bangunan
                'depreciation_expense_account_id' => $accounts['6.2.9']->id, // Biaya Penyusutan
                'gain_on_disposal_account_id' => $accounts['7.1.1']->id, // Pendapatan Sewa
                'loss_on_disposal_account_id' => $accounts['7.2.3']->id, // Kerugian Penjualan Aset
                'is_active' => true,
            ],
            [
                'code' => 'VEHICLES',
                'name' => 'Vehicles',
                'description' => 'Motor vehicles and transportation equipment',
                'life_months_default' => 60, // 5 years
                'method_default' => 'straight_line',
                'salvage_value_policy' => 0,
                'non_depreciable' => false,
                'asset_account_id' => $accounts['1.2.1.04']->id, // Kendaraan
                'accumulated_depreciation_account_id' => $accounts['1.2.1.05']->id, // Akumulasi Penyusutan Kendaraan
                'depreciation_expense_account_id' => $accounts['6.2.9']->id, // Biaya Penyusutan
                'gain_on_disposal_account_id' => $accounts['7.1.1']->id, // Pendapatan Sewa
                'loss_on_disposal_account_id' => $accounts['7.2.3']->id, // Kerugian Penjualan Aset
                'is_active' => true,
            ],
            [
                'code' => 'EQUIPMENT',
                'name' => 'Equipment',
                'description' => 'Office equipment, computers, and machinery',
                'life_months_default' => 48, // 4 years
                'method_default' => 'straight_line',
                'salvage_value_policy' => 0,
                'non_depreciable' => false,
                'asset_account_id' => $accounts['1.2.1.06']->id, // Peralatan Kantor
                'accumulated_depreciation_account_id' => $accounts['1.2.1.07']->id, // Akumulasi Penyusutan Peralatan Kantor
                'depreciation_expense_account_id' => $accounts['6.2.9']->id, // Biaya Penyusutan
                'gain_on_disposal_account_id' => $accounts['7.1.1']->id, // Pendapatan Sewa
                'loss_on_disposal_account_id' => $accounts['7.2.3']->id, // Kerugian Penjualan Aset
                'is_active' => true,
            ],
            [
                'code' => 'FURNITURE',
                'name' => 'Furniture & Fixtures',
                'description' => 'Furniture, fixtures, and office furnishings',
                'life_months_default' => 36, // 3 years
                'method_default' => 'straight_line',
                'salvage_value_policy' => 0,
                'non_depreciable' => false,
                'asset_account_id' => $accounts['1.2.2']->id, // Fixed Assets - Furniture & Fixtures
                'accumulated_depreciation_account_id' => $accounts['1.2.4']->id, // Accumulated Depreciation - Furniture & Fixtures
                'depreciation_expense_account_id' => $accounts['5.2.6']->id, // Depreciation Expense
                'gain_on_disposal_account_id' => $accounts['4.3.2']->id, // Miscellaneous Income
                'loss_on_disposal_account_id' => $accounts['5.3.1']->id, // Campaign & Promotion (temporary for loss)
                'is_active' => true,
            ],
            [
                'code' => 'IT_EQUIPMENT',
                'name' => 'IT Equipment',
                'description' => 'Computers, servers, and IT infrastructure',
                'life_months_default' => 36, // 3 years
                'method_default' => 'straight_line',
                'salvage_value_policy' => 0,
                'non_depreciable' => false,
                'asset_account_id' => $accounts['1.2.1.04']->id, // Kendaraan
                'accumulated_depreciation_account_id' => $accounts['1.2.1.05']->id, // Akumulasi Penyusutan Kendaraan
                'depreciation_expense_account_id' => $accounts['6.2.9']->id, // Biaya Penyusutan
                'gain_on_disposal_account_id' => $accounts['7.1.1']->id, // Pendapatan Sewa
                'loss_on_disposal_account_id' => $accounts['7.2.3']->id, // Kerugian Penjualan Aset
                'is_active' => true,
            ],
        ];

        foreach ($assetCategories as $category) {
            AssetCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
