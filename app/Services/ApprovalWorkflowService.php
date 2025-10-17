<?php

namespace App\Services;

use App\Models\ApprovalThreshold;
use App\Models\ApprovalWorkflow;
use App\Models\UserRole;

class ApprovalWorkflowService
{
    public function createWorkflowForDocument(string $documentType, int $documentId, float $amount = 0): array
    {
        $requiredApprovals = ApprovalThreshold::getRequiredApprovals($documentType, $amount);

        if (empty($requiredApprovals)) {
            throw new \Exception("No approval threshold found for document type: {$documentType} with amount: {$amount}");
        }

        $approvalRecords = [];

        foreach ($requiredApprovals as $roleName) {
            $users = UserRole::getUsersByRole($roleName);

            foreach ($users as $user) {
                $approvalRecords[] = [
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'user_id' => $user->id,
                    'role_name' => $roleName,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $approvalRecords;
    }

    public function getActiveWorkflow(string $documentType): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::getActiveWorkflow($documentType);
    }

    public function getUsersByRole(string $roleName): \Illuminate\Database\Eloquent\Collection
    {
        return UserRole::getUsersByRole($roleName);
    }

    public function createDefaultWorkflows(): void
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
        ApprovalThreshold::createMany([
            [
                'document_type' => 'purchase_order',
                'min_amount' => 0,
                'max_amount' => 5000000,
                'required_approvals' => ['officer'],
            ],
            [
                'document_type' => 'purchase_order',
                'min_amount' => 5000000,
                'max_amount' => 15000000,
                'required_approvals' => ['officer', 'supervisor'],
            ],
            [
                'document_type' => 'purchase_order',
                'min_amount' => 15000000,
                'max_amount' => 999999999,
                'required_approvals' => ['officer', 'supervisor', 'manager'],
            ],
        ]);
    }
}
