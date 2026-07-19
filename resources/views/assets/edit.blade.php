@extends('layouts.main')

@section('title', 'Edit Asset')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Asset</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.show', $asset) }}">{{ $asset->code }}</a>
                        </li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Edit Asset: {{ $asset->code }}</h3>
                        </div>
                        <form action="{{ route('assets.update', $asset) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="code">Code <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('code') is-invalid @enderror" name="code"
                                                id="code" value="{{ old('code', $asset->code) }}" required>
                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Name <span class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('name') is-invalid @enderror" name="name"
                                                id="name" value="{{ old('name', $asset->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="serial_number">Serial Number</label>
                                            <input type="text"
                                                class="form-control @error('serial_number') is-invalid @enderror"
                                                name="serial_number" id="serial_number"
                                                value="{{ old('serial_number', $asset->serial_number) }}">
                                            @error('serial_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category_id">Category <span class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('category_id') is-invalid @enderror"
                                                name="category_id" id="category_id" required>
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ old('category_id', $asset->category_id) == $category->id ? 'selected' : '' }}>
                                                        {{ $category->code }} - {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" name="description"
                                        id="description" rows="2">{{ old('description', $asset->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="acquisition_cost">Acquisition Cost <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control @error('acquisition_cost') is-invalid @enderror"
                                                    name="acquisition_cost" id="acquisition_cost"
                                                    value="{{ old('acquisition_cost', $asset->acquisition_cost) }}"
                                                    required
                                                    {{ $asset->depreciationEntries()->exists() ? 'readonly' : '' }}>
                                            </div>
                                            @if ($asset->depreciationEntries()->exists())
                                                <small class="text-muted">Cannot change acquisition cost when
                                                    depreciation entries exist.</small>
                                            @endif
                                            @error('acquisition_cost')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="salvage_value">Salvage Value <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control @error('salvage_value') is-invalid @enderror"
                                                    name="salvage_value" id="salvage_value"
                                                    value="{{ old('salvage_value', $asset->salvage_value) }}" required>
                                            </div>
                                            @error('salvage_value')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="method">Depreciation Method <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('method') is-invalid @enderror"
                                                name="method" id="method" required>
                                                <option value="straight_line"
                                                    {{ old('method', $asset->method) == 'straight_line' ? 'selected' : '' }}>
                                                    Straight Line</option>
                                                <option value="declining_balance"
                                                    {{ old('method', $asset->method) == 'declining_balance' ? 'selected' : '' }}>
                                                    Declining Balance</option>
                                            </select>
                                            @error('method')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="life_months">Life (Months) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" min="1"
                                                class="form-control @error('life_months') is-invalid @enderror"
                                                name="life_months" id="life_months"
                                                value="{{ old('life_months', $asset->life_months) }}" required>
                                            @error('life_months')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="placed_in_service_date">Placed in Service Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('placed_in_service_date') is-invalid @enderror"
                                                name="placed_in_service_date" id="placed_in_service_date"
                                                value="{{ old('placed_in_service_date', $asset->placed_in_service_date?->format('Y-m-d')) }}"
                                                required>
                                            @error('placed_in_service_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary">Dimensions</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="project_id">Project</label>
                                            <select
                                                class="form-control select2bs4 @error('project_id') is-invalid @enderror"
                                                name="project_id" id="project_id">
                                                <option value="">Select Project</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}"
                                                        {{ old('project_id', $asset->project_id) == $project->id ? 'selected' : '' }}>
                                                        {{ $project->code }} - {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('project_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="department_id">Department</label>
                                            <select
                                                class="form-control select2bs4 @error('department_id') is-invalid @enderror"
                                                name="department_id" id="department_id">
                                                <option value="">Select Department</option>
                                                @foreach ($departments as $department)
                                                    <option value="{{ $department->id }}"
                                                        {{ old('department_id', $asset->department_id) == $department->id ? 'selected' : '' }}>
                                                        {{ $department->code }} - {{ $department->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('department_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="business_partner_id">Vendor</label>
                                            <select
                                                class="form-control select2bs4 @error('business_partner_id') is-invalid @enderror"
                                                name="business_partner_id" id="business_partner_id">
                                                <option value="">Select Vendor</option>
                                                @foreach ($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}"
                                                        {{ old('business_partner_id', $asset->business_partner_id) == $vendor->id ? 'selected' : '' }}>
                                                        {{ $vendor->code }} - {{ $vendor->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('business_partner_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Asset
                                </button>
                                <a href="{{ route('assets.show', $asset) }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
        });
    </script>
@endsection
