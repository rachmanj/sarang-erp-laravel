@extends('layouts.main')

@section('title', 'Asset Register Report')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Register Report</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
                        <li class="breadcrumb-item active">Asset Register</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Asset Register</h3>
                            <div class="card-tools">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm dropdown-toggle"
                                        data-toggle="dropdown">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item"
                                            href="{{ route('reports.assets.register', array_merge(request()->query(), ['export' => 'csv'])) }}">
                                            <i class="fas fa-file-csv"></i> Export CSV
                                        </a>
                                        <a class="dropdown-item"
                                            href="{{ route('reports.assets.register', array_merge(request()->query(), ['export' => 'excel'])) }}">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
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
                                        <select name="fund_id" class="form-control select2bs4">
                                            <option value="">All Funds</option>
                                            @foreach ($filterOptions['funds'] as $fund)
                                                <option value="{{ $fund->id }}"
                                                    {{ request('fund_id') == $fund->id ? 'selected' : '' }}>
                                                    {{ $fund->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="project_id" class="form-control select2bs4">
                                            <option value="">All Projects</option>
                                            @foreach ($filterOptions['projects'] as $project)
                                                <option value="{{ $project->id }}"
                                                    {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                                    {{ $project->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="department_id" class="form-control select2bs4">
                                            <option value="">All Departments</option>
                                            @foreach ($filterOptions['departments'] as $department)
                                                <option value="{{ $department->id }}"
                                                    {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="status" class="form-control select2bs4">
                                            <option value="">All Status</option>
                                            @foreach ($filterOptions['statuses']['assets'] as $status)
                                                <option value="{{ $status }}"
                                                    {{ request('status') == $status ? 'selected' : '' }}>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <input type="date" name="date_from" class="form-control"
                                            value="{{ request('date_from') }}" placeholder="From Date">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" name="date_to" class="form-control"
                                            value="{{ request('date_to') }}" placeholder="To Date">
                                    </div>
                                    <div class="col-md-3">
                                        <a href="{{ route('reports.assets.register') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Clear Filters
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <!-- Report Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Asset Code</th>
                                            <th>Asset Name</th>
                                            <th>Category</th>
                                            <th>Fund</th>
                                            <th>Project</th>
                                            <th>Department</th>
                                            <th>Acquisition Date</th>
                                            <th>Acquisition Cost</th>
                                            <th>Accumulated Depreciation</th>
                                            <th>Book Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($assets as $asset)
                                            <tr>
                                                <td>{{ $asset->code }}</td>
                                                <td>{{ $asset->name }}</td>
                                                <td>{{ $asset->category_name }}</td>
                                                <td>{{ $asset->fund_name ?? '-' }}</td>
                                                <td>{{ $asset->project_name ?? '-' }}</td>
                                                <td>{{ $asset->department_name ?? '-' }}</td>
                                                <td>{{ $asset->acquisition_date ? $asset->acquisition_date->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($asset->current_book_value, 0, ',', '.') }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $asset->status == 'active' ? 'success' : ($asset->status == 'inactive' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($asset->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center">No assets found matching the
                                                    criteria.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if ($assets->count() > 0)
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td colspan="7" class="text-right">Total:</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($assets->sum('acquisition_cost'), 0, ',', '.') }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($assets->sum('accumulated_depreciation'), 0, ',', '.') }}
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($assets->sum('current_book_value'), 0, ',', '.') }}
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>

                            <!-- Summary Statistics -->
                            @if ($assets->count() > 0)
                                <div class="row mt-4">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info">
                                                <i class="fas fa-cube"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Assets</span>
                                                <span class="info-box-number">{{ $assets->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success">
                                                <i class="fas fa-dollar-sign"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Acquisition Cost</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($assets->sum('acquisition_cost'), 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-chart-line"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Depreciation</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($assets->sum('accumulated_depreciation'), 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary">
                                                <i class="fas fa-book"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Book Value</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($assets->sum('current_book_value'), 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
        });
    </script>
@endsection
