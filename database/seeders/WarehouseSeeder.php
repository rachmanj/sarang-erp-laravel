<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'WH001',
                'name' => 'Main Warehouse',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'contact_person' => 'John Doe',
                'phone' => '021-1234567',
                'email' => 'warehouse@company.com',
                'is_active' => true,
            ],
            [
                'code' => 'WH002',
                'name' => 'Branch Warehouse',
                'address' => 'Jl. Thamrin No. 456, Jakarta Selatan',
                'contact_person' => 'Jane Smith',
                'phone' => '021-7654321',
                'email' => 'branch@company.com',
                'is_active' => true,
            ],
            [
                'code' => 'WH003',
                'name' => 'Storage Facility',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta Barat',
                'contact_person' => 'Bob Johnson',
                'phone' => '021-9876543',
                'email' => 'storage@company.com',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouseData) {
            Warehouse::create($warehouseData);
        }

        $this->command->info('Created ' . count($warehouses) . ' warehouses.');
    }
}
