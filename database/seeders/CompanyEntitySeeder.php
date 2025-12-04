<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanyEntity;

class CompanyEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entities = [
            [
                'code' => '71',
                'name' => 'PT Cahaya Sarange Jaya',
                'legal_name' => 'PT Cahaya Sarange Jaya',
                'tax_number' => null,
                'address' => 'Jl. Gatot Subroto No. 12, Jakarta, Indonesia',
                'phone' => '+62 21 555 1234',
                'email' => 'info@cahayasarangjaya.com',
                'website' => 'https://www.cahayasarangjaya.com',
                'logo_path' => 'logo_pt_csj.png',
                'letterhead_meta' => [
                    'primary_color' => '#0C3559',
                    'secondary_color' => '#0C3559',
                ],
            ],
            [
                'code' => '72',
                'name' => 'CV Cahaya Saranghae',
                'legal_name' => 'CV Cahaya Saranghae',
                'tax_number' => null,
                'address' => 'Jl. Melati No. 8, Jakarta, Indonesia',
                'phone' => '+62 21 777 9876',
                'email' => 'halo@cahayasaranghae.com',
                'website' => 'https://www.cahayasaranghae.com',
                'logo_path' => 'logo_cv_saranghae.png',
                'letterhead_meta' => [
                    'primary_color' => '#1C2236',
                    'secondary_color' => '#1C2236',
                ],
            ],
        ];

        foreach ($entities as $entity) {
            CompanyEntity::updateOrCreate(
                ['code' => $entity['code']],
                [
                    'name' => $entity['name'],
                    'legal_name' => $entity['legal_name'],
                    'tax_number' => $entity['tax_number'],
                    'address' => $entity['address'],
                    'phone' => $entity['phone'],
                    'email' => $entity['email'],
                    'website' => $entity['website'],
                    'logo_path' => $entity['logo_path'],
                    'letterhead_meta' => $entity['letterhead_meta'],
                    'is_active' => true,
                ]
            );
        }
    }
}
