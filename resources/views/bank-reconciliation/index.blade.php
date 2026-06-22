@extends('layouts.main')

@section('title_page')
    Bank Reconciliation
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Bank Reconciliation</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Reconciliation Sessions</h4>
            <div class="d-flex" style="gap: 0.35rem;">
                @can('bank_accounts.view')
                    <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary">Bank Accounts</a>
                @endcan
                @can('bank_reconciliation.import')
                    <a href="{{ route('bank-reconciliation.import') }}" class="btn btn-sm btn-primary">Import Statement</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <script>
                    toastr.success(@json(session('success')));
                </script>
            @endif
            <table class="table table-striped table-sm" id="recon-table">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th>Period Start</th>
                        <th>Period End</th>
                        <th>Statement Closing</th>
                        <th>Book Balance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#recon-table').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [1, 'desc']
                ],
                ajax: '{{ route('bank-reconciliation.data') }}',
                columns: [{
                        data: 'bank_name'
                    },
                    {
                        data: 'period_start'
                    },
                    {
                        data: 'period_end'
                    },
                    {
                        data: 'statement_closing'
                    },
                    {
                        data: 'book_balance'
                    },
                    {
                        data: 'status_label',
                        orderable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });
    </script>
@endpush
