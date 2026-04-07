@extends('layouts.main')

@section('title_page')
    Document Creation Logs
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Document Creation Logs</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Filters</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.document-creation-logs.index') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_from">Created from</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from"
                                        value="{{ $filters['date_from'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="date_to">Created to</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to"
                                        value="{{ $filters['date_to'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="document_type">Document type</label>
                                    <select class="form-control" id="document_type" name="document_type">
                                        <option value="">All types</option>
                                        @foreach ($documentTypes as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ ($filters['document_type'] ?? '') === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="supplier_id">Supplier</label>
                                    <select class="form-control" id="supplier_id" name="supplier_id">
                                        <option value="">All suppliers</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ ($filters['supplier_id'] ?? '') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="customer_id">Customer</label>
                                    <select class="form-control" id="customer_id" name="customer_id">
                                        <option value="">All customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Apply filters
                                    </button>
                                    <a href="{{ route('reports.document-creation-logs.index') }}"
                                        class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                    <p class="text-muted small mb-0">Rows are ordered by system record creation time (<code>created_at</code>),
                        newest first.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Documents</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Created at</th>
                                <th>Type</th>
                                <th>Document no.</th>
                                <th>Party</th>
                                <th>Closure</th>
                                <th>Created by</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paginator as $row)
                                <tr>
                                    <td>{{ $row['created_at']->timezone(config('app.timezone'))->format('d-M-Y H:i') }}
                                    </td>
                                    <td>{{ $row['document_type_label'] }}</td>
                                    <td>{{ $row['document_number'] }}</td>
                                    <td>{{ $row['party_name'] ?? '—' }}</td>
                                    <td>
                                        @if (!empty($row['closure_status']))
                                            <span
                                                class="badge badge-{{ $row['closure_status'] === 'open' ? 'warning' : 'secondary' }}">{{ $row['closure_status'] }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $row['creator_name'] ?? '—' }}</td>
                                    <td class="text-right">
                                        @if (!empty($row['url']))
                                            <a href="{{ $row['url'] }}" class="btn btn-sm btn-info">View</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No documents match the filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($paginator->total() > 0)
                    <div class="card-footer clearfix">
                        {{ $paginator->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
