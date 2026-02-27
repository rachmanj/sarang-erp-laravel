<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Customer's Projects</h5>
        @can('business_partners.manage')
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addProjectModal">
                <i class="fas fa-plus"></i> Add Project
            </button>
        @endcan
    </div>
    <div class="card-body">
        @if ($businessPartner->projects->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            @can('business_partners.manage')
                                <th style="width: 120px">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($businessPartner->projects as $project)
                            <tr>
                                <td>{{ $project->code }}</td>
                                <td>{{ $project->name }}</td>
                                <td>
                                    @switch($project->status)
                                        @case('active')
                                            <span class="badge badge-success">Active</span>
                                            @break
                                        @case('completed')
                                            <span class="badge badge-success">Completed</span>
                                            @break
                                        @case('on_hold')
                                            <span class="badge badge-warning">On Hold</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge badge-danger">Cancelled</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($project->status) }}</span>
                                    @endswitch
                                </td>
                                <td>{{ $project->start_date ? $project->start_date->format('d/m/Y') : '-' }}</td>
                                <td>{{ $project->end_date ? $project->end_date->format('d/m/Y') : '-' }}</td>
                                @can('business_partners.manage')
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning btn-edit-project"
                                            data-id="{{ $project->id }}"
                                            data-code="{{ $project->code }}"
                                            data-name="{{ $project->name }}"
                                            data-description="{{ $project->description ?? '' }}"
                                            data-status="{{ $project->status }}"
                                            data-start-date="{{ $project->start_date?->format('Y-m-d') ?? '' }}"
                                            data-end-date="{{ $project->end_date?->format('Y-m-d') ?? '' }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('business_partner_projects.destroy', [$businessPartner, $project]) }}"
                                            method="post" class="d-inline"
                                            onsubmit="return confirm('Delete this project?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                            @if ($project->description)
                                <tr>
                                    <td colspan="{{ auth()->user()->can('business_partners.manage') ? 6 : 5 }}" class="bg-light">
                                        <small><strong>Description:</strong> {{ $project->description }}</small>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No customer projects added yet. Add projects to link them to sales documents.
            </div>
        @endif
    </div>
</div>

@can('business_partners.manage')
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('business_partner_projects.store', $businessPartner) }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Customer Project</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="project_code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_code" name="code" required maxlength="50"
                                value="{{ old('code') }}">
                            @error('code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="project_name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="project_name" name="name" required maxlength="150"
                                value="{{ old('name') }}">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="project_description">Description</label>
                            <textarea class="form-control" id="project_description" name="description" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="project_status">Status</label>
                            <select class="form-control" id="project_status" name="status">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project_start_date">Start Date</label>
                                    <input type="date" class="form-control" id="project_start_date" name="start_date"
                                        value="{{ old('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project_end_date">End Date</label>
                                    <input type="date" class="form-control" id="project_end_date" name="end_date"
                                        value="{{ old('end_date') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editProjectForm" method="post">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Customer Project</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_project_code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_project_code" name="code" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="edit_project_name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_project_name" name="name" required maxlength="150">
                        </div>
                        <div class="form-group">
                            <label for="edit_project_description">Description</label>
                            <textarea class="form-control" id="edit_project_description" name="description" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_project_status">Status</label>
                            <select class="form-control" id="edit_project_status" name="status">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="on_hold">On Hold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_project_start_date">Start Date</label>
                                    <input type="date" class="form-control" id="edit_project_start_date" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_project_end_date">End Date</label>
                                    <input type="date" class="form-control" id="edit_project_end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.btn-edit-project').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const baseUrl = "{{ route('business_partners.show', $businessPartner) }}";
                document.getElementById('editProjectForm').action = baseUrl.replace(/\/$/, '') + '/projects/' + id;
                document.getElementById('edit_project_code').value = this.dataset.code;
                document.getElementById('edit_project_name').value = this.dataset.name;
                document.getElementById('edit_project_description').value = this.dataset.description || '';
                document.getElementById('edit_project_status').value = this.dataset.status;
                document.getElementById('edit_project_start_date').value = this.dataset.startDate || '';
                document.getElementById('edit_project_end_date').value = this.dataset.endDate || '';
                $('#editProjectModal').modal('show');
            });
        });
    </script>
    @endpush
@endcan
