@extends('layouts.main')

@section('title', 'Consistency Issues')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Consistency Issues</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.data-quality.index') }}">Data Quality</a>
                        </li>
                        <li class="breadcrumb-item active">Consistency</li>
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
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-minus"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Negative Values</span>
                            <span
                                class="info-box-number">{{ $report['consistency_issues']['negative_values'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Invalid Life Months</span>
                            <span
                                class="info-box-number">{{ $report['consistency_issues']['invalid_life_months'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-calendar-plus"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Future Service Dates</span>
                            <span
                                class="info-box-number">{{ $report['consistency_issues']['future_service_dates'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Issue Summary</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted">
                        Consistency checks flag assets with negative cost/book values, life months outside 1–600, or
                        placed-in-service dates in the future. Use Bulk Operations or Edit Asset to correct these
                        records before running depreciation.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
