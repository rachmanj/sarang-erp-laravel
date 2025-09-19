@extends('layouts.main')

@section('title', 'Assets')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Assets</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Assets</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Assets Management</h3>
                            @can('assets.create')
                                <div class="card-tools">
                                    <a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Asset
                                    </a>
                                </div>
                            @endcan
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <select class="form-control select2bs4" id="categoryFilter">
                                        <option value="">All Categories</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control select2bs4" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="retired">Retired</option>
                                        <option value="disposed">Disposed</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control select2bs4" id="fundFilter">
                                        <option value="">All Funds</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control select2bs4" id="projectFilter">
                                        <option value="">All Projects</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control select2bs4" id="departmentFilter">
                                        <option value="">All Departments</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-secondary btn-sm" id="clearFilters">
                                        <i class="fas fa-times"></i> Clear Filters
                                    </button>
                                </div>
                            </div>

                            <table id="assetsTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Acquisition Cost</th>
                                        <th>Book Value</th>
                                        <th>Accumulated Depreciation</th>
                                        <th>Depreciation Info</th>
                                        <th>Dimensions</th>
                                        <th>Vendor</th>
                                        <th>Placed in Service</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @can('assets.update')
        <!-- Asset Edit Modal -->
        <div class="modal fade" id="assetModal" tabindex="-1" role="dialog" aria-labelledby="assetModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="assetForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="assetModalLabel">Edit Asset</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="code">Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="code" name="code" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="serial_number">Serial Number</label>
                                        <input type="text" class="form-control" id="serial_number" name="serial_number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id">Category <span class="text-danger">*</span></label>
                                        <select class="form-control select2bs4" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="acquisition_cost">Acquisition Cost <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="acquisition_cost"
                                                name="acquisition_cost" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="salvage_value">Salvage Value</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="salvage_value"
                                                name="salvage_value" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="method">Depreciation Method <span class="text-danger">*</span></label>
                                        <select class="form-control select2bs4" id="method" name="method" required>
                                            <option value="straight_line">Straight Line</option>
                                            <option value="declining_balance">Declining Balance</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="life_months">Life (Months) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="life_months" name="life_months"
                                            min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="placed_in_service_date">Placed in Service Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="placed_in_service_date"
                                            name="placed_in_service_date" required>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-primary">Dimensions</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fund_id">Fund</label>
                                        <select class="form-control select2bs4" id="fund_id" name="fund_id">
                                            <option value="">Select Fund</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="project_id">Project</label>
                                        <select class="form-control select2bs4" id="project_id" name="project_id">
                                            <option value="">Select Project</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="department_id">Department</label>
                                        <select class="form-control select2bs4" id="department_id" name="department_id">
                                            <option value="">Select Department</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="business_partner_id">Vendor</label>
                                        <select class="form-control select2bs4" id="business_partner_id"
                                            name="business_partner_id">
                                            <option value="">Select Vendor</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Asset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#assetsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('assets.data') }}",
                    type: 'GET',
                    data: function(d) {
                        d.category_id = $('#categoryFilter').val();
                        d.status = $('#statusFilter').val();
                        d.fund_id = $('#fundFilter').val();
                        d.project_id = $('#projectFilter').val();
                        d.department_id = $('#departmentFilter').val();
                    }
                },
                columns: [{
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
                        data: 'acquisition_cost_formatted',
                        name: 'acquisition_cost_formatted'
                    },
                    {
                        data: 'current_book_value_formatted',
                        name: 'current_book_value_formatted'
                    },
                    {
                        data: 'accumulated_depreciation_formatted',
                        name: 'accumulated_depreciation_formatted'
                    },
                    {
                        data: 'depreciation_info',
                        name: 'depreciation_info',
                        orderable: false
                    },
                    {
                        data: 'dimensions',
                        name: 'dimensions',
                        orderable: false
                    },
                    {
                        data: 'vendor_name',
                        name: 'vendor_name'
                    },
                    {
                        data: 'placed_in_service_formatted',
                        name: 'placed_in_service_formatted'
                    },
                    {
                        data: 'status_badge',
                        name: 'status_badge',
                        orderable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                order: [
                    [0, 'asc']
                ]
            });

            // Load filter data
            loadFilterData();

            // Filter change events
            $('#categoryFilter, #statusFilter, #fundFilter, #projectFilter, #departmentFilter').on('change',
                function() {
                    table.ajax.reload();
                });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#categoryFilter, #statusFilter, #fundFilter, #projectFilter, #departmentFilter').val('')
                    .trigger('change');
                $('.select2bs4').val('').trigger('change');
                table.ajax.reload();
            });

            // Form submission for edit
            $('#assetForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var assetId = $('#assetModal').data('asset-id');
                var url = `/assets/${assetId}`;

                formData.append('_method', 'PUT');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Asset updated successfully.');
                            $('#assetModal').modal('hide');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                        } else {
                            toastr.error('An error occurred while updating the asset.');
                        }
                    }
                });
            });

            // Edit asset
            $(document).on('click', '.edit-asset', function() {
                var assetId = $(this).data('id');
                var assetData = JSON.parse($(this).data('asset'));

                $('#assetModalLabel').text('Edit Asset');
                $('#assetModal').data('asset-id', assetId);

                // Populate form
                Object.keys(assetData).forEach(function(key) {
                    var element = $(`#${key}`);
                    if (element.length) {
                        element.val(assetData[key]);
                    }
                });

                // Initialize Select2 for modal
                $('.select2bs4').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#assetModal')
                });

                $('#assetModal').modal('show');
            });

            // Delete asset
            $(document).on('click', '.delete-asset', function() {
                var assetId = $(this).data('id');
                var assetName = $(this).data('name');

                if (confirm(`Are you sure you want to delete "${assetName}"?`)) {
                    $.ajax({
                        url: `/assets/${assetId}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                table.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error('An error occurred while deleting the asset.');
                            }
                        }
                    });
                }
            });

            // Reset modal when closed
            $('#assetModal').on('hidden.bs.modal', function() {
                $('#assetModalLabel').text('Edit Asset');
                $('#assetForm')[0].reset();
                $('#assetModal').removeData('asset-id');
            });

            function loadFilterData() {
                // Load categories
                $.get('/assets/categories', function(categories) {
                    var categoryOptions = '<option value="">All Categories</option>';
                    categories.forEach(function(category) {
                        categoryOptions +=
                            `<option value="${category.id}">${category.name}</option>`;
                    });
                    $('#categoryFilter').html(categoryOptions);
                });

                // Load funds
                $.get('/assets/funds', function(funds) {
                    var fundOptions = '<option value="">All Funds</option>';
                    funds.forEach(function(fund) {
                        fundOptions += `<option value="${fund.id}">${fund.name}</option>`;
                    });
                    $('#fundFilter').html(fundOptions);
                });

                // Load projects
                $.get('/assets/projects', function(projects) {
                    var projectOptions = '<option value="">All Projects</option>';
                    projects.forEach(function(project) {
                        projectOptions += `<option value="${project.id}">${project.name}</option>`;
                    });
                    $('#projectFilter').html(projectOptions);
                });

                // Load departments
                $.get('/assets/departments', function(departments) {
                    var departmentOptions = '<option value="">All Departments</option>';
                    departments.forEach(function(department) {
                        departmentOptions +=
                            `<option value="${department.id}">${department.name}</option>`;
                    });
                    $('#departmentFilter').html(departmentOptions);
                });

                // Load data for modal dropdowns
                loadModalData();
            }

            function loadModalData() {
                // Load categories for modal
                $.get('/assets/categories', function(categories) {
                    var categoryOptions = '<option value="">Select Category</option>';
                    categories.forEach(function(category) {
                        categoryOptions +=
                            `<option value="${category.id}">${category.name}</option>`;
                    });
                    $('#category_id').html(categoryOptions);
                });

                // Load funds for modal
                $.get('/assets/funds', function(funds) {
                    var fundOptions = '<option value="">Select Fund</option>';
                    funds.forEach(function(fund) {
                        fundOptions += `<option value="${fund.id}">${fund.name}</option>`;
                    });
                    $('#fund_id').html(fundOptions);
                });

                // Load projects for modal
                $.get('/assets/projects', function(projects) {
                    var projectOptions = '<option value="">Select Project</option>';
                    projects.forEach(function(project) {
                        projectOptions += `<option value="${project.id}">${project.name}</option>`;
                    });
                    $('#project_id').html(projectOptions);
                });

                // Load departments for modal
                $.get('/assets/departments', function(departments) {
                    var departmentOptions = '<option value="">Select Department</option>';
                    departments.forEach(function(department) {
                        departmentOptions +=
                            `<option value="${department.id}">${department.name}</option>`;
                    });
                    $('#department_id').html(departmentOptions);
                });

                // Load vendors for modal
                $.get('/assets/vendors', function(vendors) {
                    var vendorOptions = '<option value="">Select Vendor</option>';
                    vendors.forEach(function(vendor) {
                        vendorOptions += `<option value="${vendor.id}">${vendor.name}</option>`;
                    });
                    $('#business_partner_id').html(vendorOptions);
                });

                // Initialize Select2 for filters
                $('.select2bs4').select2({
                    theme: 'bootstrap4'
                });
            }
        });
    </script>
@endsection
