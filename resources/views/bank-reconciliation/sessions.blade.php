@extends('layouts.main')

@section('title_page')
    Reconciliation Sessions
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bank-reconciliation.index') }}">Rekening Koran</a></li>
    <li class="breadcrumb-item active">All Sessions</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Reconciliation Sessions</h4>
            <div class="d-flex" style="gap: 0.35rem;">
                <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-sm btn-secondary">Koran Grid</a>
                @can('bank_accounts.view')
                    <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary">Bank Accounts</a>
                @endcan
                @can('bank_reconciliation.import')
                    <a href="{{ route('bank-reconciliation.create') }}" class="btn btn-sm btn-primary">New Session</a>
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
                        <th>Period</th>
                        <th>Mode</th>
                        <th>Statement Closing</th>
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
                order: [[1, 'desc']],
                ajax: '{{ route('bank-reconciliation.data') }}',
                columns: [
                    { data: 'bank_name' },
                    { data: 'periode' },
                    { data: 'source_mode_label' },
                    { data: 'closing_balance_bank' },
                    { data: 'status_label', orderable: false },
                    { data: 'actions', orderable: false, searchable: false },
                ]
            });
        });
    </script>
@endpush
