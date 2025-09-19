@extends('layouts.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ \DB::table('accounts')->count() }}</h3>
                    <p>Accounts</p>
                </div>
                <div class="icon"><i class="fas fa-book"></i></div>
                <a href="{{ url('journals/manual/create') }}" class="small-box-footer">Manual Journal <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ \DB::table('journals')->count() }}</h3>
                    <p>Journals</p>
                </div>
                <div class="icon"><i class="fas fa-book-open"></i></div>
                <a href="{{ url('reports/gl-detail') }}" class="small-box-footer">GL Detail <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ \DB::table('projects')->count() }}</h3>
                    <p>Projects</p>
                </div>
                <div class="icon"><i class="fas fa-project-diagram"></i></div>
                <a href="{{ url('reports/trial-balance') }}" class="small-box-footer">Trial Balance <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ \DB::table('business_partners')->count() }}</h3>
                    <p>Parties</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="#" class="small-box-footer">&nbsp;</a>
            </div>
        </div>
    </div>

    <!-- Asset Summary Section -->
    @canany(['assets.view', 'assets.disposal.view', 'assets.movement.view'])
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cube"></i> Fixed Assets Summary
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('reports.assets.summary') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-chart-bar"></i> View Detailed Summary
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-cube"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Assets</span>
                                        <span class="info-box-number">{{ \App\Models\Asset::count() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="fas fa-dollar-sign"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Value</span>
                                        <span class="info-box-number">Rp
                                            {{ number_format(\App\Models\Asset::sum('acquisition_cost'), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning">
                                        <i class="fas fa-chart-line"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Accumulated Depreciation</span>
                                        <span class="info-box-number">Rp
                                            {{ number_format(\App\Models\Asset::sum('accumulated_depreciation'), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary">
                                        <i class="fas fa-book"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Book Value</span>
                                        <span class="info-box-number">Rp
                                            {{ number_format(\App\Models\Asset::sum('current_book_value'), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <a href="{{ route('assets.index') }}" class="btn btn-info btn-block">
                                    <i class="fas fa-list"></i> Asset Register
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('reports.assets.depreciation-schedule') }}"
                                    class="btn btn-success btn-block">
                                    <i class="fas fa-calculator"></i> Depreciation Schedule
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('reports.assets.disposal-summary') }}" class="btn btn-danger btn-block">
                                    <i class="fas fa-trash-alt"></i> Disposal Summary
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('reports.assets.movement-log') }}" class="btn btn-warning btn-block">
                                    <i class="fas fa-exchange-alt"></i> Movement Log
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcanany
@endsection

@push('scripts')
    @if (session('status'))
        <script>
            toastr.success(@json(session('status')));
        </script>
    @endif
@endpush
