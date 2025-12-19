@extends('layouts.main')

@section('title_page')
    Create Unit of Measure
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('unit-of-measures.index') }}">Units of Measure</a></li>
    <li class="breadcrumb-item active">Create</li>
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
                                <i class="fas fa-plus mr-2"></i>
                                New Unit of Measure
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('unit-of-measures.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Back to Units
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('unit-of-measures.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="code">Unit Code <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('code') is-invalid @enderror"
                                                id="code" name="code" value="{{ old('code') }}"
                                                placeholder="e.g., EA, KG, M" required maxlength="20">
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Short code for the unit (max 20 characters)</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="name">Unit Name <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name" value="{{ old('name') }}"
                                                placeholder="e.g., Each, Kilogram, Meter" required maxlength="100">
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                id="description" name="description" rows="3"
                                                placeholder="Unit description...">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="unit_type">Unit Type</label>
                                            <select class="form-control @error('unit_type') is-invalid @enderror"
                                                id="unit_type" name="unit_type">
                                                <option value="count" {{ old('unit_type', 'count') == 'count' ? 'selected' : '' }}>Count</option>
                                                <option value="weight" {{ old('unit_type') == 'weight' ? 'selected' : '' }}>Weight</option>
                                                <option value="length" {{ old('unit_type') == 'length' ? 'selected' : '' }}>Length</option>
                                                <option value="volume" {{ old('unit_type') == 'volume' ? 'selected' : '' }}>Volume</option>
                                                <option value="area" {{ old('unit_type') == 'area' ? 'selected' : '' }}>Area</option>
                                                <option value="time" {{ old('unit_type') == 'time' ? 'selected' : '' }}>Time</option>
                                            </select>
                                            @error('unit_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="is_base_unit"
                                                    name="is_base_unit" {{ old('is_base_unit') ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="is_base_unit">
                                                    Base Unit
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                Base units are used as the primary unit for conversions
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>
                                                Create Unit
                                            </button>
                                            <a href="{{ route('unit-of-measures.index') }}"
                                                class="btn btn-secondary ml-2">
                                                <i class="fas fa-times mr-2"></i>
                                                Cancel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
