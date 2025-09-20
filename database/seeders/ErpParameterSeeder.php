<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ErpParameter;

class ErpParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parameters = [
            // Document Closure Parameters
            [
                'category' => 'document_closure',
                'parameter_key' => 'po_overdue_days',
                'parameter_name' => 'Purchase Order Overdue Days',
                'parameter_value' => '30',
                'data_type' => 'integer',
                'description' => 'Number of days after which a Purchase Order is considered overdue',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'grpo_overdue_days',
                'parameter_name' => 'Goods Receipt Overdue Days',
                'parameter_value' => '15',
                'data_type' => 'integer',
                'description' => 'Number of days after which a Goods Receipt is considered overdue',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'pi_overdue_days',
                'parameter_name' => 'Purchase Invoice Overdue Days',
                'parameter_value' => '7',
                'data_type' => 'integer',
                'description' => 'Number of days after which a Purchase Invoice is considered overdue',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'so_overdue_days',
                'parameter_name' => 'Sales Order Overdue Days',
                'parameter_value' => '30',
                'data_type' => 'integer',
                'description' => 'Number of days after which a Sales Order is considered overdue',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'do_overdue_days',
                'parameter_name' => 'Delivery Order Overdue Days',
                'parameter_value' => '15',
                'data_type' => 'integer',
                'description' => 'Number of days after which a Delivery Order is considered overdue',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'si_overdue_days',
                'parameter_name' => 'Sales Invoice Overdue Days',
                'parameter_value' => '7',
                'data_type' => 'integer',
                'description' => 'Number of days after which a Sales Invoice is considered overdue',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'auto_close_days',
                'parameter_name' => 'Auto Close Days',
                'parameter_value' => '90',
                'data_type' => 'integer',
                'description' => 'Number of days after which open documents are automatically closed',
                'is_active' => true,
            ],
            [
                'category' => 'document_closure',
                'parameter_key' => 'enable_auto_closure',
                'parameter_name' => 'Enable Auto Closure',
                'parameter_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Enable automatic closure of overdue documents',
                'is_active' => true,
            ],

            // System Settings
            [
                'category' => 'system_settings',
                'parameter_key' => 'company_name',
                'parameter_name' => 'Company Name',
                'parameter_value' => 'Sarang ERP Trading Company',
                'data_type' => 'string',
                'description' => 'Company name for document headers',
                'is_active' => true,
            ],
            [
                'category' => 'system_settings',
                'parameter_key' => 'default_currency',
                'parameter_name' => 'Default Currency',
                'parameter_value' => 'IDR',
                'data_type' => 'string',
                'description' => 'Default currency for the system',
                'is_active' => true,
            ],
            [
                'category' => 'system_settings',
                'parameter_key' => 'default_timezone',
                'parameter_name' => 'Default Timezone',
                'parameter_value' => 'Asia/Jakarta',
                'data_type' => 'string',
                'description' => 'Default timezone for the system',
                'is_active' => true,
            ],

            // Price Difference Handling
            [
                'category' => 'price_handling',
                'parameter_key' => 'allow_price_differences',
                'parameter_name' => 'Allow Price Differences',
                'parameter_value' => '1',
                'data_type' => 'boolean',
                'description' => 'Allow price differences between documents',
                'is_active' => true,
            ],
            [
                'category' => 'price_handling',
                'parameter_key' => 'max_price_difference_percent',
                'parameter_name' => 'Max Price Difference Percent',
                'parameter_value' => '10',
                'data_type' => 'integer',
                'description' => 'Maximum allowed price difference percentage',
                'is_active' => true,
            ],
        ];

        foreach ($parameters as $parameter) {
            ErpParameter::updateOrCreate(
                [
                    'category' => $parameter['category'],
                    'parameter_key' => $parameter['parameter_key']
                ],
                $parameter
            );
        }

        $this->command->info('ERP Parameters seeded successfully!');
    }
}
