@extends('layouts.main')

@section('title_page')
    View Approval Workflow
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.approval-workflows.index') }}">Approval Workflows</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Workflow Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.approval-workflows.edit', $approvalWorkflow) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="{{ route('admin.approval-workflows.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Document Type:</strong><br>
                                    {{ ucwords(str_replace('_', ' ', $approvalWorkflow->document_type)) }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Workflow Name:</strong><br>
                                    {{ $approvalWorkflow->workflow_name }}
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong><br>
                                    @if($approvalWorkflow->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </div>
                            </div>

                            <hr>

                            <h5>Workflow Steps</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Step Order</th>
                                        <th>Role</th>
                                        <th>Approval Type</th>
                                        <th>Required</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($approvalWorkflow->steps as $step)
                                        <tr>
                                            <td>{{ $step->step_order }}</td>
                                            <td><span class="badge badge-info">{{ ucfirst($step->role_name) }}</span></td>
                                            <td>{{ ucfirst($step->approval_type) }}</td>
                                            <td>
                                                @if($step->is_required)
                                                    <span class="badge badge-success">Yes</span>
                                                @else
                                                    <span class="badge badge-secondary">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No steps configured</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <hr>

                            <h5>Approval Thresholds</h5>
                            @if($thresholds->isEmpty())
                                <p class="text-muted">No thresholds configured.</p>
                            @else
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Min Amount</th>
                                            <th>Max Amount</th>
                                            <th>Required Approvals</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($thresholds as $threshold)
                                            <tr>
                                                <td>{{ number_format($threshold->min_amount, 2) }}</td>
                                                <td>{{ number_format($threshold->max_amount, 2) }}</td>
                                                <td>
                                                    @foreach($threshold->required_approvals as $role)
                                                        <span class="badge badge-info mr-1">{{ ucfirst($role) }}</span>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
