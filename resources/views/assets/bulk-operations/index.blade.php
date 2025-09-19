@extends('layouts.app')

@section('title', 'Bulk Asset Operations')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Bulk Asset Operations</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                            <li class="breadcrumb-item active">Bulk Operations</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-filter"></i> Filter Assets
                                </h3>
                            </div>
                            <div class="card-body">
                                <form id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="search">Search</label>
                                                <input type="text" class="form-control" id="search" name="search"
                                                    placeholder="Search assets...">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="fund_id">Fund</label>
                                                <select class="form-control select2bs4" id="fund_id" name="fund_id">
                                                    <option value="">All Funds</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="project_id">Project</label>
                                                <select class="form-control select2bs4" id="project_id" name="project_id">
                                                    <option value="">All Projects</option>
                                                    @foreach ($projects as $project)
                                                        <option value="{{ $project->id }}">{{ $project->code }} -
                                                            {{ $project->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="department_id">Department</label>
                                                <select class="form-control select2bs4" id="department_id"
                                                    name="department_id">
                                                    <option value="">All Departments</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}">{{ $department->code }} -
                                                            {{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="business_partner_id">Vendor</label>
                                                <select class="form-control select2bs4" id="business_partner_id"
                                                    name="business_partner_id">
                                                    <option value="">All Vendors</option>
                                                    @foreach ($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->code }} -
                                                            {{ $vendor->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select class="form-control" id="status" name="status">
                                                    <option value="">All Status</option>
                                                    <option value="active">Active</option>
                                                    <option value="retired">Retired</option>
                                                    <option value="disposed">Disposed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <div>
                                                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                                        <i class="fas fa-search"></i> Apply Filters
                                                    </button>
                                                    <button type="button" class="btn btn-secondary"
                                                        onclick="clearFilters()">
                                                        <i class="fas fa-times"></i> Clear Filters
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Update Form -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-edit"></i> Bulk Update Selected Assets
                                </h3>
                            </div>
                            <div class="card-body">
                                <form id="bulkUpdateForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_fund_id">Fund</label>
                                                <select class="form-control select2bs4" id="update_fund_id"
                                                    name="updates[fund_id]">
                                                    <option value="">No Change</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_project_id">Project</label>
                                                <select class="form-control select2bs4" id="update_project_id"
                                                    name="updates[project_id]">
                                                    <option value="">No Change</option>
                                                    @foreach ($projects as $project)
                                                        <option value="{{ $project->id }}">{{ $project->code }} -
                                                            {{ $project->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_department_id">Department</label>
                                                <select class="form-control select2bs4" id="update_department_id"
                                                    name="updates[department_id]">
                                                    <option value="">No Change</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}">{{ $department->code }} -
                                                            {{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_business_partner_id">Vendor</label>
                                                <select class="form-control select2bs4" id="update_business_partner_id"
                                                    name="updates[business_partner_id]">
                                                    <option value="">No Change</option>
                                                    @foreach ($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}">{{ $vendor->code }} -
                                                            {{ $vendor->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_status">Status</label>
                                                <select class="form-control" id="update_status" name="updates[status]">
                                                    <option value="">No Change</option>
                                                    <option value="active">Active</option>
                                                    <option value="retired">Retired</option>
                                                    <option value="disposed">Disposed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_method">Depreciation Method</label>
                                                <select class="form-control" id="update_method" name="updates[method]">
                                                    <option value="">No Change</option>
                                                    <option value="straight_line">Straight Line</option>
                                                    <option value="declining_balance">Declining Balance</option>
                                                    <option value="double_declining_balance">Double Declining Balance
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_life_months">Life (Months)</label>
                                                <input type="number" class="form-control" id="update_life_months"
                                                    name="updates[life_months]" min="1" max="600">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="update_placed_in_service_date">Placed in Service Date</label>
                                                <input type="date" class="form-control"
                                                    id="update_placed_in_service_date"
                                                    name="updates[placed_in_service_date]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="update_description">Description</label>
                                                <textarea class="form-control" id="update_description" name="updates[description]" rows="2"
                                                    placeholder="Leave empty for no change"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="update_serial_number">Serial Number</label>
                                                <input type="text" class="form-control" id="update_serial_number"
                                                    name="updates[serial_number]" placeholder="Leave empty for no change">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="button" class="btn btn-info" onclick="previewUpdates()">
                                            <i class="fas fa-eye"></i> Preview Changes
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="applyUpdates()" disabled
                                            id="applyBtn">
                                            <i class="fas fa-save"></i> Apply Updates
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assets Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-list"></i> Assets
                                </h3>
                                <div class="card-tools">
                                    <button class="btn btn-sm btn-primary" onclick="selectAll()">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="selectNone()">
                                        <i class="fas fa-square"></i> Select None
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="assetsTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th width="50px">
                                                    <input type="checkbox" id="selectAllCheckbox"
                                                        onchange="toggleSelectAll()">
                                                </th>
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Acquisition Cost</th>
                                                <th>Fund</th>
                                                <th>Project</th>
                                                <th>Department</th>
                                                <th>Vendor</th>
                                                <th>Status</th>
                                                <th>Placed in Service</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Changes</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="confirmUpdates()">Confirm Updates</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .asset-checkbox {
            margin: 0;
        }

        .preview-item {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .preview-field {
            margin-bottom: 5px;
        }

        .preview-field .field-name {
            font-weight: bold;
            color: #495057;
        }

        .preview-field .current-value {
            color: #6c757d;
            text-decoration: line-through;
        }

        .preview-field .new-value {
            color: #28a745;
            font-weight: bold;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let assetsTable;
        let selectedAssets = [];

        $(document).ready(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Initialize DataTable
            assetsTable = $('#assetsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('assets.bulk-update.data') }}',
                    type: 'GET',
                    data: function(d) {
                        d.search = $('#search').val();
                        d.fund_id = $('#fund_id').val();
                        d.project_id = $('#project_id').val();
                        d.department_id = $('#department_id').val();
                        d.business_partner_id = $('#business_partner_id').val();
                        d.status = $('#status').val();
                    }
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'category_name',
                        name: 'category_name'
                    },
                    {
                        data: 'acquisition_cost',
                        name: 'acquisition_cost'
                    },
                    {
                        data: 'fund_name',
                        name: 'fund_name'
                    },
                    {
                        data: 'project_name',
                        name: 'project_name'
                    },
                    {
                        data: 'department_name',
                        name: 'department_name'
                    },
                    {
                        data: 'vendor_name',
                        name: 'vendor_name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'placed_in_service_date',
                        name: 'placed_in_service_date'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                pageLength: 25
            });

            // Handle checkbox changes
            $(document).on('change', '.asset-checkbox', function() {
                updateSelectedAssets();
            });
        });

        function applyFilters() {
            assetsTable.ajax.reload();
        }

        function clearFilters() {
            $('#filterForm')[0].reset();
            $('.select2bs4').val(null).trigger('change');
            assetsTable.ajax.reload();
        }

        function selectAll() {
            $('.asset-checkbox').prop('checked', true);
            updateSelectedAssets();
        }

        function selectNone() {
            $('.asset-checkbox').prop('checked', false);
            updateSelectedAssets();
        }

        function toggleSelectAll() {
            const isChecked = $('#selectAllCheckbox').prop('checked');
            $('.asset-checkbox').prop('checked', isChecked);
            updateSelectedAssets();
        }

        function updateSelectedAssets() {
            selectedAssets = [];
            $('.asset-checkbox:checked').each(function() {
                selectedAssets.push($(this).val());
            });

            $('#applyBtn').prop('disabled', selectedAssets.length === 0);
        }

        function previewUpdates() {
            if (selectedAssets.length === 0) {
                toastr.error('Please select at least one asset');
                return;
            }

            const formData = new FormData($('#bulkUpdateForm')[0]);
            formData.append('asset_ids', JSON.stringify(selectedAssets));

            $.ajax({
                url: '{{ route('assets.bulk-update.preview') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    displayPreview(response);
                    $('#previewModal').modal('show');
                },
                error: function(xhr) {
                    toastr.error('Failed to preview updates');
                }
            });
        }

        function displayPreview(previewData) {
            let html = '<div class="preview-summary mb-3">';
            html += '<h6>Summary: ' + previewData.length + ' assets will be updated</h6>';
            html += '</div>';

            previewData.forEach(function(asset) {
                html += '<div class="preview-item">';
                html += '<h6>' + asset.code + ' - ' + asset.name + '</h6>';

                Object.keys(asset.new_values).forEach(function(field) {
                    html += '<div class="preview-field">';
                    html += '<span class="field-name">' + field.replace('_', ' ').toUpperCase() +
                        ':</span> ';
                    html += '<span class="current-value">' + (asset.current_values[field] || 'Empty') +
                        '</span> ';
                    html += '→ <span class="new-value">' + asset.new_values[field] + '</span>';
                    html += '</div>';
                });

                html += '</div>';
            });

            $('#previewContent').html(html);
        }

        function applyUpdates() {
            if (selectedAssets.length === 0) {
                toastr.error('Please select at least one asset');
                return;
            }

            const formData = new FormData($('#bulkUpdateForm')[0]);
            formData.append('asset_ids', JSON.stringify(selectedAssets));

            $.ajax({
                url: '{{ route('assets.bulk-update') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success(response.message);
                    assetsTable.ajax.reload();
                    $('#bulkUpdateForm')[0].reset();
                    $('.select2bs4').val(null).trigger('change');
                    selectedAssets = [];
                    updateSelectedAssets();
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toastr.error(response.message || 'Failed to update assets');
                }
            });
        }

        function confirmUpdates() {
            $('#previewModal').modal('hide');
            applyUpdates();
        }
    </script>
@endpush
