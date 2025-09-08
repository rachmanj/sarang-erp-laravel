@extends('layouts.main')

@section('title_page')
    Invoices Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Invoices</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoices List</h3>
                            <div class="card-tools">
                                <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Add New Invoice
                                </a>
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- Advanced Search Panel -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card card-outline card-info search-card">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-search"></i> Advanced Search
                                            </h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_invoice_number">Invoice Number</label>
                                                        <input type="text" class="form-control"
                                                            id="search_invoice_number"
                                                            placeholder="Search by invoice number">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_supplier">Supplier</label>
                                                        <select class="form-control select2bs4" id="search_supplier">
                                                            <option value="">All Suppliers</option>
                                                            @foreach ($suppliers as $supplier)
                                                                <option value="{{ $supplier->name }}">
                                                                    {{ $supplier->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_po_no">PO Number</label>
                                                        <input type="text" class="form-control" id="search_po_no"
                                                            placeholder="Search by PO number">
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_type">Invoice Type</label>
                                                        <select class="form-control" id="search_type">
                                                            <option value="">All Types</option>
                                                            @foreach ($invoiceTypes as $type)
                                                                <option value="{{ $type->type_name }}">
                                                                    {{ $type->type_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_status">Status</label>
                                                        <select class="form-control" id="search_status">
                                                            <option value="">All Status</option>
                                                            <option value="open">Open</option>
                                                            <option value="verify">Verify</option>
                                                            <option value="return">Return</option>
                                                            <option value="sap">SAP</option>
                                                            <option value="close">Close</option>
                                                            <option value="cancel">Cancel</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label for="search_invoice_project">Invoice Project</label>
                                                        <select class="form-control" id="search_invoice_project">
                                                            <option value="">All Projects</option>
                                                            @foreach ($projects as $project)
                                                                <option value="{{ $project->code }}">
                                                                    {{ $project->code }} - {{ $project->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                @can('see-all-record-switch')
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="show_all_records">
                                                                <input type="checkbox" id="show_all_records"
                                                                    data-bootstrap-switch>
                                                                Show All Records
                                                            </label>
                                                            <small class="form-text text-muted">
                                                                Toggle to view all invoices across all locations
                                                            </small>
                                                        </div>
                                                    </div>
                                                @endcan
                                                <div class="col-md-6 text-right">
                                                    <button type="button" class="btn btn-info" id="apply_search">
                                                        <i class="fas fa-search"></i> Apply Search
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="clear_search">
                                                        <i class="fas fa-times"></i> Clear Search
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table id="invoices-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Invoice #</th>
                                        <th>Supplier</th>
                                        <th>Type</th>
                                        <th>Invoice Date</th>
                                        <th>Receive Date</th>
                                        <th>PO Number</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Current Location</th>
                                        <th>Days</th>
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
    </div>
@endsection

@section('styles')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
    <!-- Bootstrap Switch -->
    <link rel="stylesheet"
        href="{{ asset('adminlte/plugins/bootstrap-switch/css/bootstrap3/bootstrap-switch.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">


    <style>
        /* Start search card in collapsed state */
        .search-card .card-body {
            display: none;
        }

        .search-card.collapsed .card-body {
            display: block;
        }

        /* Days column badge styling */
        .badge.badge-success {
            background-color: #28a745;
        }

        .badge.badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge.badge-danger {
            background-color: #dc3545;
        }

        .badge.badge-info {
            background-color: #17a2b8;
        }
    </style>
@endsection

@section('scripts')
    <!-- DataTables -->
    <script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
    <!-- Bootstrap Switch -->
    <script src="{{ asset('adminlte/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Toastr
            if (typeof toastr !== 'undefined') {
                toastr.options = {
                    "closeButton": true,
                    "debug": false,
                    "newestOnTop": false,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                };

                // Show session messages if exists
                @if (session('success'))
                    toastr.success('{{ session('success') }}');
                @endif

                @if (session('error'))
                    toastr.error('{{ session('error') }}');
                @endif

                @if (session('warning'))
                    toastr.warning('{{ session('warning') }}');
                @endif

                @if (session('info'))
                    toastr.info('{{ session('info') }}');
                @endif
            } else {
                console.error('Toastr not loaded');
            }

            // Initialize Bootstrap Switch
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch();
            });

            // Initialize Select2 for supplier search
            $('#search_supplier').select2({
                theme: 'bootstrap4',
                placeholder: 'All Suppliers',
                allowClear: true,
                width: '100%'
            });

            // Initialize DataTable
            var table = $('#invoices-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('invoices.data') }}',
                    data: function(d) {
                        d.show_all = $('#show_all_records').length > 0 && $('#show_all_records').is(
                            ':checked') ? 1 : 0;
                        d.search_supplier = $('#search_supplier').val();
                        d.search_invoice_project = $('#search_invoice_project').val();
                    }
                },
                columns: [{
                        data: null,
                        name: 'index',
                        orderable: false,
                        searchable: false,
                        width: '50px',
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'invoice_number',
                        name: 'invoice_number'
                    },
                    {
                        data: 'supplier_name',
                        name: 'supplier_name'
                    },
                    {
                        data: 'type_name',
                        name: 'type_name'
                    },
                    {
                        data: 'formatted_invoice_date',
                        name: 'invoice_date'
                    },
                    {
                        data: 'formatted_receive_date',
                        name: 'receive_date'
                    },
                    {
                        data: 'po_no',
                        name: 'po_no'
                    },
                    {
                        data: 'formatted_amount',
                        name: 'amount'
                    },
                    {
                        data: 'status_badge',
                        name: 'status'
                    },
                    {
                        data: 'cur_loc',
                        name: 'cur_loc'
                    },
                    {
                        data: 'days_difference',
                        name: 'days_difference'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [4, 'desc']
                ],
                pageLength: 25,
                responsive: true
            });

            // Apply search
            $('#apply_search').click(function() {
                table.ajax.reload();
            });

            // Clear search
            $('#clear_search').click(function() {
                $('#search_invoice_number').val('');
                $('#search_supplier').val('').trigger('change');
                $('#search_po_no').val('');
                $('#search_type').val('');
                $('#search_status').val('');
                $('#search_invoice_project').val('');
                if ($('#show_all_records').length > 0) {
                    $('#show_all_records').bootstrapSwitch('state', false);
                }
                table.search('').columns().search('').draw();
            });

            // Custom search functionality
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var invoiceNumber = $('#search_invoice_number').val().toLowerCase();
                var supplier = $('#search_supplier').val().toLowerCase();
                var poNo = $('#search_po_no').val().toLowerCase();
                var type = $('#search_type').val();
                var status = $('#search_status').val();
                var invoiceProject = $('#search_invoice_project').val();

                // Invoice number filter (now at index 1 due to index column)
                if (invoiceNumber && data[1].toLowerCase().indexOf(invoiceNumber) === -1) {
                    return false;
                }

                // Supplier filter (now at index 2 due to index column)
                if (supplier && data[2].toLowerCase().indexOf(supplier) === -1) {
                    return false;
                }

                // PO Number filter (now at index 6 due to index column)
                if (poNo && data[6].toLowerCase().indexOf(poNo) === -1) {
                    return false;
                }

                // Type filter (now at index 3 due to index column)
                if (type && data[3] !== type) {
                    return false;
                }

                // Status filter (now at index 8 due to index column)
                if (status && data[8].indexOf(status) === -1) {
                    return false;
                }

                return true;
            });

            // Toggle show all records
            if ($('#show_all_records').length > 0) {
                $('#show_all_records').on('switchChange.bootstrapSwitch', function() {
                    table.ajax.reload();
                });
            }

            // Handle search card collapse
            $('.search-card .card-tools button').click(function() {
                var card = $(this).closest('.search-card');
                var cardBody = card.find('.card-body');
                var icon = $(this).find('i');

                if (cardBody.is(':visible')) {
                    cardBody.slideUp();
                    icon.removeClass('fa-minus').addClass('fa-plus');
                } else {
                    cardBody.slideDown();
                    icon.removeClass('fa-plus').addClass('fa-minus');
                }
            });

            // Edit now handled by direct link in actions (no modal)

            // Delete invoice (delegated for DataTables-rendered rows)
            $('#invoices-table').on('click', '.delete-invoice', function() {
                var invoiceId = $(this).data('id');
                var invoiceNumber = $(this).data('number');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete invoice "${invoiceNumber}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var deleteUrl = $(this).data('delete-url') || ('/invoices/' + invoiceId);
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    table.ajax.reload(null, false);
                                    // Use toastr for consistency with other operations
                                    toastr.success('Invoice deleted successfully.');
                                } else {
                                    toastr.error(response.message ||
                                        'Failed to delete invoice.');
                                }
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while deleting the invoice.');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
