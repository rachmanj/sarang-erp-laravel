@extends('layouts.app')

@section('title', 'Tax Report')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $report->report_name ?? 'Tax Report' }}</h1>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <p><strong>Type:</strong> {{ $report->report_type }}</p>
                    <p><strong>Status:</strong> {{ $report->status }}</p>
                    <p><strong>Due date:</strong> {{ $report->due_date?->toDateString() }}</p>
                    <pre class="bg-light p-3">{{ json_encode($report->report_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @can('tax.update')
                        @if ($report->canBeSubmitted())
                            <form method="post" action="{{ route('tax.reports.submit', $report) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                            </form>
                        @endif
                    @endcan
                    @can('tax.approve')
                        @if ($report->status === 'submitted')
                            <form method="post" action="{{ route('tax.reports.approve', $report) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </section>
@endsection
