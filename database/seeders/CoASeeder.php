<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Accounting\Account;

class CoASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codeToId = [];

        $create = function (string $code, string $name, string $type, bool $isPostable = true, ?string $parentCode = null) use (&$codeToId) {
            $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;
            $account = Account::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'is_postable' => $isPostable,
                    'parent_id' => $parentId,
                ]
            );
            $codeToId[$code] = $account->id;
        };

        // Assets (1)
        $create('1', 'Assets', 'asset', false);
        $create('1.1', 'Current Assets', 'asset', false, '1');
        $create('1.1.1', 'Cash on Hand', 'asset', true, '1.1');
        $create('1.1.2', 'Bank - Operating', 'asset', false, '1.1');
        $create('1.1.2.01', 'Bank – Operating – Main', 'asset', true, '1.1.2');
        $create('1.1.3', 'Bank - Restricted Funds', 'asset', false, '1.1');
        $create('1.1.3.01', 'Bank – Restricted – Donor X', 'asset', true, '1.1.3');
        $create('1.1.4', 'Accounts Receivable - Trade', 'asset', true, '1.1');
        $create('1.1.5', 'Accounts Receivable - Grants/Donations', 'asset', true, '1.1');
        $create('1.1.6', 'PPN Masukan (VAT Input)', 'asset', true, '1.1');
        $create('1.1.7', 'Prepaid Expenses', 'asset', true, '1.1');
        $create('1.1.8', 'Other Receivables', 'asset', true, '1.1');

        $create('1.2', 'Non-Current Assets', 'asset', false, '1');
        $create('1.2.1', 'Fixed Assets - Equipment', 'asset', true, '1.2');
        $create('1.2.2', 'Fixed Assets - Furniture & Fixtures', 'asset', true, '1.2');
        $create('1.2.3', 'Accumulated Depreciation - Equipment', 'asset', true, '1.2');
        $create('1.2.4', 'Accumulated Depreciation - Furniture & Fixtures', 'asset', true, '1.2');

        // Liabilities (2)
        $create('2', 'Liabilities', 'liability', false);
        $create('2.1', 'Current Liabilities', 'liability', false, '2');
        $create('2.1.1', 'Accounts Payable - Trade', 'liability', true, '2.1');
        $create('2.1.2', 'Taxes Payable - PPN Keluaran', 'liability', true, '2.1');
        $create('2.1.3', 'Taxes Payable - Withholding', 'liability', true, '2.1');
        $create('2.1.4', 'Accrued Expenses', 'liability', true, '2.1');
        $create('2.1.5', 'Deferred Revenue', 'liability', true, '2.1');
        $create('2.2', 'Non-Current Liabilities', 'liability', false, '2');
        $create('2.2.1', 'Long-term Loans Payable', 'liability', true, '2.2');

        // Net Assets / Dana (3)
        $create('3', 'Net Assets (Dana)', 'net_assets', false);
        $create('3.1', 'Dana Tidak Terikat', 'net_assets', false, '3');
        $create('3.1.1', 'Saldo Awal Dana Tidak Terikat', 'net_assets', true, '3.1');
        $create('3.1.2', 'Surplus/Defisit Berjalan (Unrestricted)', 'net_assets', true, '3.1');
        $create('3.2', 'Dana Terikat', 'net_assets', false, '3');
        $create('3.2.1', 'Dana Terikat Temporer', 'net_assets', true, '3.2');
        $create('3.2.2', 'Dana Terikat Permanen', 'net_assets', true, '3.2');

        // Income (4)
        $create('4', 'Income', 'income', false);
        $create('4.1', 'Program Income', 'income', false, '4');
        $create('4.1.1', 'Tuition/Training Fees (Non-PPN)', 'income', true, '4.1');
        $create('4.1.2', 'Tuition/Training Fees (PPN Output)', 'income', true, '4.1');
        $create('4.2', 'Grants and Donations', 'income', false, '4');
        $create('4.2.1', 'Restricted Grants Income', 'income', true, '4.2');
        $create('4.2.2', 'Unrestricted Donations', 'income', true, '4.2');
        $create('4.3', 'Other Income', 'income', false, '4');
        $create('4.3.1', 'Interest Income', 'income', true, '4.3');
        $create('4.3.2', 'Miscellaneous Income', 'income', true, '4.3');

        // Expenses (5)
        $create('5', 'Expenses', 'expense', false);
        $create('5.1', 'Program Expenses', 'expense', false, '5');
        $create('5.1.1', 'Instructor Fees / Honoraria', 'expense', true, '5.1');
        $create('5.1.2', 'Training Materials & Supplies', 'expense', true, '5.1');
        $create('5.1.3', 'Student Support / Scholarships', 'expense', true, '5.1');
        $create('5.1.4', 'Program Travel & Events', 'expense', true, '5.1');
        $create('5.2', 'Administrative Expenses', 'expense', false, '5');
        $create('5.2.1', 'Salaries & Wages', 'expense', true, '5.2');
        $create('5.2.2', 'BPJS Ketenagakerjaan/Kesehatan', 'expense', true, '5.2');
        $create('5.2.3', 'Office Rent & Utilities', 'expense', true, '5.2');
        $create('5.2.4', 'Communication & Internet', 'expense', true, '5.2');
        $create('5.2.5', 'Professional Fees (Legal/Accounting)', 'expense', true, '5.2');
        $create('5.2.6', 'Depreciation Expense', 'expense', true, '5.2');
        $create('5.2.7', 'Office Supplies', 'expense', true, '5.2');
        $create('5.3', 'Fundraising Expenses', 'expense', false, '5');
        $create('5.3.1', 'Campaign & Promotion', 'expense', true, '5.3');
        $create('5.3.2', 'Event Costs', 'expense', true, '5.3');
    }
}
