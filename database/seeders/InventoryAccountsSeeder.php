<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'code' => '1.1.3.01',
                'name' => 'Inventory Reserved',
                'account_type' => 'asset',
                'is_postable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '1.1.3.02',
                'name' => 'Inventory Available',
                'account_type' => 'asset',
                'is_postable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '4.1.1',
                'name' => 'Sales Revenue',
                'account_type' => 'revenue',
                'is_postable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '5.1.1',
                'name' => 'Cost of Goods Sold',
                'account_type' => 'expense',
                'is_postable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($accounts as $account) {
            // Check if account already exists
            $existing = DB::table('accounts')->where('code', $account['code'])->first();

            if (!$existing) {
                DB::table('accounts')->insert($account);
            }
        }
    }
}
