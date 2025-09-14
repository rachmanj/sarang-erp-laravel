<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Master\TaxCode;

class TradingTaxCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxCodes = [
            [
                'code' => 'PPN11_OUT',
                'name' => 'PPN Keluaran 11%',
                'type' => 'ppn_output',
                'rate' => 11.00,
                'calculation_method' => 'percentage',
                'reporting_frequency' => 'monthly',
                'is_mandatory' => true,
                'is_active' => true,
                'effective_date' => '2024-01-01',
            ],
            [
                'code' => 'PPN11_IN',
                'name' => 'PPN Masukan 11%',
                'type' => 'ppn_input',
                'rate' => 11.00,
                'calculation_method' => 'percentage',
                'reporting_frequency' => 'monthly',
                'is_mandatory' => true,
                'is_active' => true,
                'effective_date' => '2024-01-01',
            ],
            [
                'code' => 'PPH21',
                'name' => 'PPh Pasal 21',
                'type' => 'withholding',
                'rate' => 5.00,
                'calculation_method' => 'percentage',
                'reporting_frequency' => 'monthly',
                'is_mandatory' => true,
                'is_active' => true,
                'effective_date' => '2024-01-01',
            ],
            [
                'code' => 'PPH22',
                'name' => 'PPh Pasal 22',
                'type' => 'withholding',
                'rate' => 1.50,
                'calculation_method' => 'percentage',
                'reporting_frequency' => 'monthly',
                'is_mandatory' => true,
                'is_active' => true,
                'effective_date' => '2024-01-01',
            ],
            [
                'code' => 'PPH23',
                'name' => 'PPh Pasal 23',
                'type' => 'withholding',
                'rate' => 2.00,
                'calculation_method' => 'percentage',
                'reporting_frequency' => 'monthly',
                'is_mandatory' => true,
                'is_active' => true,
                'effective_date' => '2024-01-01',
            ],
        ];

        foreach ($taxCodes as $taxCode) {
            TaxCode::create($taxCode);
        }
    }
}
