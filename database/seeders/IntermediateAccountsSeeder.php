<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\Account;

class IntermediateAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get parent account IDs
        $arParent = Account::where('code', '1.1.2')->first();
        $apParent = Account::where('code', '2.1.1')->first();

        if (!$arParent || !$apParent) {
            $this->command->error('Parent accounts not found. Please run TradingCoASeeder first.');
            return;
        }

        // Create AR UnInvoice account
        Account::updateOrCreate(
            ['code' => '1.1.2.04'],
            [
                'name' => 'AR UnInvoice',
                'type' => 'asset',
                'is_postable' => true,
                'parent_id' => $arParent->id,
            ]
        );

        // Create AP UnInvoice account
        Account::updateOrCreate(
            ['code' => '2.1.1.03'],
            [
                'name' => 'AP UnInvoice',
                'type' => 'liability',
                'is_postable' => true,
                'parent_id' => $apParent->id,
            ]
        );

        $this->command->info('Intermediate accounts created successfully!');
    }
}
