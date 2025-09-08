<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Master\TaxCode;

class TaxCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['code' => 'PPN_OUT', 'name' => 'PPN Keluaran 11%', 'type' => 'ppn_output', 'rate' => 0.11],
            ['code' => 'PPN_IN', 'name' => 'PPN Masukan 11%', 'type' => 'ppn_input', 'rate' => 0.11],
            ['code' => 'PPh23', 'name' => 'PPh 23 Jasa 2%', 'type' => 'withholding', 'rate' => 0.02],
        ];

        foreach ($items as $item) {
            TaxCode::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'rate' => $item['rate'],
                ]
            );
        }
    }
}
