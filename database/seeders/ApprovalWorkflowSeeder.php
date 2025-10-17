<?php

namespace Database\Seeders;

use App\Models\ApprovalThreshold;
use App\Models\ApprovalWorkflow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default workflows for purchase orders
        $purchaseWorkflow = ApprovalWorkflow::create([
            'document_type' => 'purchase_order',
            'workflow_name' => 'Purchase Order Approval Workflow',
            'is_active' => true,
        ]);

        // Create workflow steps
        $purchaseWorkflow->steps()->createMany([
            [
                'step_order' => 1,
                'role_name' => 'officer',
                'approval_type' => 'sequential',
                'is_required' => true,
            ],
            [
                'step_order' => 2,
                'role_name' => 'supervisor',
                'approval_type' => 'sequential',
                'is_required' => true,
            ],
            [
                'step_order' => 3,
                'role_name' => 'manager',
                'approval_type' => 'sequential',
                'is_required' => true,
            ],
        ]);

        // Create default thresholds
        ApprovalThreshold::create([
            'document_type' => 'purchase_order',
            'min_amount' => 0,
            'max_amount' => 5000000,
            'required_approvals' => ['officer'],
        ]);

        ApprovalThreshold::create([
            'document_type' => 'purchase_order',
            'min_amount' => 5000000,
            'max_amount' => 15000000,
            'required_approvals' => ['officer', 'supervisor'],
        ]);

        ApprovalThreshold::create([
            'document_type' => 'purchase_order',
            'min_amount' => 15000000,
            'max_amount' => 999999999,
            'required_approvals' => ['officer', 'supervisor', 'manager'],
        ]);

        // Create default workflows for sales orders
        $salesWorkflow = ApprovalWorkflow::create([
            'document_type' => 'sales_order',
            'workflow_name' => 'Sales Order Approval Workflow',
            'is_active' => true,
        ]);

        // Create workflow steps for sales orders
        $salesWorkflow->steps()->createMany([
            [
                'step_order' => 1,
                'role_name' => 'officer',
                'approval_type' => 'sequential',
                'is_required' => true,
            ],
            [
                'step_order' => 2,
                'role_name' => 'supervisor',
                'approval_type' => 'sequential',
                'is_required' => true,
            ],
            [
                'step_order' => 3,
                'role_name' => 'manager',
                'approval_type' => 'sequential',
                'is_required' => true,
            ],
        ]);

        // Create default thresholds for sales orders
        ApprovalThreshold::create([
            'document_type' => 'sales_order',
            'min_amount' => 0,
            'max_amount' => 5000000,
            'required_approvals' => ['officer'],
        ]);

        ApprovalThreshold::create([
            'document_type' => 'sales_order',
            'min_amount' => 5000000,
            'max_amount' => 15000000,
            'required_approvals' => ['officer', 'supervisor'],
        ]);

        ApprovalThreshold::create([
            'document_type' => 'sales_order',
            'min_amount' => 15000000,
            'max_amount' => 999999999,
            'required_approvals' => ['officer', 'supervisor', 'manager'],
        ]);
    }
}
