@extends('layouts.main')

@section('title_page')
    Unit of Measure Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('unit-of-measures.index') }}">Units of Measure</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>
                                Unit of Measure Details
                            </h3>
                            <div class="card-tools">
                                @can('update_unit_of_measure')
                                    <a href="{{ route('unit-of-measures.edit', $unitOfMeasure->id) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </a>
                                @endcan
                                <a href="{{ route('unit-of-measures.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Back to Units
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="200">Code</th>
                                            <td>
                                                <span class="badge badge-info">{{ $unitOfMeasure->code }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Name</th>
                                            <td><strong>{{ $unitOfMeasure->name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>{{ $unitOfMeasure->description ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Unit Type</th>
                                            <td>
                                                <span class="badge badge-secondary">{{ ucfirst($unitOfMeasure->unit_type) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Base Unit</th>
                                            <td>
                                                @if($unitOfMeasure->is_base_unit)
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle mr-1"></i>Yes
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if($unitOfMeasure->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Conversions</h5>
                                    @if($unitOfMeasure->fromConversions->count() > 0 || $unitOfMeasure->toConversions->count() > 0)
                                        @if($unitOfMeasure->fromConversions->count() > 0)
                                            <h6>From This Unit:</h6>
                                            <ul class="list-group mb-3">
                                                @foreach($unitOfMeasure->fromConversions as $conversion)
                                                    <li class="list-group-item">
                                                        <strong>1 {{ $unitOfMeasure->code }}</strong> = 
                                                        <strong>{{ $conversion->conversion_quantity }} {{ $conversion->toUnit->code ?? 'N/A' }}</strong>
                                                        @if($conversion->toUnit)
                                                            <span class="text-muted">({{ $conversion->toUnit->name }})</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if($unitOfMeasure->toConversions->count() > 0)
                                            <h6>To This Unit:</h6>
                                            <ul class="list-group">
                                                @foreach($unitOfMeasure->toConversions as $conversion)
                                                    <li class="list-group-item">
                                                        <strong>{{ $conversion->conversion_quantity }} {{ $conversion->fromUnit->code ?? 'N/A' }}</strong> = 
                                                        <strong>1 {{ $unitOfMeasure->code }}</strong>
                                                        @if($conversion->fromUnit)
                                                            <span class="text-muted">({{ $conversion->fromUnit->name }})</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No conversions defined for this unit.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
