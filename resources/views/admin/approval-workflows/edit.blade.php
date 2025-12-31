@extends('layouts.main')

@section('title_page')
    Edit Approval Workflow
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.approval-workflows.index') }}">Approval Workflows</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Workflow Information</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.approval-workflows.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('admin.approval-workflows.update', $approvalWorkflow) }}" method="POST" id="workflow-form">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_type">Document Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('document_type') is-invalid @enderror" 
                                                id="document_type" name="document_type" required>
                                                @foreach($documentTypes as $type)
                                                    <option value="{{ $type }}" {{ old('document_type', $approvalWorkflow->document_type) == $type ? 'selected' : '' }}>
                                                        {{ ucwords(str_replace('_', ' ', $type)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('document_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="workflow_name">Workflow Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('workflow_name') is-invalid @enderror"
                                                id="workflow_name" name="workflow_name" value="{{ old('workflow_name', $approvalWorkflow->workflow_name) }}" required>
                                            @error('workflow_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" 
                                            {{ old('is_active', $approvalWorkflow->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label>Workflow Steps <span class="text-danger">*</span></label>
                                    <div id="steps-container">
                                        @php
                                            $oldSteps = old('steps', $approvalWorkflow->steps->toArray());
                                        @endphp
                                        @foreach($oldSteps as $index => $step)
                                            <div class="step-row mb-3 p-3 border rounded">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <label>Step Order</label>
                                                        <input type="number" class="form-control" name="steps[{{ $index }}][step_order]" 
                                                            value="{{ $step['step_order'] ?? $index + 1 }}" min="1" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Role</label>
                                                        <select class="form-control" name="steps[{{ $index }}][role_name]" required>
                                                            @foreach($roles as $role)
                                                                <option value="{{ $role }}" {{ ($step['role_name'] ?? '') == $role ? 'selected' : '' }}>
                                                                    {{ ucfirst($role) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Approval Type</label>
                                                        <select class="form-control" name="steps[{{ $index }}][approval_type]" required>
                                                            <option value="sequential" {{ ($step['approval_type'] ?? 'sequential') == 'sequential' ? 'selected' : '' }}>Sequential</option>
                                                            <option value="parallel" {{ ($step['approval_type'] ?? '') == 'parallel' ? 'selected' : '' }}>Parallel</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>&nbsp;</label>
                                                        <div class="custom-control custom-switch mt-2">
                                                            <input type="checkbox" class="custom-control-input" 
                                                                name="steps[{{ $index }}][is_required]" value="1" 
                                                                {{ ($step['is_required'] ?? true) ? 'checked' : '' }}>
                                                            <label class="custom-control-label">Required</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>&nbsp;</label>
                                                        <button type="button" class="btn btn-danger btn-sm btn-block remove-step">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm mt-2" id="add-step">
                                        <i class="fas fa-plus"></i> Add Step
                                    </button>
                                    @error('steps')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Workflow
                                </button>
                                <a href="{{ route('admin.approval-workflows.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-dollar-sign mr-1"></i>
                                Approval Thresholds
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addThresholdModal">
                                    <i class="fas fa-plus"></i> Add Threshold
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($thresholds->isEmpty())
                                <p class="text-muted">No thresholds configured. Add a threshold to define approval requirements based on document amount.</p>
                            @else
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Min Amount</th>
                                            <th>Max Amount</th>
                                            <th>Required Approvals</th>
                                            <th>Actions</th>
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
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning edit-threshold" 
                                                        data-id="{{ $threshold->id }}"
                                                        data-min="{{ $threshold->min_amount }}"
                                                        data-max="{{ $threshold->max_amount }}"
                                                        data-approvals="{{ json_encode($threshold->required_approvals) }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('admin.approval-workflows.thresholds.destroy', $threshold) }}" 
                                                        method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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

    <!-- Add Threshold Modal -->
    <div class="modal fade" id="addThresholdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.approval-workflows.thresholds.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="document_type" value="{{ $approvalWorkflow->document_type }}">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Approval Threshold</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Min Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="min_amount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Max Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="max_amount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Required Approvals <span class="text-danger">*</span></label>
                            <div>
                                @foreach($roles as $role)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role_{{ $role }}" 
                                            name="required_approvals[]" value="{{ $role }}">
                                        <label class="custom-control-label" for="role_{{ $role }}">{{ ucfirst($role) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Threshold</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Threshold Modal -->
    <div class="modal fade" id="editThresholdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editThresholdForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Approval Threshold</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Min Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="min_amount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Max Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="max_amount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Required Approvals <span class="text-danger">*</span></label>
                            <div>
                                @foreach($roles as $role)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="edit_role_{{ $role }}" 
                                            name="required_approvals[]" value="{{ $role }}">
                                        <label class="custom-control-label" for="edit_role_{{ $role }}">{{ ucfirst($role) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Threshold</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            let stepIndex = {{ count($approvalWorkflow->steps) }};
            const roles = @json($roles);

            $('#add-step').on('click', function() {
                const stepHtml = `
                    <div class="step-row mb-3 p-3 border rounded">
                        <div class="row">
                            <div class="col-md-2">
                                <label>Step Order</label>
                                <input type="number" class="form-control" name="steps[${stepIndex}][step_order]" value="${stepIndex + 1}" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label>Role</label>
                                <select class="form-control" name="steps[${stepIndex}][role_name]" required>
                                    ${roles.map(role => `<option value="${role}">${role.charAt(0).toUpperCase() + role.slice(1)}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Approval Type</label>
                                <select class="form-control" name="steps[${stepIndex}][approval_type]" required>
                                    <option value="sequential" selected>Sequential</option>
                                    <option value="parallel">Parallel</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" name="steps[${stepIndex}][is_required]" value="1" checked>
                                    <label class="custom-control-label">Required</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm btn-block remove-step">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $('#steps-container').append(stepHtml);
                stepIndex++;
            });

            $(document).on('click', '.remove-step', function() {
                if ($('.step-row').length > 1) {
                    $(this).closest('.step-row').remove();
                } else {
                    Swal.fire('Warning', 'At least one step is required', 'warning');
                }
            });

            $('.edit-threshold').on('click', function() {
                const id = $(this).data('id');
                const min = $(this).data('min');
                const max = $(this).data('max');
                const approvals = $(this).data('approvals');

                $('#editThresholdForm').attr('action', '/admin/approval-workflows/thresholds/' + id);
                $('#editThresholdForm input[name="min_amount"]').val(min);
                $('#editThresholdForm input[name="max_amount"]').val(max);
                
                $('#editThresholdForm input[type="checkbox"]').prop('checked', false);
                approvals.forEach(function(role) {
                    $('#editThresholdForm input[value="' + role + '"]').prop('checked', true);
                });

                $('#editThresholdModal').modal('show');
            });
        });
    </script>
@endpush
