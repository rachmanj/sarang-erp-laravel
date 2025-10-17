<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderApproval;
use App\Models\SalesOrderApproval;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $pendingApprovals = $this->getPendingApprovalsForUser($userId);
        $approvalStats = $this->getApprovalStatistics($userId);
        $recentActivity = $this->getRecentApprovalActivity($userId);

        return view('approvals.dashboard', compact(
            'pendingApprovals',
            'approvalStats',
            'recentActivity'
        ));
    }

    private function getPendingApprovalsForUser(int $userId): \Illuminate\Support\Collection
    {
        $purchaseApprovals = PurchaseOrderApproval::where('user_id', $userId)
            ->where('status', 'pending')
            ->with(['purchaseOrder.businessPartner', 'purchaseOrder.warehouse', 'purchaseOrder.lines.inventoryItem'])
            ->get()
            ->map(function ($approval) {
                $purchaseOrder = $approval->purchaseOrder;
                return [
                    'id' => $approval->id,
                    'document_id' => $approval->purchase_order_id,
                    'document_number' => $purchaseOrder ? $purchaseOrder->order_no : 'N/A',
                    'document_type' => 'Purchase Order',
                    'amount' => $purchaseOrder ? $purchaseOrder->total_amount : 0,
                    'priority' => $this->calculatePriority($purchaseOrder ? $purchaseOrder->total_amount : 0),
                    'created_at' => $approval->created_at,
                    'approval_level' => $approval->approval_level,
                    'po_date' => $purchaseOrder ? $purchaseOrder->date : null,
                    'vendor' => $purchaseOrder && $purchaseOrder->businessPartner ? $purchaseOrder->businessPartner->name : 'N/A',
                    'warehouse' => $purchaseOrder && $purchaseOrder->warehouse ? $purchaseOrder->warehouse->name : 'N/A',
                    'order_type' => $purchaseOrder ? $purchaseOrder->order_type : 'N/A',
                    'description' => $purchaseOrder ? $purchaseOrder->description : '',
                    'line_items' => $purchaseOrder && $purchaseOrder->lines ? $purchaseOrder->lines->map(function ($line) {
                        return [
                            'item_code' => $line->item_code,
                            'item_name' => $line->item_name,
                            'description' => $line->description,
                            'qty' => $line->qty,
                            'unit_price' => $line->unit_price,
                            'amount' => $line->amount,
                        ];
                    }) : collect(),
                ];
            });

        $salesApprovals = SalesOrderApproval::where('user_id', $userId)
            ->where('status', 'pending')
            ->with(['salesOrder'])
            ->get()
            ->map(function ($approval) {
                $salesOrder = $approval->salesOrder;
                return [
                    'id' => $approval->id,
                    'document_id' => $approval->sales_order_id,
                    'document_number' => $salesOrder ? $salesOrder->order_no : 'N/A',
                    'document_type' => 'Sales Order',
                    'amount' => $salesOrder ? $salesOrder->total_amount : 0,
                    'priority' => $this->calculatePriority($salesOrder ? $salesOrder->total_amount : 0),
                    'created_at' => $approval->created_at,
                    'approval_level' => $approval->approval_level,
                ];
            });

        return $purchaseApprovals->concat($salesApprovals)->sortBy('created_at');
    }

    private function getApprovalStatistics(int $userId): array
    {
        $pendingCount = PurchaseOrderApproval::where('user_id', $userId)
            ->where('status', 'pending')
            ->count() +
            SalesOrderApproval::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        $approvedCount = PurchaseOrderApproval::where('user_id', $userId)
            ->where('status', 'approved')
            ->count() +
            SalesOrderApproval::where('user_id', $userId)
            ->where('status', 'approved')
            ->count();

        $rejectedCount = PurchaseOrderApproval::where('user_id', $userId)
            ->where('status', 'rejected')
            ->count() +
            SalesOrderApproval::where('user_id', $userId)
            ->where('status', 'rejected')
            ->count();

        return [
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'total' => $pendingCount + $approvedCount + $rejectedCount,
        ];
    }

    private function getRecentApprovalActivity(int $userId): \Illuminate\Support\Collection
    {
        $purchaseActivity = PurchaseOrderApproval::where('user_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->with(['purchaseOrder'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($approval) {
                $purchaseOrder = $approval->purchaseOrder;
                return [
                    'document_type' => 'Purchase Order',
                    'document_number' => $purchaseOrder ? $purchaseOrder->order_no : 'N/A',
                    'action' => $approval->status,
                    'date' => $approval->updated_at,
                ];
            });

        $salesActivity = SalesOrderApproval::where('user_id', $userId)
            ->whereIn('status', ['approved', 'rejected'])
            ->with(['salesOrder'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($approval) {
                $salesOrder = $approval->salesOrder;
                return [
                    'document_type' => 'Sales Order',
                    'document_number' => $salesOrder ? $salesOrder->order_no : 'N/A',
                    'action' => $approval->status,
                    'date' => $approval->updated_at,
                ];
            });

        return $purchaseActivity->concat($salesActivity)
            ->sortByDesc('date')
            ->take(10);
    }

    private function calculatePriority(float $amount): string
    {
        if ($amount >= 15000000) {
            return 'high';
        } elseif ($amount >= 5000000) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function approve(Request $request, $approvalId)
    {
        try {
            $approval = $this->findApproval($approvalId);
            if (!$approval) {
                return response()->json(['error' => 'Approval not found'], 404);
            }

            // Check if user has permission to approve this document
            if ($approval['user_id'] !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Update approval status
            $this->updateApprovalStatus($approval['id'], $approval['document_type'], 'approved');

            // Check if all required approvals are completed
            $this->checkWorkflowCompletion($approval['document_id'], $approval['document_type']);

            return response()->json([
                'success' => true,
                'message' => 'Document approved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error approving document: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to approve document'], 500);
        }
    }

    public function reject(Request $request, $approvalId)
    {
        try {
            $approval = $this->findApproval($approvalId);
            if (!$approval) {
                return response()->json(['error' => 'Approval not found'], 404);
            }

            // Check if user has permission to reject this document
            if ($approval['user_id'] !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Update approval status
            $this->updateApprovalStatus($approval['id'], $approval['document_type'], 'rejected');

            // Reject all pending approvals for this document
            $this->rejectAllPendingApprovals($approval['document_id'], $approval['document_type']);

            return response()->json([
                'success' => true,
                'message' => 'Document rejected successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error rejecting document: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to reject document'], 500);
        }
    }

    private function findApproval($approvalId)
    {
        // Try to find in purchase order approvals
        $purchaseApproval = PurchaseOrderApproval::find($approvalId);
        if ($purchaseApproval) {
            return [
                'id' => $purchaseApproval->id,
                'user_id' => $purchaseApproval->user_id,
                'document_id' => $purchaseApproval->purchase_order_id,
                'document_type' => 'purchase_order',
                'status' => $purchaseApproval->status,
            ];
        }

        // Try to find in sales order approvals
        $salesApproval = SalesOrderApproval::find($approvalId);
        if ($salesApproval) {
            return [
                'id' => $salesApproval->id,
                'user_id' => $salesApproval->user_id,
                'document_id' => $salesApproval->sales_order_id,
                'document_type' => 'sales_order',
                'status' => $salesApproval->status,
            ];
        }

        return null;
    }

    private function updateApprovalStatus($approvalId, $documentType, $status)
    {
        if ($documentType === 'purchase_order') {
            PurchaseOrderApproval::where('id', $approvalId)->update([
                'status' => $status,
                'updated_at' => now()
            ]);
        } elseif ($documentType === 'sales_order') {
            SalesOrderApproval::where('id', $approvalId)->update([
                'status' => $status,
                'updated_at' => now()
            ]);
        }
    }

    private function checkWorkflowCompletion($documentId, $documentType)
    {
        // Get all approvals for this document
        $approvals = $this->getAllApprovalsForDocument($documentId, $documentType);

        // Check if all required approvals are completed
        $allApproved = $approvals->every(function ($approval) {
            return $approval['status'] === 'approved';
        });

        if ($allApproved) {
            // Update document status to approved
            $this->updateDocumentStatus($documentId, $documentType, 'approved');
        }
    }

    private function rejectAllPendingApprovals($documentId, $documentType)
    {
        $approvals = $this->getAllApprovalsForDocument($documentId, $documentType);

        foreach ($approvals as $approval) {
            if ($approval['status'] === 'pending') {
                $this->updateApprovalStatus($approval['id'], $documentType, 'rejected');
            }
        }

        // Update document status to rejected
        $this->updateDocumentStatus($documentId, $documentType, 'rejected');
    }

    private function getAllApprovalsForDocument($documentId, $documentType)
    {
        if ($documentType === 'purchase_order') {
            return PurchaseOrderApproval::where('purchase_order_id', $documentId)->get();
        } elseif ($documentType === 'sales_order') {
            return SalesOrderApproval::where('sales_order_id', $documentId)->get();
        }

        return collect();
    }

    private function updateDocumentStatus($documentId, $documentType, $status)
    {
        if ($documentType === 'purchase_order') {
            PurchaseOrder::where('id', $documentId)->update(['status' => $status]);
        } elseif ($documentType === 'sales_order') {
            SalesOrder::where('id', $documentId)->update(['status' => $status]);
        }
    }
}
