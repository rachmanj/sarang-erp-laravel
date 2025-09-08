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
                                <th>Date</th>
                                <th>Description</th>
                                <th>Expense Account</th>
                                <th class="text-right">Amount</th>
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
                        data: 'date',
                        name: 'ce.date'
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
                        data: 'amount',
                        name: 'ce.amount',
                        className: 'text-right',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            });
        });
    </script>
@endpush
