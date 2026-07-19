@extends('layouts.main')

@section('title', 'Disposal ' . ($disposal->disposal_no ?? $disposal->id))

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Disposal</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.disposals.index') }}">Disposals</a></li>
                        <li class="breadcrumb-item active">{{ $disposal->disposal_no ?? $disposal->id }}</li>
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
                            <i class="fas fa-recycle mr-1"></i>
                            {{ $disposal->disposal_no ?? 'Disposal #' . $disposal->id }}
                        </h3>
                        <div class="text-muted small">
                            {!! $disposal->status_badge !!}
                            · {{ $disposal->disposal_type_display }}
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                        @can('assets.disposal.update')
                            @if ($disposal->isDraft())
                                <a href="{{ route('assets.disposals.edit', $disposal) }}"
                                    class="btn btn-sm btn-warning mr-1 mb-1">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                            @endif
                        @endcan
                        @can('assets.disposal.post')
                            @if ($disposal->isDraft())
                                <form method="POST" action="{{ route('assets.disposals.post', $disposal) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success mr-1 mb-1"
                                        onclick="return confirm('Are you sure you want to post this disposal?')">
                                        <i class="fas fa-check mr-1"></i>Post
                                    </button>
                                </form>
                            @endif
                        @endcan
                        @can('assets.disposal.reverse')
                            @if ($disposal->isPosted())
                                <form method="POST" action="{{ route('assets.disposals.reverse', $disposal) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger mr-1 mb-1"
                                        onclick="return confirm('Are you sure you want to reverse this disposal?')">
                                        <i class="fas fa-undo mr-1"></i>Reverse
                                    </button>
                                </form>
                            @endif
                        @endcan
                        @can('assets.disposal.delete')
                            @if ($disposal->isDraft())
                                <form method="POST" action="{{ route('assets.disposals.destroy', $disposal) }}"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mr-1 mb-1"
                                        onclick="return confirm('Are you sure you want to delete this disposal?')">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </form>
                            @endif
                        @endcan
                        <a href="{{ route('assets.disposals.index') }}" class="btn btn-sm btn-secondary mb-1">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Disposal Information</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Disposal No</th>
                                        <td>{{ $disposal->disposal_no ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Disposal Date</th>
                                        <td>{{ $disposal->disposal_date?->format('d/m/Y') ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td>{{ $disposal->disposal_type_display }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>{!! $disposal->status_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <th>Reason</th>
                                        <td>{{ $disposal->disposal_reason ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Method</th>
                                        <td>{{ $disposal->disposal_method ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Reference</th>
                                        <td>{{ $disposal->disposal_reference ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $disposal->notes ?: '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Asset Information</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Asset Code</th>
                                        <td>
                                            @if ($disposal->asset)
                                                <a href="{{ route('assets.show', $disposal->asset) }}">
                                                    {{ $disposal->asset->code }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Asset Name</th>
                                        <td>{{ $disposal->asset->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>{{ $disposal->asset->category->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Project</th>
                                        <td>{{ $disposal->asset->project->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Department</th>
                                        <td>{{ $disposal->asset->department->name ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Financial Impact</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Proceeds</strong><br>
                            Rp {{ number_format($disposal->disposal_proceeds ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Book Value</strong><br>
                            Rp {{ number_format($disposal->book_value_at_disposal, 0, ',', '.') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Gain/Loss</strong><br>
                            {!! $disposal->gain_loss_display !!}
                        </div>
                        <div class="col-md-3">
                            <strong>Journal Entry</strong><br>
                            @if ($disposal->journal)
                                <a href="{{ route('journals.index') }}?search={{ $disposal->journal->journal_no ?? $disposal->journal_id }}">
                                    {{ $disposal->journal->journal_no ?? '#' . $disposal->journal_id }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Audit</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Created By:</strong> {{ $disposal->creator->name ?? '-' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Posted By:</strong> {{ $disposal->poster->name ?? '-' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Posted At:</strong>
                            {{ $disposal->posted_at ? $disposal->posted_at->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
