@extends('layouts.main')

@section('title', 'Orphaned Records')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Orphaned Records</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.data-quality.index') }}">Data Quality</a>
                        </li>
                        <li class="breadcrumb-item active">Orphaned</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="mb-3">
                <a href="{{ route('assets.data-quality.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Data Quality
                </a>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-tags"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Orphaned Categories</span>
                            <span
                                class="info-box-number">{{ $report['orphaned_records']['orphaned_categories'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-building"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Orphaned Vendors</span>
                            <span class="info-box-number">{{ $report['orphaned_records']['orphaned_vendors'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-project-diagram"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Orphaned Projects</span>
                            <span
                                class="info-box-number">{{ $report['orphaned_records']['orphaned_projects'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-sitemap"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Orphaned Departments</span>
                            <span
                                class="info-box-number">{{ $report['orphaned_records']['orphaned_departments'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">What This Means</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">
                        Orphaned records are assets whose foreign keys no longer resolve to active master data
                        (category, business partner/vendor, project, or department). Correct the dimension assignment
                        on the asset before posting depreciation or disposal journals.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
