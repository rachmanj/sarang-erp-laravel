@extends('layouts.main')

@section('title_page')
    Disposal Summary
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
    <li class="breadcrumb-item active">Disposal Summary</li>
@endsection

@section('content')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Asset Disposals</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.disposal-summary', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('reports.assets.disposal-summary', array_merge(request()->query(), ['export' => 'excel'])) }}">
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
                                <select name="disposal_type" class="form-control">
                                    <option value="">All Types</option>
                                    @foreach ($filterOptions['disposal_types'] as $type)
                                        <option value="{{ $type }}"
                                            {{ request('disposal_type') == $type ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    @foreach ($filterOptions['statuses']['disposals'] as $status)
                                        <option value="{{ $status }}"
                                            {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
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
                                    <th>Disposal No</th>
                                    <th>Asset</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Proceeds</th>
                                    <th>Book Value</th>
                                    <th>Gain/Loss</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($disposals as $disposal)
                                    <tr>
                                        <td>{{ $disposal->disposal_no }}</td>
                                        <td>{{ $disposal->asset_code }} - {{ $disposal->asset_name }}</td>
                                        <td>{{ $disposal->category_name }}</td>
                                        <td>{{ $disposal->disposal_date ? $disposal->disposal_date->format('d/m/Y') : '-' }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $disposal->disposal_type)) }}</td>
                                        <td class="text-right">Rp {{ number_format($disposal->disposal_proceeds, 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($disposal->book_value_at_disposal, 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($disposal->gain_loss_amount, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $disposal->status === 'posted' ? 'success' : ($disposal->status === 'reversed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($disposal->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No disposals found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($totals['count'] > 0)
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="5" class="text-right">Total ({{ $totals['count'] }}):</td>
                                        <td class="text-right">Rp {{ number_format($totals['disposal_proceeds'], 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($totals['book_value_at_disposal'], 0, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($totals['gain_loss_amount'], 0, ',', '.') }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    @if ($disposals->hasPages())
                        <div class="mt-3">{{ $disposals->links() }}</div>
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
