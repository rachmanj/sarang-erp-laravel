@extends('layouts.main')

@section('title_page')
    Asset Reports
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Asset Reports</li>
@endsection

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Reports</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Asset Reports</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Standard Reports -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line"></i> Standard Reports
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="{{ route('reports.assets.register') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-list-alt text-primary"></i> Asset Register
                                        </h5>
                                        <small class="text-muted">Complete asset listing</small>
                                    </div>
                                    <p class="mb-1">Comprehensive listing of all assets with detailed information
                                        including acquisition cost, depreciation, and current book value.</p>
                                </a>

                                <a href="{{ route('reports.assets.depreciation-schedule') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-calculator text-success"></i> Depreciation Schedule
                                        </h5>
                                        <small class="text-muted">Depreciation entries</small>
                                    </div>
                                    <p class="mb-1">Detailed depreciation entries showing monthly depreciation
                                        calculations and posting history.</p>
                                </a>

                                <a href="{{ route('reports.assets.disposal-summary') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-trash-alt text-danger"></i> Disposal Summary
                                        </h5>
                                        <small class="text-muted">Asset disposals</small>
                                    </div>
                                    <p class="mb-1">Summary of asset disposals including gain/loss calculations and
                                        disposal methods.</p>
                                </a>

                                <a href="{{ route('reports.assets.movement-log') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-exchange-alt text-info"></i> Movement Log
                                        </h5>
                                        <small class="text-muted">Asset movements</small>
                                    </div>
                                    <p class="mb-1">Complete audit trail of asset movements including location transfers
                                        and custodian changes.</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Reports -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar"></i> Analytics Reports
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="{{ route('reports.assets.summary') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-tachometer-alt text-warning"></i> Asset Summary
                                        </h5>
                                        <small class="text-muted">Dashboard overview</small>
                                    </div>
                                    <p class="mb-1">Executive summary with key metrics, asset distribution by category,
                                        and recent activity.</p>
                                </a>

                                <a href="{{ route('reports.assets.aging') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-clock text-secondary"></i> Asset Aging
                                        </h5>
                                        <small class="text-muted">Age analysis</small>
                                    </div>
                                    <p class="mb-1">Analysis of asset age distribution to identify assets approaching end
                                        of useful life.</p>
                                </a>

                                <a href="{{ route('reports.assets.low-value') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-coins text-dark"></i> Low Value Assets
                                        </h5>
                                        <small class="text-muted">Cost analysis</small>
                                    </div>
                                    <p class="mb-1">Identification of low-value assets that may qualify for immediate
                                        expensing or simplified tracking.</p>
                                </a>

                                <a href="{{ route('reports.assets.depreciation-history') }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            <i class="fas fa-history text-primary"></i> Depreciation History
                                        </h5>
                                        <small class="text-muted">Run history</small>
                                    </div>
                                    <p class="mb-1">Historical record of depreciation runs with posting status and total
                                        depreciation amounts.</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Quick Statistics
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info">
                                            <i class="fas fa-cube"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Assets</span>
                                            <span class="info-box-number" id="total-assets">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Value</span>
                                            <span class="info-box-number" id="total-value">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning">
                                            <i class="fas fa-chart-line"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Accumulated Depreciation</span>
                                            <span class="info-box-number" id="total-depreciation">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary">
                                            <i class="fas fa-book"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Book Value</span>
                                            <span class="info-box-number" id="book-value">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
            // Load quick statistics
            loadQuickStats();
        });

        function loadQuickStats() {
            $.ajax({
                url: '{{ route('reports.assets.data') }}',
                method: 'POST',
                data: {
                    report_type: 'asset_summary',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.data) {
                        const summary = response.data;

                        // Update statistics
                        $('#total-assets').text(summary.by_status.reduce((sum, item) => sum + item.count, 0));
                        $('#total-value').text('Rp ' + summary.by_status.reduce((sum, item) => sum + parseFloat(
                            item.total_cost), 0).toLocaleString('id-ID'));
                        $('#total-depreciation').text('Rp ' + summary.depreciation.total_depreciation
                            .toLocaleString('id-ID'));
                        $('#book-value').text('Rp ' + summary.depreciation.total_book_value.toLocaleString(
                            'id-ID'));
                    }
                },
                error: function() {
                    console.log('Failed to load quick statistics');
                }
            });
        }
    </script>
@endsection
