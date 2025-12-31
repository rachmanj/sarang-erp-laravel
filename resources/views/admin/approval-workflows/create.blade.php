@extends('layouts.main')

@section('title_page')
    Create Approval Workflow
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.approval-workflows.index') }}">Approval Workflows</a></li>
    <li class="breadcrumb-item active">Create</li>
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
                                <a href="{{ route('admin.approval-workflows.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('admin.approval-workflows.store') }}" method="POST" id="workflow-form">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_type">Document Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('document_type') is-invalid @enderror" 
                                                id="document_type" name="document_type" required>
                                                <option value="">-- Select Document Type --</option>
                                                @foreach($documentTypes as $type)
                                                    <option value="{{ $type }}" {{ old('document_type') == $type ? 'selected' : '' }}>
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
                                                id="workflow_name" name="workflow_name" value="{{ old('workflow_name') }}" required>
                                            @error('workflow_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label>Workflow Steps <span class="text-danger">*</span></label>
                                    <div id="steps-container">
                                        @if(old('steps'))
                                            @foreach(old('steps') as $index => $step)
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
                                        @else
                                            <div class="step-row mb-3 p-3 border rounded">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <label>Step Order</label>
                                                        <input type="number" class="form-control" name="steps[0][step_order]" value="1" min="1" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Role</label>
                                                        <select class="form-control" name="steps[0][role_name]" required>
                                                            @foreach($roles as $role)
                                                                <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label>Approval Type</label>
                                                        <select class="form-control" name="steps[0][approval_type]" required>
                                                            <option value="sequential" selected>Sequential</option>
                                                            <option value="parallel">Parallel</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>&nbsp;</label>
                                                        <div class="custom-control custom-switch mt-2">
                                                            <input type="checkbox" class="custom-control-input" name="steps[0][is_required]" value="1" checked>
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
                                        @endif
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
                                    <i class="fas fa-save"></i> Create Workflow
                                </button>
                                <a href="{{ route('admin.approval-workflows.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function() {
            let stepIndex = {{ old('steps') ? count(old('steps')) : 1 }};
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
        });
    </script>
@endpush
