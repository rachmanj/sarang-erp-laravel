@extends('layouts.main')

@section('title', 'Edit Asset Disposal')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Asset Disposal</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.disposals.index') }}">Disposals</a></li>
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
                            <h3 class="card-title">Edit Disposal
                                {{ $disposal->disposal_no ?? '#' . $disposal->id }}</h3>
                        </div>
                        <form action="{{ route('assets.disposals.update', $disposal) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="asset_id">Asset</label>
                                            <input type="text" class="form-control" id="asset_display"
                                                value="{{ $disposal->asset->code }} - {{ $disposal->asset->name }}"
                                                readonly disabled>
                                            <input type="hidden" name="asset_id" value="{{ $disposal->asset_id }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_date">Disposal Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('disposal_date') is-invalid @enderror"
                                                name="disposal_date" id="disposal_date"
                                                value="{{ old('disposal_date', $disposal->disposal_date?->format('Y-m-d')) }}"
                                                required>
                                            @error('disposal_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card card-info">
                                            <div class="card-header">
                                                <h3 class="card-title">Asset Information</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <strong>Category:</strong><br>
                                                        {{ $disposal->asset->category->name ?? '-' }}
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Project:</strong><br>
                                                        {{ $disposal->asset->project->name ?? '-' }}
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Department:</strong><br>
                                                        {{ $disposal->asset->department->name ?? '-' }}
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Book Value at Disposal:</strong><br>
                                                        <span class="text-primary">Rp
                                                            {{ number_format($disposal->book_value_at_disposal, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_type">Disposal Type <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('disposal_type') is-invalid @enderror"
                                                name="disposal_type" id="disposal_type" required>
                                                <option value="">Select Type</option>
                                                @foreach (['sale' => 'Sale', 'scrap' => 'Scrap', 'donation' => 'Donation', 'trade_in' => 'Trade-in', 'other' => 'Other'] as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ old('disposal_type', $disposal->disposal_type) == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('disposal_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_proceeds">Disposal Proceeds</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control @error('disposal_proceeds') is-invalid @enderror"
                                                    name="disposal_proceeds" id="disposal_proceeds"
                                                    value="{{ old('disposal_proceeds', $disposal->disposal_proceeds) }}"
                                                    placeholder="0.00">
                                            </div>
                                            @error('disposal_proceeds')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div id="gain-loss-preview" class="row">
                                    <div class="col-12">
                                        <div class="card card-warning">
                                            <div class="card-header">
                                                <h3 class="card-title">Gain/Loss Preview</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <strong>Book Value:</strong><br>
                                                        <span id="preview-book-value">Rp
                                                            {{ number_format($disposal->book_value_at_disposal, 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Disposal Proceeds:</strong><br>
                                                        <span id="preview-proceeds">Rp 0</span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Gain/Loss:</strong><br>
                                                        <span id="preview-gain-loss" class="font-weight-bold">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_reason">Disposal Reason</label>
                                            <textarea class="form-control @error('disposal_reason') is-invalid @enderror" name="disposal_reason"
                                                id="disposal_reason" rows="3">{{ old('disposal_reason', $disposal->disposal_reason) }}</textarea>
                                            @error('disposal_reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_method">Disposal Method</label>
                                            <input type="text"
                                                class="form-control @error('disposal_method') is-invalid @enderror"
                                                name="disposal_method" id="disposal_method"
                                                value="{{ old('disposal_method', $disposal->disposal_method) }}"
                                                placeholder="e.g., Auction, Direct Sale, etc.">
                                            @error('disposal_method')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_reference">Reference Number</label>
                                            <input type="text"
                                                class="form-control @error('disposal_reference') is-invalid @enderror"
                                                name="disposal_reference" id="disposal_reference"
                                                value="{{ old('disposal_reference', $disposal->disposal_reference) }}">
                                            @error('disposal_reference')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes"
                                                rows="3">{{ old('notes', $disposal->notes) }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Disposal
                                </button>
                                <a href="{{ route('assets.disposals.show', $disposal) }}" class="btn btn-secondary">
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

            var bookValue = {{ (float) $disposal->book_value_at_disposal }};

            function updateGainLossPreview() {
                var proceeds = parseFloat($('#disposal_proceeds').val()) || 0;
                $('#preview-proceeds').text('Rp ' + proceeds.toLocaleString('id-ID'));

                var difference = proceeds - bookValue;
                var gainLossText = '';
                var gainLossClass = '';

                if (difference > 0) {
                    gainLossText = 'Gain: Rp ' + difference.toLocaleString('id-ID');
                    gainLossClass = 'text-success';
                } else if (difference < 0) {
                    gainLossText = 'Loss: Rp ' + Math.abs(difference).toLocaleString('id-ID');
                    gainLossClass = 'text-danger';
                } else {
                    gainLossText = 'No Gain/Loss';
                    gainLossClass = 'text-muted';
                }

                $('#preview-gain-loss').html('<span class="' + gainLossClass + '">' + gainLossText + '</span>');
            }

            $('#disposal_proceeds').on('input', updateGainLossPreview);
            updateGainLossPreview();
        });
    </script>
@endsection
