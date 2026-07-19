@extends('layouts.main')

@section('title', 'Asset ' . $asset->code)

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item active">{{ $asset->code }}</li>
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
                            <i class="fas fa-building mr-1"></i>
                            {{ $asset->code }} — {{ $asset->name }}
                        </h3>
                        <div class="text-muted small">
                            @php
                                $statusBadge = match ($asset->status) {
                                    'active' => '<span class="badge badge-success">Active</span>',
                                    'retired' => '<span class="badge badge-warning">Retired</span>',
                                    'disposed' => '<span class="badge badge-danger">Disposed</span>',
                                    default => '<span class="badge badge-secondary">Unknown</span>',
                                };
                            @endphp
                            {!! $statusBadge !!}
                            @if ($asset->category)
                                · {{ $asset->category->name }}
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                        @can('assets.update')
                            @if ($asset->status === 'active')
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-primary mr-1 mb-1">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                            @endif
                        @endcan
                        @can('assets.disposal.create')
                            @if ($asset->canBeDisposed())
                                <a href="{{ route('assets.disposals.create', ['asset_id' => $asset->id]) }}"
                                    class="btn btn-sm btn-warning mr-1 mb-1">
                                    <i class="fas fa-trash-alt mr-1"></i>Dispose
                                </a>
                            @endif
                        @endcan
                        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-secondary mb-1">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Index
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">General Information</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Code</th>
                                        <td>{{ $asset->code }}</td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $asset->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td>{{ $asset->description ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Serial Number</th>
                                        <td>{{ $asset->serial_number ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>{{ $asset->category->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vendor</th>
                                        <td>{{ $asset->vendor->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>{!! $statusBadge !!}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Financial & Depreciation</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Acquisition Cost</th>
                                        <td>Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Salvage Value</th>
                                        <td>Rp {{ number_format($asset->salvage_value, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Book Value</th>
                                        <td class="font-weight-bold text-primary">Rp
                                            {{ number_format($asset->current_book_value, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Accumulated Depreciation</th>
                                        <td>Rp {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Method</th>
                                        <td>{{ str_replace('_', ' ', ucwords($asset->method, '_')) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Life Months</th>
                                        <td>{{ $asset->life_months }}</td>
                                    </tr>
                                    <tr>
                                        <th>Remaining Life</th>
                                        <td>{{ $asset->remaining_life_months }} months</td>
                                    </tr>
                                    <tr>
                                        <th>Placed in Service</th>
                                        <td>{{ $asset->placed_in_service_date?->format('d/m/Y') ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Dimensions</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Project:</strong>
                            {{ $asset->project ? $asset->project->code . ' - ' . $asset->project->name : '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Department:</strong>
                            {{ $asset->department ? $asset->department->code . ' - ' . $asset->department->name : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Depreciation Entries</h3>
                </div>
                <div class="card-body p-0">
                    @if ($asset->depreciationEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Book</th>
                                        <th class="text-right">Amount</th>
                                        <th>Status</th>
                                        <th>Journal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($asset->depreciationEntries->sortByDesc('period') as $entry)
                                        <tr>
                                            <td>{{ $entry->period_display }}</td>
                                            <td>{{ ucfirst($entry->book) }}</td>
                                            <td class="text-right">Rp
                                                {{ number_format($entry->amount, 0, ',', '.') }}</td>
                                            <td>
                                                @if ($entry->isPosted())
                                                    <span class="badge badge-success">Posted</span>
                                                @else
                                                    <span class="badge badge-warning">Draft</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($entry->journal)
                                                    {{ $entry->journal->journal_no ?? $entry->journal->id }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 text-muted">No depreciation entries yet.</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
