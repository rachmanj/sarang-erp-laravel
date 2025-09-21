<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GRGIPurpose;
use App\Models\GRGIAccountMapping;
use App\Models\ProductCategory;
use App\Models\Accounting\Account;

class GRGIAccountMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get default accounts based on the actual chart of accounts
        $inventoryAccount = Account::where('code', '1.1.3.01')->first(); // Persediaan Barang Dagangan
        $expenseAccount = Account::where('code', '5.7')->first(); // Penyesuaian Persediaan
        $revenueAccount = Account::where('name', 'like', '%pendapatan%')->first();
        $donationAccount = Account::where('name', 'like', '%donasi%')->orWhere('name', 'like', '%donation%')->first();
        $adjustmentAccount = Account::where('code', '5.7')->first(); // Penyesuaian Persediaan

        // If accounts don't exist, create them or use fallback
        if (!$inventoryAccount) {
            $inventoryAccount = Account::where('name', 'like', '%persediaan%')->first();
        }
        if (!$expenseAccount) {
            $expenseAccount = Account::where('type', 'expense')->first();
        }
        if (!$revenueAccount) {
            $revenueAccount = Account::where('type', 'income')->first();
        }
        if (!$donationAccount) {
            $donationAccount = Account::where('type', 'expense')->first();
        }
        if (!$adjustmentAccount) {
            $adjustmentAccount = Account::where('code', '5.7')->first();
        }

        // Get product categories
        $categories = ProductCategory::all();

        // Get GR/GI purposes
        $grPurposes = GRGIPurpose::where('type', 'goods_receipt')->get();
        $giPurposes = GRGIPurpose::where('type', 'goods_issue')->get();

        // Create account mappings for GR purposes
        foreach ($grPurposes as $purpose) {
            foreach ($categories as $category) {
                $debitAccountId = $inventoryAccount ? $inventoryAccount->id : null;
                $creditAccountId = $this->getCreditAccountForGRPurpose($purpose->name, $expenseAccount, $revenueAccount, $donationAccount, $adjustmentAccount);

                if ($debitAccountId && $creditAccountId) {
                    GRGIAccountMapping::updateOrCreate(
                        [
                            'purpose_id' => $purpose->id,
                            'item_category_id' => $category->id,
                        ],
                        [
                            'debit_account_id' => $debitAccountId,
                            'credit_account_id' => $creditAccountId,
                        ]
                    );
                }
            }
        }

        // Create account mappings for GI purposes
        foreach ($giPurposes as $purpose) {
            foreach ($categories as $category) {
                $debitAccountId = $this->getDebitAccountForGIPurpose($purpose->name, $expenseAccount, $revenueAccount, $donationAccount, $adjustmentAccount);
                $creditAccountId = $inventoryAccount ? $inventoryAccount->id : null;

                if ($debitAccountId && $creditAccountId) {
                    GRGIAccountMapping::updateOrCreate(
                        [
                            'purpose_id' => $purpose->id,
                            'item_category_id' => $category->id,
                        ],
                        [
                            'debit_account_id' => $debitAccountId,
                            'credit_account_id' => $creditAccountId,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Get credit account for GR purpose
     */
    private function getCreditAccountForGRPurpose($purposeName, $expenseAccount, $revenueAccount, $donationAccount, $adjustmentAccount)
    {
        switch (strtolower($purposeName)) {
            case 'customer return':
                return $revenueAccount ? $revenueAccount->id : null;
            case 'consignment received':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'donation received':
                return $donationAccount ? $donationAccount->id : null;
            case 'found inventory':
                return $adjustmentAccount ? $adjustmentAccount->id : null;
            case 'sample received':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'transfer in':
                return $adjustmentAccount ? $adjustmentAccount->id : null;
            default:
                return $expenseAccount ? $expenseAccount->id : null;
        }
    }

    /**
     * Get debit account for GI purpose
     */
    private function getDebitAccountForGIPurpose($purposeName, $expenseAccount, $revenueAccount, $donationAccount, $adjustmentAccount)
    {
        switch (strtolower($purposeName)) {
            case 'damaged goods':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'donation given':
                return $donationAccount ? $donationAccount->id : null;
            case 'employee benefits':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'internal consumption':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'quality control':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'r&d materials':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'sample given':
                return $expenseAccount ? $expenseAccount->id : null;
            case 'transfer out':
                return $adjustmentAccount ? $adjustmentAccount->id : null;
            default:
                return $expenseAccount ? $expenseAccount->id : null;
        }
    }
}