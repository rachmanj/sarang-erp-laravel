<?php

namespace App\Services;

use App\Models\ControlAccount;
use App\Models\ControlAccountBalance;
use App\Models\SubsidiaryLedgerAccount;
use App\Models\BusinessPartner;
use App\Models\InventoryItem;
use App\Models\Asset;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalLine;
use Illuminate\Support\Facades\DB;
use Exception;

class ControlAccountService
{
    /**
     * Create a new control account
     */
    public function createControlAccount(array $data): ControlAccount
    {
        return DB::transaction(function () use ($data) {
            $controlAccount = ControlAccount::create($data);
            
            // Create initial balance record for default dimensions
            $this->initializeBalance($controlAccount->id);
            
            return $controlAccount;
        });
    }

    /**
     * Add a subsidiary account to a control account
     */
    public function addSubsidiaryAccount(int $controlAccountId, string $subsidiaryType, int $subsidiaryId, int $accountId): SubsidiaryLedgerAccount
    {
        return SubsidiaryLedgerAccount::create([
            'control_account_id' => $controlAccountId,
            'subsidiary_type' => $subsidiaryType,
            'subsidiary_id' => $subsidiaryId,
            'account_id' => $accountId,
            'is_active' => true,
        ]);
    }

    /**
     * Calculate control account balance from journal lines
     */
    public function calculateControlBalance(int $controlAccountId, ?int $projectId = null, ?int $deptId = null): float
    {
        $controlAccount = ControlAccount::findOrFail($controlAccountId);
        
        $query = JournalLine::where('account_id', $controlAccount->account_id);
        
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        
        if ($deptId !== null) {
            $query->where('dept_id', $deptId);
        }
        
        $result = $query->selectRaw('SUM(debit - credit) as balance')->first();
        
        return (float) ($result->balance ?? 0);
    }

    /**
     * Calculate subsidiary accounts total
     */
    public function calculateSubsidiaryTotal(int $controlAccountId, ?int $projectId = null, ?int $deptId = null): float
    {
        $controlAccount = ControlAccount::findOrFail($controlAccountId);
        $subsidiaryAccounts = $controlAccount->subsidiaryAccounts()->active()->get();
        
        $total = 0;
        
        foreach ($subsidiaryAccounts as $subsidiary) {
            $query = JournalLine::where('account_id', $subsidiary->account_id);
            
            if ($projectId !== null) {
                $query->where('project_id', $projectId);
            }
            
            if ($deptId !== null) {
                $query->where('dept_id', $deptId);
            }
            
            $result = $query->selectRaw('SUM(debit - credit) as balance')->first();
            $total += (float) ($result->balance ?? 0);
        }
        
        return $total;
    }

    /**
     * Reconcile control account with subsidiary accounts
     */
    public function reconcileControlAccount(int $controlAccountId, ?int $projectId = null, ?int $deptId = null): array
    {
        $controlBalance = $this->calculateControlBalance($controlAccountId, $projectId, $deptId);
        $subsidiaryTotal = $this->calculateSubsidiaryTotal($controlAccountId, $projectId, $deptId);
        $variance = $controlBalance - $subsidiaryTotal;
        
        // Update or create balance record
        $balanceRecord = ControlAccountBalance::updateOrCreate(
            [
                'control_account_id' => $controlAccountId,
                'project_id' => $projectId,
                'dept_id' => $deptId,
            ],
            [
                'balance' => $controlBalance,
                'reconciled_balance' => $subsidiaryTotal,
                'last_reconciled_at' => now(),
            ]
        );
        
        return [
            'control_balance' => $controlBalance,
            'subsidiary_total' => $subsidiaryTotal,
            'variance' => $variance,
            'is_reconciled' => abs($variance) < 0.01,
            'reconciled_at' => $balanceRecord->last_reconciled_at,
        ];
    }

    /**
     * Get reconciliation exceptions (variances above tolerance)
     */
    public function getReconciliationExceptions(float $tolerance = 0.01): array
    {
        $exceptions = [];
        
        $controlAccounts = ControlAccount::active()->get();
        
        foreach ($controlAccounts as $controlAccount) {
            $balances = $controlAccount->balances;
            
            foreach ($balances as $balance) {
                $variance = $balance->getReconciliationVarianceAttribute();
                
                if ($variance !== null && abs($variance) > $tolerance) {
                    $exceptions[] = [
                        'control_account' => $controlAccount,
                        'balance_record' => $balance,
                        'variance' => $variance,
                        'last_reconciled' => $balance->last_reconciled_at,
                    ];
                }
            }
        }
        
        return $exceptions;
    }

    /**
     * Set up control accounts for existing business partners
     */
    public function setupBusinessPartnerControlAccounts(): array
    {
        $results = ['ar_created' => 0, 'ap_created' => 0, 'subsidiaries_created' => 0];
        
        return DB::transaction(function () use ($results) {
            // Create AR Control Account
            $arAccount = Account::where('code', '1.1.2.01')->first();
            if ($arAccount) {
                $arControl = ControlAccount::firstOrCreate([
                    'account_id' => $arAccount->id,
                    'control_type' => 'ar',
                ], [
                    'description' => 'Accounts Receivable Control Account',
                    'is_active' => true,
                ]);
                
                if ($arControl->wasRecentlyCreated) {
                    $results['ar_created'] = 1;
                    $this->initializeBalance($arControl->id);
                }
                
                // Create subsidiary accounts for customers
                $customers = BusinessPartner::where('partner_type', 'customer')->get();
                foreach ($customers as $customer) {
                    // Use the control account for now - in a full implementation, 
                    // each business partner would have their own subsidiary account
                    $accountId = $customer->account_id ?: $arControl->account_id;
                    
                    $subsidiary = SubsidiaryLedgerAccount::firstOrCreate([
                        'control_account_id' => $arControl->id,
                        'subsidiary_type' => 'business_partner',
                        'subsidiary_id' => $customer->id,
                    ], [
                        'account_id' => $accountId,
                        'is_active' => true,
                    ]);
                    
                    if ($subsidiary->wasRecentlyCreated) {
                        $results['subsidiaries_created']++;
                    }
                }
            }
            
            // Create AP Control Account
            $apAccount = Account::where('code', '2.1.1.01')->first();
            if ($apAccount) {
                $apControl = ControlAccount::firstOrCreate([
                    'account_id' => $apAccount->id,
                    'control_type' => 'ap',
                ], [
                    'description' => 'Accounts Payable Control Account',
                    'is_active' => true,
                ]);
                
                if ($apControl->wasRecentlyCreated) {
                    $results['ap_created'] = 1;
                    $this->initializeBalance($apControl->id);
                }
                
                // Create subsidiary accounts for suppliers
                $suppliers = BusinessPartner::where('partner_type', 'supplier')->get();
                foreach ($suppliers as $supplier) {
                    // Use the control account for now - in a full implementation, 
                    // each business partner would have their own subsidiary account
                    $accountId = $supplier->account_id ?: $apControl->account_id;
                    
                    $subsidiary = SubsidiaryLedgerAccount::firstOrCreate([
                        'control_account_id' => $apControl->id,
                        'subsidiary_type' => 'business_partner',
                        'subsidiary_id' => $supplier->id,
                    ], [
                        'account_id' => $accountId,
                        'is_active' => true,
                    ]);
                    
                    if ($subsidiary->wasRecentlyCreated) {
                        $results['subsidiaries_created']++;
                    }
                }
            }
            
            return $results;
        });
    }

    /**
     * Set up inventory control account
     */
    public function setupInventoryControlAccount(): array
    {
        $results = ['inventory_created' => 0, 'subsidiaries_created' => 0];
        
        return DB::transaction(function () use ($results) {
            $inventoryAccount = Account::where('code', '1.1.3.01')->first();
            if ($inventoryAccount) {
                $inventoryControl = ControlAccount::firstOrCreate([
                    'account_id' => $inventoryAccount->id,
                    'control_type' => 'inventory',
                ], [
                    'description' => 'Inventory Control Account',
                    'is_active' => true,
                ]);
                
                if ($inventoryControl->wasRecentlyCreated) {
                    $results['inventory_created'] = 1;
                    $this->initializeBalance($inventoryControl->id);
                }
                
                // Create subsidiary accounts for inventory items
                $inventoryItems = InventoryItem::where('item_type', 'item')->get();
                foreach ($inventoryItems as $item) {
                    if ($item->productCategory && $item->productCategory->inventory_account_id) {
                        $subsidiary = SubsidiaryLedgerAccount::firstOrCreate([
                            'control_account_id' => $inventoryControl->id,
                            'subsidiary_type' => 'inventory_item',
                            'subsidiary_id' => $item->id,
                        ], [
                            'account_id' => $item->productCategory->inventory_account_id,
                            'is_active' => true,
                        ]);
                        
                        if ($subsidiary->wasRecentlyCreated) {
                            $results['subsidiaries_created']++;
                        }
                    }
                }
            }
            
            return $results;
        });
    }

    /**
     * Initialize balance record for a control account
     */
    private function initializeBalance(int $controlAccountId, ?int $projectId = null, ?int $deptId = null): ControlAccountBalance
    {
        return ControlAccountBalance::create([
            'control_account_id' => $controlAccountId,
            'project_id' => $projectId,
            'dept_id' => $deptId,
            'balance' => 0.00,
            'reconciled_balance' => null,
            'last_reconciled_at' => null,
        ]);
    }

    /**
     * Update control account balance when journal entries are posted
     */
    public function updateBalanceOnJournalPost(int $accountId, float $debitAmount, float $creditAmount, ?int $projectId = null, ?int $deptId = null): void
    {
        // Find control account for this GL account
        $controlAccount = ControlAccount::where('account_id', $accountId)->active()->first();
        
        if (!$controlAccount) {
            return;
        }
        
        $balanceChange = $debitAmount - $creditAmount;
        
        // Update or create balance record
        $balance = ControlAccountBalance::firstOrCreate([
            'control_account_id' => $controlAccount->id,
            'project_id' => $projectId,
            'dept_id' => $deptId,
        ]);
        
        $balance->balance += $balanceChange;
        $balance->save();
    }
}
