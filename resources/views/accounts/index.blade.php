@extends('layouts.main')

@section('title_page')
    Accounts
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Accounts</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="mb-0">Chart of Accounts</h4>
                @can('accounts.manage')
                    <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary">Create</a>
                @endcan
            </div>
            <div class="card card-primary card-outline mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('accounts.index') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="type">Account Type</label>
                                    <select name="type" id="type" class="form-control form-control-sm">
                                        <option value="">All Types</option>
                                        @foreach ($accountTypes as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ request('type') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                    @if (request('type'))
                                        <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-secondary">Clear</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @if (session('success'))
                <script>
                    toastr.success(@json(session('success')));
                </script>
            @endif
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Postable</th>
                        <th>Parent</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $a)
                        <tr>
                            <td>{{ $a->code }}</td>
                            <td>{{ $a->name }}</td>
                            <td>{{ strtoupper($a->type) }}</td>
                            <td>{{ $a->is_postable ? 'Yes' : 'No' }}</td>
                            <td>{{ optional(\DB::table('accounts')->find($a->parent_id))->code }}</td>
                            <td>
                                @can('accounts.manage')
                                    <a href="{{ route('accounts.edit', $a->id) }}" class="btn btn-xs btn-info">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>
                {{ $accounts->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#type').on('change', function() {
                $('#filterForm').submit();
            });
        });
    </script>
@endpush
