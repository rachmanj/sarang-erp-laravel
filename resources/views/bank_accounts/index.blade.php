@extends('layouts.main')

@section('title_page')
    Bank Accounts
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Bank Accounts</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Bank Accounts</h4>
            @can('bank_accounts.manage')
                <a href="{{ route('bank-accounts.create') }}" class="btn btn-sm btn-primary">Create</a>
            @endcan
        </div>
        <div class="card-body">
            @if (session('success'))
                <script>
                    toastr.success(@json(session('success')));
                </script>
            @endif
            <table class="table table-striped table-sm" id="bank-accounts-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Bank</th>
                        <th>Account No.</th>
                        <th>COA Code</th>
                        <th>COA Name</th>
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
            $('#bank-accounts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('bank-accounts.data') }}',
                columns: [{
                        data: 'code'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'bank_name'
                    },
                    {
                        data: 'account_number'
                    },
                    {
                        data: 'coa_code'
                    },
                    {
                        data: 'coa_name'
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
