@extends('layouts.main')

@section('title', 'Asset Movement')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Asset Movement</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.movements.index') }}">Movements</a></li>
                        <li class="breadcrumb-item active">#{{ $movement->id }}</li>
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
                            <i class="fas fa-exchange-alt mr-1"></i>
                            {{ $movement->movement_type_display }} — #{{ $movement->id }}
                        </h3>
                        <div class="text-muted small">
                            {!! $movement->status_badge !!}
                            · {{ $movement->movement_date?->format('d/m/Y') }}
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0">
                        @can('assets.movement.approve')
                            @if ($movement->canBeApproved())
                                <form method="POST" action="{{ route('assets.movements.approve', $movement) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success mr-1 mb-1"
                                        onclick="return confirm('Approve this movement?')">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                            @endif
                        @endcan
                        @can('assets.movement.update')
                            @if ($movement->canBeCompleted())
                                <form method="POST" action="{{ route('assets.movements.complete', $movement) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"
                                        onclick="return confirm('Complete this movement?')">
                                        <i class="fas fa-flag-checkered mr-1"></i>Complete
                                    </button>
                                </form>
                            @endif
                            @if ($movement->canBeCancelled())
                                <form method="POST" action="{{ route('assets.movements.cancel', $movement) }}"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning mr-1 mb-1"
                                        onclick="return confirm('Cancel this movement?')">
                                        <i class="fas fa-ban mr-1"></i>Cancel
                                    </button>
                                </form>
                            @endif
                            @if ($movement->isDraft())
                                <a href="{{ route('assets.movements.edit', $movement) }}"
                                    class="btn btn-sm btn-outline-primary mr-1 mb-1">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                            @endif
                        @endcan
                        @can('assets.movement.delete')
                            @if ($movement->isDraft())
                                <form method="POST" action="{{ route('assets.movements.destroy', $movement) }}"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mr-1 mb-1"
                                        onclick="return confirm('Delete this movement?')">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </form>
                            @endif
                        @endcan
                        <a href="{{ route('assets.movements.index') }}" class="btn btn-sm btn-secondary mb-1">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Movement Details</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%">Date</th>
                                        <td>{{ $movement->movement_date?->format('d/m/Y') ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td>{{ $movement->movement_type_display }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>{!! $movement->status_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <th>Reference</th>
                                        <td>{{ $movement->reference_number ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Reason</th>
                                        <td>{{ $movement->movement_reason ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $movement->notes ?: '-' }}</td>
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
                                            @if ($movement->asset)
                                                <a href="{{ route('assets.show', $movement->asset) }}">
                                                    {{ $movement->asset->code }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Asset Name</th>
                                        <td>{{ $movement->asset->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td>{{ $movement->asset->category->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Project</th>
                                        <td>{{ $movement->asset->project->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Department</th>
                                        <td>{{ $movement->asset->department->name ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Location & Custodian</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>From Location</strong><br>
                            {{ $movement->from_location ?: '-' }}
                        </div>
                        <div class="col-md-3">
                            <strong>To Location</strong><br>
                            {{ $movement->to_location ?: '-' }}
                        </div>
                        <div class="col-md-3">
                            <strong>From Custodian</strong><br>
                            {{ $movement->from_custodian ?: '-' }}
                        </div>
                        <div class="col-md-3">
                            <strong>To Custodian</strong><br>
                            {{ $movement->to_custodian ?: '-' }}
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
                            <strong>Created By:</strong> {{ $movement->creator->name ?? '-' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Approved By:</strong> {{ $movement->approver->name ?? '-' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Approved At:</strong>
                            {{ $movement->approved_at ? $movement->approved_at->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
