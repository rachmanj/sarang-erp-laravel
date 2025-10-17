<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Accounting\Account;
use App\Models\ErpParameter;

class FXAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create FX Gain/Loss accounts if they don't exist
        $fxAccounts = [
            [
                'code' => '5.2.1.01',
                'name' => 'Realized FX Gain/Loss',
                'type' => 'income',
                'parent_id' => null,
                'is_postable' => true,
            ],
            [
                'code' => '4.2.1.01',
                'name' => 'Unrealized FX Gain/Loss',
                'type' => 'liability',
                'parent_id' => null,
                'is_postable' => true,
            ],
        ];

        $realizedFxAccount = null;
        $unrealizedFxAccount = null;

        foreach ($fxAccounts as $accountData) {
            $account = Account::updateOrCreate(
                ['code' => $accountData['code']],
                $accountData
            );

            if ($accountData['code'] === '5.2.1.01') {
                $realizedFxAccount = $account;
            } elseif ($accountData['code'] === '4.2.1.01') {
                $unrealizedFxAccount = $account;
            }
        }

        // Update ERP Parameters with FX account IDs
        if ($realizedFxAccount) {
            ErpParameter::updateOrCreate(
                ['parameter_key' => 'realized_gain_loss_account_id'],
                [
                    'category' => 'accounting',
                    'parameter_name' => 'Realized FX Gain/Loss Account',
                    'parameter_value' => (string)$realizedFxAccount->id,
                    'data_type' => 'integer',
                    'description' => 'Account used for realized foreign exchange gains and losses',
                ]
            );
        }

        if ($unrealizedFxAccount) {
            ErpParameter::updateOrCreate(
                ['parameter_key' => 'unrealized_gain_loss_account_id'],
                [
                    'category' => 'accounting',
                    'parameter_name' => 'Unrealized FX Gain/Loss Account',
                    'parameter_value' => (string)$unrealizedFxAccount->id,
                    'data_type' => 'integer',
                    'description' => 'Account used for unrealized foreign exchange gains and losses',
                ]
            );
        }

        $this->command->info('FX accounts and parameters seeded successfully!');
    }
}
