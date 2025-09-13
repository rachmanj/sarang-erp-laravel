@extends('layouts.app')

@section('title', 'Asset Data Quality Report')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Asset Data Quality Report</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                            <li class="breadcrumb-item active">Data Quality</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Data Quality Score -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line"></i> Data Quality Score
                                </h3>
                                <div class="card-tools">
                                    <button class="btn btn-success btn-sm" onclick="exportReport('csv')">
                                        <i class="fas fa-download"></i> Export CSV
                                    </button>
                                    <button class="btn btn-info btn-sm" onclick="exportReport('json')">
                                        <i class="fas fa-download"></i> Export JSON
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary">
                                                <i class="fas fa-percentage"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Quality Score</span>
                                                <span class="info-box-number">{{ $score }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info">
                                                <i class="fas fa-cube"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Assets</span>
                                                <span
                                                    class="info-box-number">{{ $report['summary']['total_assets'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Issues</span>
                                                <span
                                                    class="info-box-number">{{ $report['summary']['total_issues'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Clean Assets</span>
                                                <span
                                                    class="info-box-number">{{ $report['summary']['total_assets'] - $report['summary']['total_issues'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Issue Categories -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-copy"></i> Duplicate Issues
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-tags"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Duplicate Names</span>
                                                <span
                                                    class="info-box-number">{{ $report['duplicates']['duplicate_names'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-barcode"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Duplicate Serials</span>
                                                <span
                                                    class="info-box-number">{{ $report['duplicates']['duplicate_serials'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-hashtag"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Duplicate Codes</span>
                                                <span
                                                    class="info-box-number">{{ $report['duplicates']['duplicate_codes'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('assets.data-quality.duplicates') }}" class="btn btn-danger btn-sm">
                                        <i class="fas fa-eye"></i> View Duplicates
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-exclamation-circle"></i> Incomplete Data
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-file-alt"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Missing Description</span>
                                                <span
                                                    class="info-box-number">{{ $report['incomplete_data']['missing_description'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-barcode"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Missing Serial</span>
                                                <span
                                                    class="info-box-number">{{ $report['incomplete_data']['missing_serial_number'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-calendar"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Missing Service Date</span>
                                                <span
                                                    class="info-box-number">{{ $report['incomplete_data']['missing_service_date'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning">
                                                <i class="fas fa-building"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Missing Vendor</span>
                                                <span
                                                    class="info-box-number">{{ $report['incomplete_data']['missing_vendor'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('assets.data-quality.incomplete') }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="fas fa-eye"></i> View Incomplete Data
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Issues -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-balance-scale"></i> Consistency Issues
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-minus"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Negative Values</span>
                                                <span
                                                    class="info-box-number">{{ $report['consistency_issues']['negative_values'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-clock"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Invalid Life Months</span>
                                                <span
                                                    class="info-box-number">{{ $report['consistency_issues']['invalid_life_months'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-danger">
                                                <i class="fas fa-calendar-plus"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Future Service Dates</span>
                                                <span
                                                    class="info-box-number">{{ $report['consistency_issues']['future_service_dates'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('assets.data-quality.consistency') }}"
                                        class="btn btn-danger btn-sm">
                                        <i class="fas fa-eye"></i> View Consistency Issues
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-unlink"></i> Orphaned Records
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-secondary">
                                                <i class="fas fa-tags"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Orphaned Categories</span>
                                                <span
                                                    class="info-box-number">{{ $report['orphaned_records']['orphaned_categories'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-secondary">
                                                <i class="fas fa-building"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Orphaned Vendors</span>
                                                <span
                                                    class="info-box-number">{{ $report['orphaned_records']['orphaned_vendors'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-secondary">
                                                <i class="fas fa-coins"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Orphaned Funds</span>
                                                <span
                                                    class="info-box-number">{{ $report['orphaned_records']['orphaned_funds'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-secondary">
                                                <i class="fas fa-project-diagram"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Orphaned Projects</span>
                                                <span
                                                    class="info-box-number">{{ $report['orphaned_records']['orphaned_projects'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-secondary">
                                                <i class="fas fa-sitemap"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Orphaned Departments</span>
                                                <span
                                                    class="info-box-number">{{ $report['orphaned_records']['orphaned_departments'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="{{ route('assets.data-quality.orphaned') }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> View Orphaned Records
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        function exportReport(format) {
            const url = '{{ route('assets.data-quality.export') }}?format=' + format;
            window.open(url, '_blank');
        }
    </script>
@endpush
