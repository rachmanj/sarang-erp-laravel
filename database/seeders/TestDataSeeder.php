<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessPartner;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Accounting\Account;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Create test supplier
        $supplier = BusinessPartner::firstOrCreate(
            ['name' => 'PT Test Supplier'],
            [
                'code' => 'SUP001',
                'partner_type' => 'supplier',
                'status' => 'active'
            ]
        );

        // Create test customer
        $customer = BusinessPartner::firstOrCreate(
            ['name' => 'PT Test Customer'],
            [
                'code' => 'CUST001',
                'partner_type' => 'customer',
                'status' => 'active'
            ]
        );

        // Get inventory account
        $inventoryAccount = Account::where('code', '1.1.3.01')->first();
        if (!$inventoryAccount) {
            $inventoryAccount = Account::first();
        }

        // Create test Purchase Order
        $po = PurchaseOrder::firstOrCreate(
            ['order_no' => 'PO-TEST-001'],
            [
                'date' => now()->toDateString(),
                'business_partner_id' => $supplier->id,
                'description' => 'Test Purchase Order for GRPO Testing',
                'total_amount' => 1000000,
                'order_type' => 'item',
                'status' => 'approved'
            ]
        );

        // Create PO lines
        PurchaseOrderLine::firstOrCreate(
            ['order_id' => $po->id, 'item_code' => 'ITEM001'],
            [
                'account_id' => $inventoryAccount->id,
                'item_name' => 'Test Item 1',
                'unit_of_measure' => 'PCS',
                'description' => 'Test item for GRPO',
                'qty' => 10,
                'received_qty' => 0,
                'pending_qty' => 10,
                'unit_price' => 50000,
                'amount' => 500000,
                'status' => 'open'
            ]
        );

        PurchaseOrderLine::firstOrCreate(
            ['order_id' => $po->id, 'item_code' => 'ITEM002'],
            [
                'account_id' => $inventoryAccount->id,
                'item_name' => 'Test Item 2',
                'unit_of_measure' => 'PCS',
                'description' => 'Another test item',
                'qty' => 5,
                'received_qty' => 0,
                'pending_qty' => 5,
                'unit_price' => 100000,
                'amount' => 500000,
                'status' => 'open'
            ]
        );

        $this->command->info('Test data created successfully!');
        $this->command->info('Supplier: ' . $supplier->name . ' (ID: ' . $supplier->id . ')');
        $this->command->info('Customer: ' . $customer->name . ' (ID: ' . $customer->id . ')');
        $this->command->info('Purchase Order: ' . $po->order_no . ' (ID: ' . $po->id . ')');
    }
}
