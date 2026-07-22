@extends('layouts.main')

@section('title_page')
    Depreciation Schedule
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
    <li class="breadcrumb-item active">Depreciation Schedule</li>
@endsection

@section('content')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Depreciation Entries</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.depreciation-schedule', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.depreciation-schedule', array_merge(request()->query(), ['export' => 'excel'])) }}">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="category_id" class="form-control select2bs4">
                                    <option value="">All Categories</option>
                                    @foreach ($filterOptions['categories'] as $category)
                                        <option value="{{ $category->id }}"
                                            {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="asset_id" class="form-control select2bs4">
                                    <option value="">All Assets</option>
                                    @foreach ($filterOptions['assets'] as $asset)
                                        <option value="{{ $asset->id }}"
                                            {{ request('asset_id') == $asset->id ? 'selected' : '' }}>
                                            {{ $asset->code }} - {{ $asset->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    @foreach ($filterOptions['statuses']['depreciation_entries'] as $status)
                                        <option value="{{ $status }}"
                                            {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="month" name="period_from" class="form-control"
                                    value="{{ request('period_from') }}" placeholder="Period From">
                            </div>
                            <div class="col-md-2">
                                <input type="month" name="period_to" class="form-control"
                                    value="{{ request('period_to') }}" placeholder="Period To">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Asset Code</th>
                                    <th>Asset Name</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Book</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        <td>{{ $entry->period }}</td>
                                        <td>{{ $entry->asset_code }}</td>
                                        <td>{{ $entry->asset_name }}</td>
                                        <td>{{ $entry->category_name }}</td>
                                        <td class="text-right">Rp {{ number_format($entry->amount, 0, ',', '.') }}</td>
                                        <td>{{ ucfirst($entry->book ?? 'financial') }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ ($entry->entry_status ?? '') === 'posted' ? 'success' : 'warning' }}">
                                                {{ ucfirst($entry->entry_status ?? 'draft') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No depreciation entries found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($totals['count'] > 0)
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="4" class="text-right">Total ({{ $totals['count'] }} entries):</td>
                                        <td class="text-right">Rp {{ number_format($totals['amount'], 0, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    @if ($entries->hasPages())
                        <div class="mt-3">{{ $entries->links() }}</div>
                    @endif
                </div>
            </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2bs4').select2({ theme: 'bootstrap4', width: '100%' });
        });
    </script>
@endsection
