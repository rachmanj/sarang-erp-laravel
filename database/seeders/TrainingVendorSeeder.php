<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Master\Vendor;

class TrainingVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create vendors for training scenarios
        $vendors = [
            [
                'code' => 'VEND-001',
                'name' => 'OfficeMax Indonesia',
                'email' => 'sales@officemax.co.id',
                'phone' => '+62-21-11111111',
                'address' => 'Jl. Industri No. 100, Tangerang',
                'tax_id' => '01.111.111.1-111.000',
                'industry' => 'Office Furniture',
                'contact_person' => 'Bapak Joko Widodo',
                'payment_terms' => 30,
                'delivery_performance' => 95,
                'quality_score' => 90,
                'cost_efficiency' => 85,
            ],
            [
                'code' => 'VEND-002',
                'name' => 'PT Furniture Indonesia',
                'email' => 'orders@furniture.co.id',
                'phone' => '+62-21-22222222',
                'address' => 'Jl. Kayu Jati No. 200, Semarang',
                'tax_id' => '02.222.222.2-222.000',
                'industry' => 'Furniture Manufacturing',
                'contact_person' => 'Ibu Siti Nurhaliza',
                'payment_terms' => 45,
                'delivery_performance' => 88,
                'quality_score' => 95,
                'cost_efficiency' => 80,
            ],
            [
                'code' => 'VEND-003',
                'name' => 'CV Elektronik Maju',
                'email' => 'sales@elektronik.co.id',
                'phone' => '+62-21-33333333',
                'address' => 'Jl. Teknologi No. 300, Bandung',
                'tax_id' => '03.333.333.3-333.000',
                'industry' => 'Electronics',
                'contact_person' => 'Bapak Agus Salim',
                'payment_terms' => 15,
                'delivery_performance' => 92,
                'quality_score' => 88,
                'cost_efficiency' => 90,
            ],
            [
                'code' => 'VEND-004',
                'name' => 'PT Tekstil Nusantara',
                'email' => 'info@tekstil.co.id',
                'phone' => '+62-21-44444444',
                'address' => 'Jl. Tekstil No. 400, Solo',
                'tax_id' => '04.444.444.4-444.000',
                'industry' => 'Textile',
                'contact_person' => 'Ibu Maya Sari',
                'payment_terms' => 30,
                'delivery_performance' => 85,
                'quality_score' => 92,
                'cost_efficiency' => 75,
            ],
            [
                'code' => 'VEND-005',
                'name' => 'UD Makanan Sehat',
                'email' => 'orders@makanansehat.co.id',
                'phone' => '+62-21-55555555',
                'address' => 'Jl. Pangan No. 500, Bogor',
                'tax_id' => '05.555.555.5-555.000',
                'industry' => 'Food & Beverage',
                'contact_person' => 'Bapak Bambang Sutrisno',
                'payment_terms' => 14,
                'delivery_performance' => 90,
                'quality_score' => 95,
                'cost_efficiency' => 85,
            ],
        ];

        foreach ($vendors as $vendorData) {
            // Create vendor
            $vendor = Vendor::updateOrCreate(
                ['code' => $vendorData['code']],
                [
                    'name' => $vendorData['name'],
                    'email' => $vendorData['email'],
                    'phone' => $vendorData['phone'],
                ]
            );

            // Vendor created successfully
        }
    }
}
