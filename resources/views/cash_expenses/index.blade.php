@extends('layouts.main')

@section('title_page')
    Cash Expenses
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Cash Expenses</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List</h4>
                    <a href="{{ route('cash-expenses.create') }}" class="btn btn-sm btn-primary float-right">New Expense</a>
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
    <script>
        $(function() {
            $('#ce-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('cash-expenses.data') }}',
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
        });
    </script>
@endpush
