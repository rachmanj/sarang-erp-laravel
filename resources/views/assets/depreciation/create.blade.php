@extends('layouts.main')

@section('title', 'Create Depreciation Run')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Depreciation Run</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.depreciation.index') }}">Depreciation</a>
                        </li>
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
                            <h3 class="card-title">New Depreciation Run</h3>
                        </div>
                        <form id="createDepreciationForm">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="period">Period <span class="text-danger">*</span></label>
                                            <input type="month"
                                                class="form-control @error('period') is-invalid @enderror"
                                                name="period" id="period"
                                                value="{{ old('period', date('Y-m')) }}" required>
                                            @error('period')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Select the month for this depreciation
                                                run.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="asset_ids">Specific Assets (optional)</label>
                                            <select class="form-control select2bs4" name="asset_ids[]" id="asset_ids"
                                                multiple>
                                                @foreach (\App\Models\Asset::active()->depreciable()->with('category')->orderBy('code')->get() as $assetOption)
                                                    <option value="{{ $assetOption->id }}"
                                                        data-cost="{{ $assetOption->acquisition_cost }}"
                                                        data-book="{{ $assetOption->current_book_value }}"
                                                        data-category="{{ $assetOption->category->name ?? '-' }}">
                                                        {{ $assetOption->code }} - {{ $assetOption->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Leave empty to include all active
                                                depreciable assets. Selection is for preview; the run includes all
                                                eligible assets for the period.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">Assets Preview</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-sm mb-0"
                                                id="assets-preview-table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th class="text-right">Acquisition Cost</th>
                                                        <th class="text-right">Book Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="assets-preview-body">
                                                    @foreach (\App\Models\Asset::active()->depreciable()->with('category')->orderBy('code')->get() as $previewAsset)
                                                        <tr data-asset-id="{{ $previewAsset->id }}">
                                                            <td>{{ $previewAsset->code }}</td>
                                                            <td>{{ $previewAsset->name }}</td>
                                                            <td>{{ $previewAsset->category->name ?? '-' }}</td>
                                                            <td class="text-right">Rp
                                                                {{ number_format($previewAsset->acquisition_cost, 0, ',', '.') }}
                                                            </td>
                                                            <td class="text-right">Rp
                                                                {{ number_format($previewAsset->current_book_value, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="p-2 text-muted small" id="preview-count"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> Create Run
                                </button>
                                <a href="{{ route('assets.depreciation.index') }}" class="btn btn-secondary">
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

            function updatePreview() {
                var selected = $('#asset_ids').val() || [];
                var $rows = $('#assets-preview-body tr');

                if (selected.length === 0) {
                    $rows.show();
                    $('#preview-count').text($rows.length + ' asset(s) will be included (all active depreciable).');
                } else {
                    $rows.hide();
                    selected.forEach(function(id) {
                        $rows.filter('[data-asset-id="' + id + '"]').show();
                    });
                    $('#preview-count').text(selected.length +
                        ' asset(s) selected for preview (run still covers all eligible assets).');
                }
            }

            $('#asset_ids').on('change', updatePreview);
            updatePreview();

            $('#createDepreciationForm').on('submit', function(e) {
                e.preventDefault();
                var $btn = $('#submitBtn');
                $btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route('assets.depreciation.store') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        period: $('#period').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            if (response.data && response.data.id) {
                                window.location.href = @json(url('/assets/depreciation')) + '/' + response.data.id;
                            } else {
                                window.location.href = '{{ route('assets.depreciation.index') }}';
                            }
                        } else {
                            toastr.error(response.message || 'Failed to create depreciation run.');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON?.message ||
                            'An error occurred while creating the depreciation run.';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function(key, value) {
                                toastr.error(value[0]);
                            });
                        } else {
                            toastr.error(message);
                        }
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection
