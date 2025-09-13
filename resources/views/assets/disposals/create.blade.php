@extends('layouts.main')

@section('title', 'Create Asset Disposal')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Asset Disposal</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.disposals.index') }}">Disposals</a></li>
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
                            <h3 class="card-title">Asset Disposal Information</h3>
                        </div>
                        <form action="{{ route('assets.disposals.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <!-- Asset Selection -->
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
                                                    @foreach (\App\Models\Asset::active()->with('category')->get() as $assetOption)
                                                        <option value="{{ $assetOption->id }}"
                                                            data-book-value="{{ $assetOption->current_book_value }}"
                                                            data-category="{{ $assetOption->category->name }}">
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

                                    <!-- Disposal Date -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_date">Disposal Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('disposal_date') is-invalid @enderror"
                                                name="disposal_date" id="disposal_date"
                                                value="{{ old('disposal_date', date('Y-m-d')) }}" required>
                                            @error('disposal_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Disposal Type -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_type">Disposal Type <span
                                                    class="text-danger">*</span></label>
                                            <select
                                                class="form-control select2bs4 @error('disposal_type') is-invalid @enderror"
                                                name="disposal_type" id="disposal_type" required>
                                                <option value="">Select Type</option>
                                                <option value="sale"
                                                    {{ old('disposal_type') == 'sale' ? 'selected' : '' }}>Sale</option>
                                                <option value="scrap"
                                                    {{ old('disposal_type') == 'scrap' ? 'selected' : '' }}>Scrap</option>
                                                <option value="donation"
                                                    {{ old('disposal_type') == 'donation' ? 'selected' : '' }}>Donation
                                                </option>
                                                <option value="trade_in"
                                                    {{ old('disposal_type') == 'trade_in' ? 'selected' : '' }}>Trade-in
                                                </option>
                                                <option value="other"
                                                    {{ old('disposal_type') == 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                            @error('disposal_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Disposal Proceeds -->
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
                                                    value="{{ old('disposal_proceeds') }}" placeholder="0.00">
                                            </div>
                                            @error('disposal_proceeds')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Asset Information Display -->
                                <div id="asset-info" class="row" style="display: none;">
                                    <div class="col-12">
                                        <div class="card card-info">
                                            <div class="card-header">
                                                <h3 class="card-title">Asset Information</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <strong>Asset Code:</strong><br>
                                                        <span id="asset-code">-</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Asset Name:</strong><br>
                                                        <span id="asset-name">-</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Category:</strong><br>
                                                        <span id="asset-category">-</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Current Book Value:</strong><br>
                                                        <span id="asset-book-value" class="text-primary">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gain/Loss Preview -->
                                <div id="gain-loss-preview" class="row" style="display: none;">
                                    <div class="col-12">
                                        <div class="card card-warning">
                                            <div class="card-header">
                                                <h3 class="card-title">Gain/Loss Preview</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <strong>Book Value:</strong><br>
                                                        <span id="preview-book-value">Rp 0</span>
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
                                    <!-- Disposal Reason -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_reason">Disposal Reason</label>
                                            <textarea class="form-control @error('disposal_reason') is-invalid @enderror" name="disposal_reason"
                                                id="disposal_reason" rows="3" placeholder="Reason for disposal...">{{ old('disposal_reason') }}</textarea>
                                            @error('disposal_reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Disposal Method -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_method">Disposal Method</label>
                                            <input type="text"
                                                class="form-control @error('disposal_method') is-invalid @enderror"
                                                name="disposal_method" id="disposal_method"
                                                value="{{ old('disposal_method') }}"
                                                placeholder="e.g., Auction, Direct Sale, etc.">
                                            @error('disposal_method')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Disposal Reference -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="disposal_reference">Reference Number</label>
                                            <input type="text"
                                                class="form-control @error('disposal_reference') is-invalid @enderror"
                                                name="disposal_reference" id="disposal_reference"
                                                value="{{ old('disposal_reference') }}"
                                                placeholder="Document reference number">
                                            @error('disposal_reference')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" id="notes" rows="3"
                                                placeholder="Additional notes...">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Disposal
                                </button>
                                <a href="{{ route('assets.disposals.index') }}" class="btn btn-secondary">
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
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Asset selection change
            $('#asset_id').change(function() {
                const selectedOption = $(this).find('option:selected');
                const bookValue = selectedOption.data('book-value');
                const category = selectedOption.data('category');

                if (selectedOption.val()) {
                    $('#asset-info').show();
                    $('#asset-code').text(selectedOption.text().split(' - ')[0]);
                    $('#asset-name').text(selectedOption.text().split(' - ')[1]);
                    $('#asset-category').text(category);
                    $('#asset-book-value').text('Rp ' + parseFloat(bookValue).toLocaleString('id-ID'));

                    // Update preview
                    updateGainLossPreview();
                } else {
                    $('#asset-info').hide();
                    $('#gain-loss-preview').hide();
                }
            });

            // Disposal proceeds change
            $('#disposal_proceeds').on('input', function() {
                updateGainLossPreview();
            });

            function updateGainLossPreview() {
                const bookValue = parseFloat($('#asset-book-value').text().replace(/[^\d]/g, ''));
                const proceeds = parseFloat($('#disposal_proceeds').val()) || 0;

                if (bookValue > 0) {
                    $('#gain-loss-preview').show();
                    $('#preview-book-value').text('Rp ' + bookValue.toLocaleString('id-ID'));
                    $('#preview-proceeds').text('Rp ' + proceeds.toLocaleString('id-ID'));

                    const difference = proceeds - bookValue;
                    let gainLossText = '';
                    let gainLossClass = '';

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

                    $('#preview-gain-loss').html(`<span class="${gainLossClass}">${gainLossText}</span>`);
                }
            }

            // Initialize if asset is pre-selected
            if ($('#asset_id').val()) {
                $('#asset_id').trigger('change');
            }
        });
    </script>
@endsection
