<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test vendors
        $vendors = [
            [
                'code' => 'VEND001',
                'name' => 'PT Supplier Elektronik',
                'email' => 'john@supplier-elektronik.com',
                'phone' => '081234567890',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'npwp' => '123456789012345',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'VEND002',
                'name' => 'CV Toko Pakaian',
                'email' => 'jane@toko-pakaian.com',
                'phone' => '081234567891',
                'address' => 'Jl. Thamrin No. 456, Jakarta',
                'npwp' => '123456789012346',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'VEND003',
                'name' => 'PT Konsultan IT',
                'email' => 'bob@konsultan-it.com',
                'phone' => '081234567892',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta',
                'npwp' => '123456789012347',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($vendors as $vendor) {
            DB::table('vendors')->insert($vendor);
        }

        // Create test customers
        $customers = [
            [
                'code' => 'CUST001',
                'name' => 'PT Pelanggan Utama',
                'email' => 'alice@pelanggan-utama.com',
                'phone' => '081234567893',
                'address' => 'Jl. Rasuna Said No. 321, Jakarta',
                'npwp' => '123456789012348',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CUST002',
                'name' => 'CV Toko Retail',
                'email' => 'charlie@toko-retail.com',
                'phone' => '081234567894',
                'address' => 'Jl. HR Rasuna Said No. 654, Jakarta',
                'npwp' => '123456789012349',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->insert($customer);
        }
    }
}
