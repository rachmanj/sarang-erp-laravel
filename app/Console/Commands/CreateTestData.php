<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessPartner;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Accounting\Account;

class CreateTestData extends Command
{
    protected $signature = 'test:create-data';
    protected $description = 'Create test data for GRPO testing';

    public function handle()
    {
        $this->info('Creating test data...');

        // Get or create supplier
        $supplier = BusinessPartner::where('partner_type', 'supplier')->first();
        if (!$supplier) {
            $supplier = BusinessPartner::create([
                'code' => 'SUP001',
                'name' => 'PT Test Supplier',
                'partner_type' => 'supplier',
                'status' => 'active'
            ]);
        }

        // Get inventory account
        $account = Account::where('code', '1.1.3.01')->first() ?? Account::first();

        // Create Purchase Order
        $po = PurchaseOrder::create([
            'order_no' => 'PO-TEST-001',
            'date' => now()->toDateString(),
            'business_partner_id' => $supplier->id,
            'description' => 'Test Purchase Order for GRPO Testing',
            'total_amount' => 1000000,
            'order_type' => 'item',
            'status' => 'approved'
        ]);

        // Create PO lines
        PurchaseOrderLine::create([
            'order_id' => $po->id,
            'account_id' => $account->id,
            'item_code' => 'ITEM001',
            'item_name' => 'Test Item 1',
            'unit_of_measure' => 'PCS',
            'description' => 'Test item for GRPO',
            'qty' => 10,
            'received_qty' => 0,
            'pending_qty' => 10,
            'unit_price' => 50000,
            'amount' => 500000,
            'status' => 'open'
        ]);

        PurchaseOrderLine::create([
            'order_id' => $po->id,
            'account_id' => $account->id,
            'item_code' => 'ITEM002',
            'item_name' => 'Test Item 2',
            'unit_of_measure' => 'PCS',
            'description' => 'Another test item',
            'qty' => 5,
            'received_qty' => 0,
            'pending_qty' => 5,
            'unit_price' => 100000,
            'amount' => 500000,
            'status' => 'open'
        ]);

        $this->info('Test data created successfully!');
        $this->info('Supplier: ' . $supplier->name . ' (ID: ' . $supplier->id . ')');
        $this->info('Purchase Order: ' . $po->order_no . ' (ID: ' . $po->id . ')');
        $this->info('You can now test GRPO creation at: http://localhost:8000/goods-receipt-pos/create');
    }
}
