@extends('layouts.main')

@section('title_page')
    Cash Expenses
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Cash Expenses</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">List</h4>
                    <div class="d-flex align-items-center flex-wrap" style="gap: 0.35rem;">
                        <div class="input-group input-group-sm" style="width: 240px;">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                            </div>
                            <input type="text" id="filter_date_range" class="form-control" placeholder="Date range"
                                autocomplete="off" readonly>
                        </div>
                        <input type="hidden" id="filter_from">
                        <input type="hidden" id="filter_to">
                        <button type="button" id="apply_filters" class="btn btn-sm btn-info">Apply</button>
                        <a href="{{ route('cash-expenses.create') }}" class="btn btn-sm btn-primary">New Expense</a>
                    </div>
                </div>

                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif

                <div class="card-body">
                    <table class="table table-striped table-sm mb-0" id="ce-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Expense Account</th>
                                <th>Account Name</th>
                                <th>Cash Account</th>
                                <th>Creator</th>
                                <th class="text-right">Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(function() {
            function syncDateRangeInputs(start, end) {
                $('#filter_from').val(start.format('YYYY-MM-DD'));
                $('#filter_to').val(end.format('YYYY-MM-DD'));
                $('#filter_date_range').val(
                    start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY')
                );
            }

            function clearDateRangeInputs() {
                $('#filter_from').val('');
                $('#filter_to').val('');
                $('#filter_date_range').val('');
            }

            $('#filter_date_range').daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY',
                    separator: ' - ',
                    applyLabel: 'Apply',
                    cancelLabel: 'Clear',
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [
                        moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month'),
                    ],
                },
            });

            const table = $('#ce-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('cash-expenses.data') }}',
                    data: function(d) {
                        d.from = $('#filter_from').val();
                        d.to = $('#filter_to').val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'date',
                        name: 'ce.date',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                const date = new Date(data);
                                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                                ];
                                const day = String(date.getDate()).padStart(2, '0');
                                const month = months[date.getMonth()];
                                const year = date.getFullYear();
                                return `${day}-${month}-${year}`;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'description',
                        name: 'ce.description'
                    },
                    {
                        data: 'expense_code',
                        name: 'a.code'
                    },
                    {
                        data: 'expense_name',
                        name: 'a.name'
                    },
                    {
                        data: 'cash_account',
                        name: 'cash_account',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'creator_name',
                        name: 'u.name'
                    },
                    {
                        data: 'amount',
                        name: 'ce.amount',
                        className: 'text-right',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return parseFloat(data).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                            return data;
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return '<a href="/cash-expenses/' + row.id +
                                '/print" target="_blank" class="btn btn-sm btn-info" title="Print"><i class="fas fa-print"></i></a>';
                        }
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            $('#apply_filters').on('click', function() {
                table.ajax.reload();
            });

            $('#filter_date_range').on('apply.daterangepicker', function(ev, picker) {
                syncDateRangeInputs(picker.startDate, picker.endDate);
                table.ajax.reload();
            });

            $('#filter_date_range').on('cancel.daterangepicker', function() {
                clearDateRangeInputs();
                table.ajax.reload();
            });
        });
    </script>
@endpush
