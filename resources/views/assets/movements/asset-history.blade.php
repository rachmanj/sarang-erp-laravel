@extends('layouts.main')

@section('title', 'Movement History — ' . $asset->code)

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Movement History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.show', $asset) }}">{{ $asset->code }}</a>
                        </li>
                        <li class="breadcrumb-item active">Movements</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $asset->code }} — {{ $asset->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Asset
                        </a>
                        @can('assets.movement.create')
                            <a href="{{ route('assets.movements.create', ['asset_id' => $asset->id]) }}"
                                class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> New Movement
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td>{{ $movement->movement_date?->format('d/m/Y') }}</td>
                                        <td>{{ $movement->movement_type_display }}</td>
                                        <td>
                                            {{ $movement->from_location ?: '-' }}
                                            @if ($movement->from_custodian)
                                                <br><small class="text-muted">{{ $movement->from_custodian }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $movement->to_location ?: '-' }}
                                            @if ($movement->to_custodian)
                                                <br><small class="text-muted">{{ $movement->to_custodian }}</small>
                                            @endif
                                        </td>
                                        <td>{!! $movement->status_badge !!}</td>
                                        <td>{{ $movement->creator->name ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('assets.movements.show', $movement) }}"
                                                class="btn btn-info btn-sm mr-1 mb-1" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted text-center p-3">No movements recorded for
                                            this asset</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
