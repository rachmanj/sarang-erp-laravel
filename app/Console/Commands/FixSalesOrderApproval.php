<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserRole;
use App\Models\SalesOrderApproval;
use App\Models\SalesOrder;
use App\Services\ApprovalWorkflowService;

class FixSalesOrderApproval extends Command
{
    protected $signature = 'sales-order:fix-approval {orderNo?} {--all : Fix all Sales Orders with missing approval records}';
    protected $description = 'Fix missing approval records for Sales Order(s)';

    public function handle()
    {
        // Ensure superadmin has officer role
        $this->ensureSuperadminHasOfficerRole();
        
        if ($this->option('all')) {
            return $this->fixAllSalesOrders();
        }
        
        $orderNo = $this->argument('orderNo');
        
        if (!$orderNo) {
            $this->error("Please provide an order number or use --all flag");
            return 1;
        }
        
        $salesOrder = SalesOrder::where('order_no', $orderNo)->first();
        
        if (!$salesOrder) {
            $this->error("Sales Order {$orderNo} not found");
            return 1;
        }
        
        $this->info("Found Sales Order: {$salesOrder->order_no} (ID: {$salesOrder->id}, Amount: {$salesOrder->total_amount})");
        
        // Ensure supervisor role exists for at least one user
        $supervisorExists = UserRole::where('role_name', 'supervisor')
            ->where('is_active', true)
            ->exists();
            
        if (!$supervisorExists) {
            $this->warn("No active supervisor found. Assigning supervisor role to user ID 2 (Nurita)...");
            UserRole::updateOrCreate(
                [
                    'user_id' => 2,
                    'role_name' => 'supervisor',
                ],
                [
                    'is_active' => true,
                ]
            );
            $this->info("✓ Supervisor role assigned to user ID 2");
        }
        
        // Check if superadmin has officer role
        $officerRole = UserRole::where('user_id', 1)
            ->where('role_name', 'officer')
            ->first();
            
        if (!$officerRole) {
            $this->info("Creating 'officer' role for superadmin...");
            UserRole::create([
                'user_id' => 1,
                'role_name' => 'officer',
                'is_active' => true,
            ]);
            $this->info("✓ Role created");
        } else {
            $this->info("✓ superadmin already has 'officer' role");
        }
        
        // Check if approval records exist
        $existingApprovals = SalesOrderApproval::where('sales_order_id', $salesOrder->id)->count();
        
        if ($existingApprovals > 0) {
            $this->info("Found {$existingApprovals} existing approval record(s)");
            $this->warn("Deleting existing approval records to recreate workflow...");
            SalesOrderApproval::where('sales_order_id', $salesOrder->id)->delete();
        }
        
        $this->info("Creating approval workflow...");
        
        try {
            $approvalWorkflowService = app(ApprovalWorkflowService::class);
            $approvalRecords = $approvalWorkflowService->createWorkflowForDocument(
                'sales_order',
                $salesOrder->id,
                $salesOrder->total_amount
            );
            
            foreach ($approvalRecords as $record) {
                SalesOrderApproval::create([
                    'sales_order_id' => $record['document_id'],
                    'user_id' => $record['user_id'],
                    'approval_level' => $record['role_name'],
                    'status' => $record['status'],
                ]);
            }
            
            $this->info("✓ Created " . count($approvalRecords) . " approval record(s)");
        } catch (\Exception $e) {
            $this->error("Error creating approval workflow: " . $e->getMessage());
            return 1;
        }
        
        // Show current approval status
        $approvals = SalesOrderApproval::where('sales_order_id', $salesOrder->id)
            ->with('user')
            ->get();
            
        $this->info("\nCurrent approval status:");
        foreach ($approvals as $approval) {
            $status = $approval->status === 'pending' ? '⏳ Pending' : ($approval->status === 'approved' ? '✓ Approved' : '✗ Rejected');
            $this->line("  - {$approval->user->name} ({$approval->approval_level}): {$status}");
        }
        
        $this->info("\n✓ Done! You can now approve the Sales Order.");
        
        return 0;
    }
    
    private function ensureSuperadminHasOfficerRole()
    {
        $officerRole = UserRole::where('user_id', 1)
            ->where('role_name', 'officer')
            ->first();
            
        if (!$officerRole) {
            $this->info("Creating 'officer' role for superadmin...");
            UserRole::create([
                'user_id' => 1,
                'role_name' => 'officer',
                'is_active' => true,
            ]);
            $this->info("✓ Role created");
        }
    }
    
    private function fixAllSalesOrders()
    {
        // Find all Sales Orders with pending approval status but no approval records
        $salesOrders = SalesOrder::where('approval_status', 'pending')
            ->whereDoesntHave('approvals')
            ->get();
            
        if ($salesOrders->isEmpty()) {
            $this->info("No Sales Orders found with missing approval records.");
            return 0;
        }
        
        $this->info("Found {$salesOrders->count()} Sales Order(s) with missing approval records.");
        
        $fixed = 0;
        $failed = 0;
        
        $approvalWorkflowService = app(ApprovalWorkflowService::class);
        
        foreach ($salesOrders as $so) {
            try {
                $this->line("Processing: {$so->order_no} (ID: {$so->id})...");
                
                $approvalRecords = $approvalWorkflowService->createWorkflowForDocument(
                    'sales_order',
                    $so->id,
                    $so->total_amount
                );
                
                foreach ($approvalRecords as $record) {
                    SalesOrderApproval::create([
                        'sales_order_id' => $record['document_id'],
                        'user_id' => $record['user_id'],
                        'approval_level' => $record['role_name'],
                        'status' => $record['status'],
                    ]);
                }
                
                $this->info("  ✓ Created " . count($approvalRecords) . " approval record(s)");
                $fixed++;
            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                $failed++;
            }
        }
        
        $this->info("\n✓ Fixed: {$fixed}");
        if ($failed > 0) {
            $this->error("✗ Failed: {$failed}");
        }
        
        return $failed > 0 ? 1 : 0;
    }
}
