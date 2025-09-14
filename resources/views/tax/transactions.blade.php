@extends('layouts.app')

@section('title', 'Tax Transactions')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tax Transactions</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                        <li class="breadcrumb-item active">Transactions</li>
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
                            <h3 class="card-title">
                                <i class="fas fa-receipt mr-2"></i>
                                Tax Transactions Management
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('tax.transactions.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i>
                                    New Transaction
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="start_date"
                                        placeholder="Start Date">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="end_date"
                                        placeholder="End Date">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control form-control-sm" id="tax_type">
                                        <option value="">All Tax Types</option>
                                        <option value="ppn">PPN</option>
                                        <option value="pph_21">PPh 21</option>
                                        <option value="pph_22">PPh 22</option>
                                        <option value="pph_23">PPh 23</option>
                                        <option value="pph_26">PPh 26</option>
                                        <option value="pph_4_2">PPh 4(2)</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control form-control-sm" id="tax_category">
                                        <option value="">All Categories</option>
                                        <option value="input">Input</option>
                                        <option value="output">Output</option>
                                        <option value="withholding">Withholding</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-control form-control-sm" id="status">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="paid">Paid</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary btn-sm" id="filter-btn">
                                        <i class="fas fa-filter mr-1"></i>
                                        Filter
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="reset-btn">
                                        <i class="fas fa-undo mr-1"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Search -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="search"
                                            placeholder="Search by transaction number, tax name, or tax number...">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <button type="button" class="btn btn-success btn-sm" id="export-btn">
                                        <i class="fas fa-download mr-1"></i>
                                        Export CSV
                                    </button>
                                </div>
                            </div>

                            <!-- DataTable -->
                            <div class="table-responsive">
                                <table id="transactions-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Transaction No</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Tax Type</th>
                                            <th>Category</th>
                                            <th>Tax Name</th>
                                            <th>Tax Number</th>
                                            <th>Taxable Amount</th>
                                            <th>Tax Rate</th>
                                            <th>Tax Amount</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark as Paid</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="payment-form">
                    <div class="modal-body">
                        <input type="hidden" id="transaction_id">
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <input type="text" class="form-control" id="payment_method"
                                placeholder="e.g., Bank Transfer, Cash">
                        </div>
                        <div class="form-group">
                            <label for="payment_reference">Payment Reference</label>
                            <input type="text" class="form-control" id="payment_reference"
                                placeholder="e.g., Receipt number, Transaction ID">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Mark as Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#transactions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('tax.transactions.data') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.tax_type = $('#tax_type').val();
                        d.tax_category = $('#tax_category').val();
                        d.status = $('#status').val();
                        d.search = $('#search').val();
                    }
                },
                columns: [{
                        data: 'transaction_no',
                        name: 'transaction_no'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'transaction_type',
                        name: 'transaction_type'
                    },
                    {
                        data: 'tax_type',
                        name: 'tax_type'
                    },
                    {
                        data: 'tax_category',
                        name: 'tax_category'
                    },
                    {
                        data: 'tax_name',
                        name: 'tax_name'
                    },
                    {
                        data: 'tax_number',
                        name: 'tax_number'
                    },
                    {
                        data: 'taxable_amount',
                        name: 'taxable_amount',
                        render: function(data) {
                            return 'Rp ' + parseFloat(data).toLocaleString('id-ID', {
                                minimumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'tax_rate',
                        name: 'tax_rate',
                        render: function(data) {
                            return parseFloat(data).toFixed(2) + '%';
                        }
                    },
                    {
                        data: 'tax_amount',
                        name: 'tax_amount',
                        render: function(data) {
                            return 'Rp ' + parseFloat(data).toLocaleString('id-ID', {
                                minimumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        render: function(data) {
                            return 'Rp ' + parseFloat(data).toLocaleString('id-ID', {
                                minimumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data) {
                            var statusClass = {
                                'pending': 'warning',
                                'approved': 'info',
                                'paid': 'success',
                                'refunded': 'danger'
                            };
                            return '<span class="badge badge-' + (statusClass[data] ||
                                'secondary') + '">' + data.charAt(0).toUpperCase() + data.slice(1) +
                                '</span>';
                        }
                    },
                    {
                        data: 'due_date',
                        name: 'due_date'
                    },
                    {
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            var actions = '<div class="btn-group">';
                            actions += '<a href="/tax/transactions/' + data +
                                '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>';

                            if (row.status === 'pending' || row.status === 'approved') {
                                actions +=
                                    '<button class="btn btn-success btn-sm mark-paid-btn" data-id="' +
                                    data + '"><i class="fas fa-check"></i></button>';
                            }

                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 25,
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Filter button
            $('#filter-btn').click(function() {
                table.ajax.reload();
            });

            // Reset button
            $('#reset-btn').click(function() {
                $('#start_date, #end_date, #tax_type, #tax_category, #status, #search').val('');
                table.ajax.reload();
            });

            // Search button
            $('#search-btn').click(function() {
                table.ajax.reload();
            });

            // Search on Enter key
            $('#search').keypress(function(e) {
                if (e.which == 13) {
                    table.ajax.reload();
                }
            });

            // Export button
            $('#export-btn').click(function() {
                var params = new URLSearchParams();
                if ($('#start_date').val()) params.append('start_date', $('#start_date').val());
                if ($('#end_date').val()) params.append('end_date', $('#end_date').val());
                if ($('#tax_type').val()) params.append('tax_type', $('#tax_type').val());
                if ($('#tax_category').val()) params.append('tax_category', $('#tax_category').val());
                if ($('#status').val()) params.append('status', $('#status').val());

                window.location.href = "{{ route('tax.transactions.export') }}?" + params.toString();
            });

            // Mark as paid button
            $(document).on('click', '.mark-paid-btn', function() {
                var transactionId = $(this).data('id');
                $('#transaction_id').val(transactionId);
                $('#paymentModal').modal('show');
            });

            // Payment form submission
            $('#payment-form').submit(function(e) {
                e.preventDefault();

                var transactionId = $('#transaction_id').val();
                var paymentMethod = $('#payment_method').val();
                var paymentReference = $('#payment_reference').val();

                $.ajax({
                    url: '/tax/transactions/' + transactionId + '/mark-paid',
                    method: 'POST',
                    data: {
                        payment_method: paymentMethod,
                        payment_reference: paymentReference,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#paymentModal').modal('hide');
                        table.ajax.reload();
                        toastr.success('Transaction marked as paid successfully');
                    },
                    error: function(xhr) {
                        toastr.error('Error updating transaction: ' + xhr.responseJSON.message);
                    }
                });
            });
        });
    </script>
@endpush
