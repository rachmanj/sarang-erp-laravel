@extends('layouts.main')

@section('title', 'Asset Summary Dashboard')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Summary Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('reports.assets.index') }}">Asset Reports</a></li>
                        <li class="breadcrumb-item active">Summary</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Key Metrics -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $summary['by_status']->sum('count') }}</h3>
                            <p>Total Assets</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-cube"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>Rp {{ number_format($summary['by_status']->sum('total_cost'), 0, ',', '.') }}</h3>
                            <p>Total Acquisition Cost</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>Rp {{ number_format($summary['depreciation']['total_depreciation'], 0, ',', '.') }}</h3>
                            <p>Accumulated Depreciation</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>Rp {{ number_format($summary['depreciation']['total_book_value'], 0, ',', '.') }}</h3>
                            <p>Total Book Value</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Assets by Status -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie"></i> Assets by Status
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-right">Count</th>
                                            <th class="text-right">Total Cost</th>
                                            <th class="text-right">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summary['by_status'] as $status)
                                            <tr>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $status->status == 'active' ? 'success' : ($status->status == 'inactive' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($status->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-right">{{ $status->count }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($status->total_cost, 0, ',', '.') }}</td>
                                                <td class="text-right">
                                                    {{ $summary['by_status']->sum('count') > 0 ? round(($status->count / $summary['by_status']->sum('count')) * 100, 1) : 0 }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assets by Category -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i> Assets by Category
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-right">Count</th>
                                            <th class="text-right">Total Cost</th>
                                            <th class="text-right">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summary['by_category'] as $category)
                                            <tr>
                                                <td>{{ $category->category_name }}</td>
                                                <td class="text-right">{{ $category->count }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format($category->total_cost, 0, ',', '.') }}</td>
                                                <td class="text-right">
                                                    {{ $summary['by_status']->sum('total_cost') > 0 ? round(($category->total_cost / $summary['by_status']->sum('total_cost')) * 100, 1) : 0 }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Depreciation Summary -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calculator"></i> Depreciation Summary
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-cube"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Depreciable Assets</span>
                                            <span
                                                class="info-box-number">{{ $summary['depreciation']['depreciable_assets'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Fully Depreciated</span>
                                            <span
                                                class="info-box-number">{{ $summary['depreciation']['fully_depreciated'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-chart-line"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Depreciation</span>
                                            <span class="info-box-number">Rp
                                                {{ number_format($summary['depreciation']['total_depreciation'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-book"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Book Value</span>
                                            <span class="info-box-number">Rp
                                                {{ number_format($summary['depreciation']['total_book_value'], 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history"></i> Recent Activity
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Recent Disposals</h5>
                                    @if ($summary['recent_disposals']->count() > 0)
                                        <ul class="list-unstyled">
                                            @foreach ($summary['recent_disposals']->take(5) as $disposal)
                                                <li class="mb-1">
                                                    <small
                                                        class="text-muted">{{ $disposal->disposal_date->format('d/m/Y') }}</small><br>
                                                    <strong>{{ $disposal->asset->code }}</strong> -
                                                    {{ $disposal->disposal_type_display }}
                                                    <span
                                                        class="badge badge-{{ $disposal->status == 'posted' ? 'success' : 'warning' }} badge-sm">
                                                        {{ ucfirst($disposal->status) }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-muted">No recent disposals</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h5>Recent Movements</h5>
                                    @if ($summary['recent_movements']->count() > 0)
                                        <ul class="list-unstyled">
                                            @foreach ($summary['recent_movements']->take(5) as $movement)
                                                <li class="mb-1">
                                                    <small
                                                        class="text-muted">{{ $movement->movement_date->format('d/m/Y') }}</small><br>
                                                    <strong>{{ $movement->asset->code }}</strong> -
                                                    {{ $movement->movement_type_display }}
                                                    <span
                                                        class="badge badge-{{ $movement->status == 'completed' ? 'success' : ($movement->status == 'approved' ? 'info' : 'warning') }} badge-sm">
                                                        {{ ucfirst($movement->status) }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-muted">No recent movements</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="{{ route('reports.assets.register') }}" class="btn btn-primary btn-block">
                                        <i class="fas fa-list-alt"></i> Asset Register
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('reports.assets.depreciation-schedule') }}"
                                        class="btn btn-success btn-block">
                                        <i class="fas fa-calculator"></i> Depreciation Schedule
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('reports.assets.disposal-summary') }}"
                                        class="btn btn-danger btn-block">
                                        <i class="fas fa-trash-alt"></i> Disposal Summary
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('reports.assets.movement-log') }}" class="btn btn-info btn-block">
                                        <i class="fas fa-exchange-alt"></i> Movement Log
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
