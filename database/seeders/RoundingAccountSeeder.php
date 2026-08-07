<?php

namespace Database\Seeders;

use App\Models\Accounting\Account;
use App\Models\ErpParameter;
use Illuminate\Database\Seeder;

class RoundingAccountSeeder extends Seeder
{
    public function run(): void
    {
        $parent = Account::where('code', '7.1')->first();

        $roundingAccount = Account::updateOrCreate(
            ['code' => '7.1.4'],
            [
                'name' => 'Selisih Pembulatan (Rounding)',
                'type' => 'income',
                'is_postable' => true,
                'parent_id' => $parent?->id,
            ]
        );

        ErpParameter::updateOrCreate(
            ['parameter_key' => 'rounding_account_id'],
            [
                'category' => 'payment_settings',
                'parameter_name' => 'Default Rounding Account',
                'parameter_value' => (string) $roundingAccount->id,
                'data_type' => 'integer',
                'description' => 'Default account for sales receipt and purchase payment rounding differences',
                'is_active' => true,
            ]
        );

        ErpParameter::updateOrCreate(
            ['parameter_key' => 'sales_receipt_rounding_tolerance'],
            [
                'category' => 'payment_settings',
                'parameter_name' => 'Sales Receipt Rounding Tolerance',
                'parameter_value' => '999999',
                'data_type' => 'decimal',
                'description' => 'Maximum allowed difference between cash received and invoice allocation total',
                'is_active' => true,
            ]
        );

        ErpParameter::updateOrCreate(
            ['parameter_key' => 'purchase_payment_rounding_tolerance'],
            [
                'category' => 'payment_settings',
                'parameter_name' => 'Purchase Payment Rounding Tolerance',
                'parameter_value' => '999999',
                'data_type' => 'decimal',
                'description' => 'Maximum allowed difference between cash paid and invoice allocation total',
                'is_active' => true,
            ]
        );
    }
}
