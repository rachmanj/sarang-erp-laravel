@extends('layouts.main')

@section('title_page')
    Account Statements
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Account Statements</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        @can('account_statements.create')
                            <a href="{{ route('account-statements.create') }}" class="btn btn-primary btn-sm">Generate
                                Statement</a>
                        @endcan
                    </div>
                    <form class="form-inline" id="filters">
                        <select name="statement_type" class="form-control form-control-sm mr-1">
                            <option value="">Statement Type</option>
                            <option value="gl_account" {{ request('statement_type') == 'gl_account' ? 'selected' : '' }}>GL
                                Account</option>
                            <option value="business_partner"
                                {{ request('statement_type') == 'business_partner' ? 'selected' : '' }}>Business Partner
                            </option>
                        </select>
                        <select name="account_id" class="form-control form-control-sm mr-1">
                            <option value="">Account</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}"
                                    {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="business_partner_id" class="form-control form-control-sm mr-1">
                            <option value="">Business Partner</option>
                            @foreach ($businessPartners as $partner)
                                <option value="{{ $partner->id }}"
                                    {{ request('business_partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="">Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="finalized" {{ request('status') == 'finalized' ? 'selected' : '' }}>Finalized
                            </option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                            </option>
                        </select>
                        <input type="date" name="from_date" class="form-control form-control-sm mr-1" placeholder="From"
                            value="{{ request('from_date') }}">
                        <input type="date" name="to_date" class="form-control form-control-sm mr-1" placeholder="To"
                            value="{{ request('to_date') }}">
                        <button class="btn btn-sm btn-secondary" type="submit">Apply</button>
                        <a class="btn btn-sm btn-outline-secondary ml-1"
                            href="{{ route('account-statements.index') }}">Clear</a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-statements">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Statement No</th>
                                <th>Type</th>
                                <th>Account/Partner</th>
                                <th>Period</th>
                                <th>Opening Balance</th>
                                <th>Closing Balance</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($statements as $statement)
                                <tr>
                                    <td>{{ $statement->id }}</td>
                                    <td>{{ $statement->statement_no }}</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $statement->statement_type === 'gl_account' ? 'info' : 'success' }}">
                                            {{ ucfirst(str_replace('_', ' ', $statement->statement_type)) }}
                                        </span>
                                    </td>
                                    <td>{{ $statement->display_name }}</td>
                                    <td>{{ $statement->from_date->format('d/m/Y') }} -
                                        {{ $statement->to_date->format('d/m/Y') }}</td>
                                    <td class="text-right">{{ number_format($statement->opening_balance, 2) }}</td>
                                    <td class="text-right">{{ number_format($statement->closing_balance, 2) }}</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $statement->status === 'finalized' ? 'success' : ($statement->status === 'draft' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($statement->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $statement->creator->name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('account-statements.show', $statement) }}"
                                                class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if ($statement->status === 'draft')
                                                @can('account_statements.update')
                                                    <a href="{{ route('account-statements.edit', $statement) }}"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('account_statements.update')
                                                    <form method="POST"
                                                        action="{{ route('account-statements.finalize', $statement) }}"
                                                        class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm"
                                                            onclick="return confirm('Finalize this statement?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                            @if ($statement->status !== 'finalized')
                                                @can('account_statements.delete')
                                                    <form method="POST"
                                                        action="{{ route('account-statements.destroy', $statement) }}"
                                                        class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Delete this statement?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            $('#filters').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                window.location.href = '{{ route('account-statements.index') }}?' + formData;
            });
        });
    </script>
@endsection
