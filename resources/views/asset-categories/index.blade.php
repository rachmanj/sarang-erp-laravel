@extends('layouts.main')

@section('title', 'Asset Categories')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Categories</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Asset Categories</li>
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
                            <h3 class="card-title">Asset Categories Management</h3>
                            @can('asset_categories.manage')
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                        data-target="#categoryModal">
                                        <i class="fas fa-plus"></i> Add Category
                                    </button>
                                </div>
                            @endcan
                        </div>
                        <div class="card-body">
                            <table id="categoriesTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Account Mappings</th>
                                        <th>Depreciation Info</th>
                                        <th>Assets</th>
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

    @can('asset_categories.manage')
        <!-- Category Modal -->
        <div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form id="categoryForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="categoryModalLabel">Add Asset Category</h5>
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

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="life_months_default">Life (Months)</label>
                                        <input type="number" class="form-control" id="life_months_default"
                                            name="life_months_default" min="1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="method_default">Depreciation Method <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control select2bs4" id="method_default" name="method_default"
                                            required>
                                            <option value="straight_line">Straight Line</option>
                                            <option value="declining_balance">Declining Balance</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="salvage_value_policy">Salvage Value Policy</label>
                                        <input type="number" class="form-control" id="salvage_value_policy"
                                            name="salvage_value_policy" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="non_depreciable"
                                        name="non_depreciable" value="1">
                                    <label class="custom-control-label" for="non_depreciable">Non-Depreciable (e.g.,
                                        Land)</label>
                                </div>
                            </div>

                            <h6 class="text-primary">Account Mappings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="asset_account_id">Asset Account <span class="text-danger">*</span></label>
                                        <select class="form-control select2bs4" id="asset_account_id" name="asset_account_id"
                                            required>
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="accumulated_depreciation_account_id">Accumulated Depreciation Account <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control select2bs4" id="accumulated_depreciation_account_id"
                                            name="accumulated_depreciation_account_id" required>
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="depreciation_expense_account_id">Depreciation Expense Account <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control select2bs4" id="depreciation_expense_account_id"
                                            name="depreciation_expense_account_id" required>
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gain_on_disposal_account_id">Gain on Disposal Account</label>
                                        <select class="form-control select2bs4" id="gain_on_disposal_account_id"
                                            name="gain_on_disposal_account_id">
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="loss_on_disposal_account_id">Loss on Disposal Account</label>
                                        <select class="form-control select2bs4" id="loss_on_disposal_account_id"
                                            name="loss_on_disposal_account_id">
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox mt-4">
                                            <input type="checkbox" class="custom-control-input" id="is_active"
                                                name="is_active" value="1" checked>
                                            <label class="custom-control-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Category</button>
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
            var table = $('#categoriesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('asset-categories.data') }}",
                    type: 'GET'
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
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'account_mappings',
                        name: 'account_mappings',
                        orderable: false
                    },
                    {
                        data: 'depreciation_info',
                        name: 'depreciation_info',
                        orderable: false
                    },
                    {
                        data: 'asset_count',
                        name: 'asset_count',
                        orderable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
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
                pageLength: 25
            });

            // Load accounts for select dropdowns
            loadAccounts();

            // Form submission
            $('#categoryForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var url = "{{ route('asset-categories.store') }}";
                var method = 'POST';

                if ($('#categoryModalLabel').text() === 'Edit Asset Category') {
                    var categoryId = $('#categoryModal').data('category-id');
                    url = `/asset-categories/${categoryId}`;
                    method = 'POST';
                    formData.append('_method', 'PUT');
                }

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#categoryModal').modal('hide');
                            $('#categoryForm')[0].reset();
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
                            toastr.error('An error occurred while saving the category.');
                        }
                    }
                });
            });

            // Edit category
            $(document).on('click', '.edit-category', function() {
                var categoryId = $(this).data('id');
                var categoryData = JSON.parse($(this).data('category'));

                $('#categoryModalLabel').text('Edit Asset Category');
                $('#categoryModal').data('category-id', categoryId);

                // Populate form
                Object.keys(categoryData).forEach(function(key) {
                    var element = $(`#${key}`);
                    if (element.is(':checkbox')) {
                        element.prop('checked', categoryData[key]);
                    } else {
                        element.val(categoryData[key]);
                    }
                });

                $('#categoryModal').modal('show');
            });

            // Delete category
            $(document).on('click', '.delete-category', function() {
                var categoryId = $(this).data('id');
                var categoryName = $(this).data('name');

                if (confirm(`Are you sure you want to delete "${categoryName}"?`)) {
                    $.ajax({
                        url: `/asset-categories/${categoryId}`,
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
                                toastr.error('An error occurred while deleting the category.');
                            }
                        }
                    });
                }
            });

            // Reset modal when closed
            $('#categoryModal').on('hidden.bs.modal', function() {
                $('#categoryModalLabel').text('Add Asset Category');
                $('#categoryForm')[0].reset();
                $('#categoryModal').removeData('category-id');
            });

            function loadAccounts() {
                $.get('/asset-categories/accounts', function(accounts) {
                    var accountOptions = '<option value="">Select Account</option>';
                    accounts.forEach(function(account) {
                        accountOptions +=
                            `<option value="${account.id}">${account.code} - ${account.name}</option>`;
                    });

                    $('.select2bs4').each(function() {
                        if ($(this).find('option').length <= 1) {
                            $(this).html(accountOptions);
                        }
                    });

                    // Initialize Select2
                    $('.select2bs4').select2({
                        theme: 'bootstrap4',
                        dropdownParent: $('#categoryModal')
                    });
                });
            }
        });
    </script>
@endsection
