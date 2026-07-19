@extends('layouts.main')

@section('title', 'Create Asset Movement')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Asset Movement</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.movements.index') }}">Movements</a></li>
                        <li class="breadcrumb-item active">Create</li>
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
                            <h3 class="card-title">Asset Movement Information</h3>
                        </div>
                        <form action="{{ route('assets.movements.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="asset_id">Asset <span class="text-danger">*</span></label>
                                            <select class="form-control select2bs4 @error('asset_id') is-invalid @enderror"
                                                name="asset_id" id="asset_id" required>
                                                <option value="">Select Asset</option>
                                                @if ($asset)
                                                    <option value="{{ $asset->id }}" selected>
                                                        {{ $asset->code }} - {{ $asset->name }}
                                                    </option>
                                                @else
                                                    @foreach (\App\Models\Asset::active()->with('category')->orderBy('code')->get() as $assetOption)
                                                        <option value="{{ $assetOption->id }}"
                                                            {{ old('asset_id') == $assetOption->id ? 'selected' : '' }}>
                                                            {{ $assetOption->code }} - {{ $assetOption->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('asset_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="movement_date">Movement Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('movement_date') is-invalid @enderror"
                                                name="movement_date" id="movement_date"
                                                value="{{ old('movement_date', date('Y-m-d')) }}" required>
                                            @error('movement_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="movement_type">Movement Type <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('movement_type') is-invalid @enderror"
                                                name="movement_type" id="movement_type" required>
                                                <option value="">Select Type</option>
                                                <option value="transfer"
                                                    {{ old('movement_type') == 'transfer' ? 'selected' : '' }}>Transfer
                                                </option>
                                                <option value="relocation"
                                                    {{ old('movement_type') == 'relocation' ? 'selected' : '' }}>
                                                    Relocation</option>
                                                <option value="custodian_change"
                                                    {{ old('movement_type') == 'custodian_change' ? 'selected' : '' }}>
                                                    Custodian Change</option>
                                                <option value="maintenance"
                                                    {{ old('movement_type') == 'maintenance' ? 'selected' : '' }}>
                                                    Maintenance</option>
                                                <option value="other"
                                                    {{ old('movement_type') == 'other' ? 'selected' : '' }}>Other
                                                </option>
                                            </select>
                                            @error('movement_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_number">Reference Number</label>
                                            <input type="text"
                                                class="form-control @error('reference_number') is-invalid @enderror"
                                                name="reference_number" id="reference_number"
                                                value="{{ old('reference_number') }}">
                                            @error('reference_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="from_location">From Location</label>
                                            <input type="text"
                                                class="form-control @error('from_location') is-invalid @enderror"
                                                name="from_location" id="from_location"
                                                value="{{ old('from_location') }}">
                                            @error('from_location')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="to_location">To Location</label>
                                            <input type="text"
                                                class="form-control @error('to_location') is-invalid @enderror"
                                                name="to_location" id="to_location" value="{{ old('to_location') }}">
                                            @error('to_location')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="from_custodian">From Custodian</label>
                                            <input type="text"
                                                class="form-control @error('from_custodian') is-invalid @enderror"
                                                name="from_custodian" id="from_custodian"
                                                value="{{ old('from_custodian') }}">
                                            @error('from_custodian')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="to_custodian">To Custodian</label>
                                            <input type="text"
                                                class="form-control @error('to_custodian') is-invalid @enderror"
                                                name="to_custodian" id="to_custodian"
                                                value="{{ old('to_custodian') }}">
                                            @error('to_custodian')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="movement_reason">Movement Reason</label>
                                            <textarea class="form-control @error('movement_reason') is-invalid @enderror" name="movement_reason"
                                                id="movement_reason" rows="3">{{ old('movement_reason') }}</textarea>
                                            @error('movement_reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes"
                                                rows="3">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Movement
                                </button>
                                <a href="{{ route('assets.movements.index') }}" class="btn btn-secondary">
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
