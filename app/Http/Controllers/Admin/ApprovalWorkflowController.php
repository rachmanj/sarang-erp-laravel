<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalThreshold;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ApprovalWorkflowController extends Controller
{
    public function index()
    {
        $this->authorize('view-admin');
        return view('admin.approval-workflows.index');
    }

    public function data(Request $request)
    {
        $this->authorize('view-admin');
        $query = ApprovalWorkflow::with('steps')->select(['id', 'document_type', 'workflow_name', 'is_active', 'created_at']);
        
        return DataTables::of($query)
            ->addColumn('document_type_label', function (ApprovalWorkflow $workflow) {
                return ucwords(str_replace('_', ' ', $workflow->document_type));
            })
            ->addColumn('steps_count', function (ApprovalWorkflow $workflow) {
                return $workflow->steps->count();
            })
            ->addColumn('steps_preview', function (ApprovalWorkflow $workflow) {
                $steps = $workflow->steps->pluck('role_name')->toArray();
                if (empty($steps)) {
                    return '<span class="text-muted">No steps</span>';
                }
                return '<span class="badge badge-info mr-1">' . implode('</span> <span class="badge badge-info mr-1">', $steps) . '</span>';
            })
            ->addColumn('is_active_label', function (ApprovalWorkflow $workflow) {
                return $workflow->is_active 
                    ? '<span class="badge badge-success">Active</span>' 
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('actions', function (ApprovalWorkflow $workflow) {
                $html = '<a href="' . route('admin.approval-workflows.show', $workflow) . '" class="btn btn-sm btn-info mr-1" title="View"><i class="fas fa-eye"></i></a>';
                $html .= '<a href="' . route('admin.approval-workflows.edit', $workflow) . '" class="btn btn-sm btn-warning mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                return $html;
            })
            ->rawColumns(['steps_preview', 'is_active_label', 'actions'])
            ->toJson();
    }

    public function create()
    {
        $this->authorize('view-admin');
        $documentTypes = ['purchase_order', 'sales_order'];
        $roles = ['officer', 'supervisor', 'manager'];
        return view('admin.approval-workflows.create', compact('documentTypes', 'roles'));
    }

    public function store(Request $request)
    {
        $this->authorize('view-admin');
        
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:purchase_order,sales_order'],
            'workflow_name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.role_name' => ['required', 'string', 'in:officer,supervisor,manager'],
            'steps.*.approval_type' => ['required', 'string', 'in:sequential,parallel'],
            'steps.*.is_required' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            $workflow = ApprovalWorkflow::create([
                'document_type' => $validated['document_type'],
                'workflow_name' => $validated['workflow_name'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['steps'] as $step) {
                $workflow->steps()->create([
                    'step_order' => $step['step_order'],
                    'role_name' => $step['role_name'],
                    'approval_type' => $step['approval_type'],
                    'is_required' => $step['is_required'] ?? true,
                ]);
            }
        });

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Approval workflow created successfully.');
    }

    public function show(ApprovalWorkflow $approvalWorkflow)
    {
        $this->authorize('view-admin');
        $approvalWorkflow->load('steps');
        $thresholds = ApprovalThreshold::where('document_type', $approvalWorkflow->document_type)
            ->orderBy('min_amount')
            ->get();
        
        return view('admin.approval-workflows.show', compact('approvalWorkflow', 'thresholds'));
    }

    public function edit(ApprovalWorkflow $approvalWorkflow)
    {
        $this->authorize('view-admin');
        $approvalWorkflow->load('steps');
        $documentTypes = ['purchase_order', 'sales_order'];
        $roles = ['officer', 'supervisor', 'manager'];
        $thresholds = ApprovalThreshold::where('document_type', $approvalWorkflow->document_type)
            ->orderBy('min_amount')
            ->get();
        
        return view('admin.approval-workflows.edit', compact('approvalWorkflow', 'documentTypes', 'roles', 'thresholds'));
    }

    public function update(Request $request, ApprovalWorkflow $approvalWorkflow)
    {
        $this->authorize('view-admin');
        
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:purchase_order,sales_order'],
            'workflow_name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.role_name' => ['required', 'string', 'in:officer,supervisor,manager'],
            'steps.*.approval_type' => ['required', 'string', 'in:sequential,parallel'],
            'steps.*.is_required' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated, $approvalWorkflow) {
            $approvalWorkflow->update([
                'document_type' => $validated['document_type'],
                'workflow_name' => $validated['workflow_name'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $approvalWorkflow->steps()->delete();
            
            foreach ($validated['steps'] as $step) {
                $approvalWorkflow->steps()->create([
                    'step_order' => $step['step_order'],
                    'role_name' => $step['role_name'],
                    'approval_type' => $step['approval_type'],
                    'is_required' => $step['is_required'] ?? true,
                ]);
            }
        });

        return redirect()->route('admin.approval-workflows.index')
            ->with('success', 'Approval workflow updated successfully.');
    }

    public function storeThreshold(Request $request)
    {
        $this->authorize('view-admin');
        
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:purchase_order,sales_order'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'gt:min_amount'],
            'required_approvals' => ['required', 'array', 'min:1'],
            'required_approvals.*' => ['required', 'string', 'in:officer,supervisor,manager'],
        ]);

        $overlapping = ApprovalThreshold::where('document_type', $validated['document_type'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('min_amount', [$validated['min_amount'], $validated['max_amount']])
                    ->orWhereBetween('max_amount', [$validated['min_amount'], $validated['max_amount']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('min_amount', '<=', $validated['min_amount'])
                          ->where('max_amount', '>=', $validated['max_amount']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return back()->withInput()->with('error', 'This threshold range overlaps with an existing threshold.');
        }

        ApprovalThreshold::create([
            'document_type' => $validated['document_type'],
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'],
            'required_approvals' => $validated['required_approvals'],
        ]);

        return back()->with('success', 'Approval threshold created successfully.');
    }

    public function updateThreshold(Request $request, ApprovalThreshold $approvalThreshold)
    {
        $this->authorize('view-admin');
        
        $validated = $request->validate([
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'gt:min_amount'],
            'required_approvals' => ['required', 'array', 'min:1'],
            'required_approvals.*' => ['required', 'string', 'in:officer,supervisor,manager'],
        ]);

        $overlapping = ApprovalThreshold::where('document_type', $approvalThreshold->document_type)
            ->where('id', '!=', $approvalThreshold->id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('min_amount', [$validated['min_amount'], $validated['max_amount']])
                    ->orWhereBetween('max_amount', [$validated['min_amount'], $validated['max_amount']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('min_amount', '<=', $validated['min_amount'])
                          ->where('max_amount', '>=', $validated['max_amount']);
                    });
            })
            ->exists();

        if ($overlapping) {
            return back()->withInput()->with('error', 'This threshold range overlaps with an existing threshold.');
        }

        $approvalThreshold->update([
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'],
            'required_approvals' => $validated['required_approvals'],
        ]);

        return back()->with('success', 'Approval threshold updated successfully.');
    }

    public function destroyThreshold(ApprovalThreshold $approvalThreshold)
    {
        $this->authorize('view-admin');
        $documentType = $approvalThreshold->document_type;
        $approvalThreshold->delete();
        
        return back()->with('success', 'Approval threshold deleted successfully.');
    }
}
