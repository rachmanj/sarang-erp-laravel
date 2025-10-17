<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ErpParameter;

class CompanyInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyInfoParameters = [
            [
                'category' => 'company_info',
                'parameter_key' => 'company_name',
                'parameter_name' => 'Company Name',
                'parameter_value' => 'Sarange ERP Trading Company',
                'data_type' => 'string',
                'description' => 'Company name displayed on documents',
                'is_active' => true,
            ],
            [
                'category' => 'company_info',
                'parameter_key' => 'company_address',
                'parameter_name' => 'Company Address',
                'parameter_value' => 'Jl. Sudirman No. 123, Jakarta Pusat 10110',
                'data_type' => 'string',
                'description' => 'Company address displayed on documents',
                'is_active' => true,
            ],
            [
                'category' => 'company_info',
                'parameter_key' => 'company_phone',
                'parameter_name' => 'Company Phone',
                'parameter_value' => '+62 21 1234 5678',
                'data_type' => 'string',
                'description' => 'Company phone number',
                'is_active' => true,
            ],
            [
                'category' => 'company_info',
                'parameter_key' => 'company_email',
                'parameter_name' => 'Company Email',
                'parameter_value' => 'info@sarange-erp.com',
                'data_type' => 'string',
                'description' => 'Company email address',
                'is_active' => true,
            ],
            [
                'category' => 'company_info',
                'parameter_key' => 'company_tax_number',
                'parameter_name' => 'Company Tax Number',
                'parameter_value' => '01.234.567.8-901.000',
                'data_type' => 'string',
                'description' => 'Company tax registration number (NPWP)',
                'is_active' => true,
            ],
            [
                'category' => 'company_info',
                'parameter_key' => 'company_website',
                'parameter_name' => 'Company Website',
                'parameter_value' => 'www.sarange-erp.com',
                'data_type' => 'string',
                'description' => 'Company website URL',
                'is_active' => true,
            ],
            [
                'category' => 'company_info',
                'parameter_key' => 'company_logo_path',
                'parameter_name' => 'Company Logo Path',
                'parameter_value' => '',
                'data_type' => 'string',
                'description' => 'Path to company logo file in storage',
                'is_active' => true,
            ],
        ];

        foreach ($companyInfoParameters as $parameter) {
            ErpParameter::updateOrCreate(
                [
                    'category' => $parameter['category'],
                    'parameter_key' => $parameter['parameter_key']
                ],
                $parameter
            );
        }

        $this->command->info('Company Information parameters seeded successfully!');
    }
}
