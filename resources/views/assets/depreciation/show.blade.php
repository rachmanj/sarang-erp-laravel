@extends('layouts.main')

@section('title', 'Depreciation Run ' . $run->period_display)

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Depreciation Run</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.depreciation.index') }}">Depreciation</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $run->period_display }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-1">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            {{ $run->period_display }}
                        </h3>
                        <div class="text-muted small">
                            {!! $run->status_badge !!}
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                        @can('assets.depreciation.run')
                            @if ($run->isDraft())
                                <button type="button" class="btn btn-sm btn-info mr-1 mb-1" id="btn-calculate"
                                    data-id="{{ $run->id }}">
                                    <i class="fas fa-calculator mr-1"></i>Calculate
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary mr-1 mb-1" id="btn-create-entries"
                                    data-id="{{ $run->id }}">
                                    <i class="fas fa-list mr-1"></i>Create Entries
                                </button>
                                <button type="button" class="btn btn-sm btn-success mr-1 mb-1" id="btn-post"
                                    data-id="{{ $run->id }}">
                                    <i class="fas fa-check mr-1"></i>Post
                                </button>
                            @endif
                        @endcan
                        @can('assets.depreciation.reverse')
                            @if ($run->canBeReversed())
                                <button type="button" class="btn btn-sm btn-warning mr-1 mb-1" id="btn-reverse"
                                    data-id="{{ $run->id }}">
                                    <i class="fas fa-undo mr-1"></i>Reverse
                                </button>
                            @endif
                        @endcan
                        <a href="{{ route('assets.depreciation.index') }}" class="btn btn-sm btn-secondary mb-1">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Run Details</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Period</th>
                                        <td>{{ $run->period_display }} ({{ $run->period }})</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>{!! $run->status_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Depreciation</th>
                                        <td class="font-weight-bold">Rp
                                            {{ number_format($run->total_depreciation, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Asset Count</th>
                                        <td>{{ $run->asset_count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Audit</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Created By</th>
                                        <td>{{ $run->creator->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Posted By</th>
                                        <td>{{ $run->poster->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Posted At</th>
                                        <td>{{ $run->posted_at ? $run->posted_at->format('d/m/Y H:i') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $run->notes ?: '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Depreciation Entries</h3>
                </div>
                <div class="card-body p-0">
                    @if ($run->depreciationEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Asset Code</th>
                                        <th>Asset Name</th>
                                        <th>Category</th>
                                        <th class="text-right">Amount</th>
                                        <th>Dimensions</th>
                                        <th>Posted Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($run->depreciationEntries as $entry)
                                        @php
                                            $dims = [];
                                            if ($entry->project) {
                                                $dims[] = 'Project: ' . $entry->project->name;
                                            }
                                            if ($entry->department) {
                                                $dims[] = 'Dept: ' . $entry->department->name;
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $entry->asset->code ?? '-' }}</td>
                                            <td>{{ $entry->asset->name ?? '-' }}</td>
                                            <td>{{ $entry->asset->category->name ?? '-' }}</td>
                                            <td class="text-right">Rp
                                                {{ number_format($entry->amount, 0, ',', '.') }}</td>
                                            <td>{!! $dims ? implode('<br>', $dims) : 'No dimensions' !!}</td>
                                            <td>
                                                @if ($entry->isPosted())
                                                    <span class="badge badge-success">Posted</span>
                                                @else
                                                    <span class="badge badge-warning">Draft</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 text-muted">No depreciation entries yet. Use Calculate / Create Entries for draft
                            runs.</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            function handleAction(url, method, confirmMsg) {
                if (!confirm(confirmMsg)) {
                    return;
                }

                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            location.reload();
                        } else {
                            toastr.error(response.message || 'Action failed.');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'An error occurred.');
                    }
                });
            }

            $('#btn-calculate').on('click', function() {
                var id = $(this).data('id');
                handleAction('/assets/depreciation/' + id + '/calculate', 'GET',
                    'Calculate depreciation for this run?');
            });

            $('#btn-create-entries').on('click', function() {
                var id = $(this).data('id');
                handleAction('/assets/depreciation/' + id + '/entries', 'POST',
                    'Create draft depreciation entries for this run?');
            });

            $('#btn-post').on('click', function() {
                var id = $(this).data('id');
                handleAction('/assets/depreciation/' + id + '/post', 'POST',
                    'Are you sure you want to post this depreciation run?');
            });

            $('#btn-reverse').on('click', function() {
                var id = $(this).data('id');
                handleAction('/assets/depreciation/' + id + '/reverse', 'POST',
                    'Are you sure you want to reverse this depreciation run?');
            });
        });
    </script>
@endsection
