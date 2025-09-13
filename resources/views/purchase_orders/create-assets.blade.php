@extends('layouts.app')

@section('title', 'Create Assets from Purchase Order')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Create Assets from Purchase Order</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('purchase-orders.show', $order->id) }}">{{ $order->order_no }}</a></li>
                            <li class="breadcrumb-item active">Create Assets</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Purchase Order Info -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-invoice"></i> Purchase Order Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Order Number:</strong> {{ $order->order_no }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Date:</strong> {{ $order->date->format('d/m/Y') }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Vendor:</strong> {{ $order->vendor->name }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Amount:</strong> Rp
                                        {{ number_format($order->total_amount, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Creation Form -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-cube"></i> Create Assets from Purchase Order Lines
                                </h3>
                            </div>
                            <div class="card-body">
                                <form id="createAssetsForm" method="POST"
                                    action="{{ route('purchase-orders.store-assets', $order->id) }}">
                                    @csrf

                                    <div id="assetsContainer">
                                        @foreach ($assetLines as $index => $line)
                                            <div class="asset-form-row border rounded p-3 mb-3"
                                                data-line-id="{{ $line->id }}">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6 class="text-primary">
                                                            <i class="fas fa-list"></i>
                                                            Line {{ $index + 1 }}:
                                                            {{ $line->description ?: 'No description' }}
                                                            <span class="badge badge-info">Rp
                                                                {{ number_format($line->amount, 0, ',', '.') }}</span>
                                                        </h6>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_code">Asset Code <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" class="form-control"
                                                                id="assets_{{ $index }}_code"
                                                                name="assets[{{ $index }}][code]"
                                                                value="{{ strtoupper(substr($line->description ?: 'ASSET', 0, 8)) }}-{{ $order->order_no }}-{{ $index + 1 }}"
                                                                required>
                                                            <input type="hidden"
                                                                name="assets[{{ $index }}][line_id]"
                                                                value="{{ $line->id }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_name">Asset Name <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" class="form-control"
                                                                id="assets_{{ $index }}_name"
                                                                name="assets[{{ $index }}][name]"
                                                                value="{{ $line->description ?: 'Asset from PO ' . $order->order_no }}"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label
                                                                for="assets_{{ $index }}_description">Description</label>
                                                            <textarea class="form-control" id="assets_{{ $index }}_description"
                                                                name="assets[{{ $index }}][description]" rows="2">{{ $line->description }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_serial_number">Serial
                                                                Number</label>
                                                            <input type="text" class="form-control"
                                                                id="assets_{{ $index }}_serial_number"
                                                                name="assets[{{ $index }}][serial_number]">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_category_id">Category
                                                                <span class="text-danger">*</span></label>
                                                            <select class="form-control select2bs4"
                                                                id="assets_{{ $index }}_category_id"
                                                                name="assets[{{ $index }}][category_id]" required>
                                                                <option value="">Select Category</option>
                                                                @foreach ($assetCategories as $category)
                                                                    <option value="{{ $category->id }}"
                                                                        data-method="{{ $category->method_default }}"
                                                                        data-life="{{ $category->life_months_default }}"
                                                                        data-salvage="{{ $category->salvage_value_default }}">
                                                                        {{ $category->code }} - {{ $category->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                for="assets_{{ $index }}_acquisition_cost">Acquisition
                                                                Cost <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control"
                                                                id="assets_{{ $index }}_acquisition_cost"
                                                                name="assets[{{ $index }}][acquisition_cost]"
                                                                value="{{ $line->amount }}" min="0"
                                                                step="0.01" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_salvage_value">Salvage
                                                                Value</label>
                                                            <input type="number" class="form-control"
                                                                id="assets_{{ $index }}_salvage_value"
                                                                name="assets[{{ $index }}][salvage_value]"
                                                                min="0" step="0.01">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_method">Depreciation
                                                                Method <span class="text-danger">*</span></label>
                                                            <select class="form-control"
                                                                id="assets_{{ $index }}_method"
                                                                name="assets[{{ $index }}][method]" required>
                                                                <option value="straight_line">Straight Line</option>
                                                                <option value="declining_balance">Declining Balance
                                                                </option>
                                                                <option value="double_declining_balance">Double Declining
                                                                    Balance</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_life_months">Life
                                                                (Months) <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control"
                                                                id="assets_{{ $index }}_life_months"
                                                                name="assets[{{ $index }}][life_months]"
                                                                min="1" max="600" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                for="assets_{{ $index }}_placed_in_service_date">Placed
                                                                in Service Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control"
                                                                id="assets_{{ $index }}_placed_in_service_date"
                                                                name="assets[{{ $index }}][placed_in_service_date]"
                                                                value="{{ $order->date->format('Y-m-d') }}" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="assets_{{ $index }}_fund_id">Fund</label>
                                                            <select class="form-control select2bs4"
                                                                id="assets_{{ $index }}_fund_id"
                                                                name="assets[{{ $index }}][fund_id]">
                                                                <option value="">Select Fund</option>
                                                                @foreach ($funds as $fund)
                                                                    <option value="{{ $fund->id }}">
                                                                        {{ $fund->code }} - {{ $fund->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                for="assets_{{ $index }}_project_id">Project</label>
                                                            <select class="form-control select2bs4"
                                                                id="assets_{{ $index }}_project_id"
                                                                name="assets[{{ $index }}][project_id]">
                                                                <option value="">Select Project</option>
                                                                @foreach ($projects as $project)
                                                                    <option value="{{ $project->id }}">
                                                                        {{ $project->code }} - {{ $project->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                for="assets_{{ $index }}_department_id">Department</label>
                                                            <select class="form-control select2bs4"
                                                                id="assets_{{ $index }}_department_id"
                                                                name="assets[{{ $index }}][department_id]">
                                                                <option value="">Select Department</option>
                                                                @foreach ($departments as $department)
                                                                    <option value="{{ $department->id }}">
                                                                        {{ $department->code }} - {{ $department->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Create Assets
                                        </button>
                                        <a href="{{ route('purchase-orders.show', $order->id) }}"
                                            class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Purchase Order
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .asset-form-row {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff !important;
        }

        .asset-form-row:hover {
            background-color: #e9ecef;
        }

        .form-group label {
            font-weight: 600;
        }

        .text-danger {
            color: #dc3545 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Auto-populate fields when category is selected
            $('select[name*="[category_id]"]').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const rowIndex = $(this).attr('name').match(/\[(\d+)\]/)[1];

                if (selectedOption.val()) {
                    const method = selectedOption.data('method');
                    const life = selectedOption.data('life');
                    const salvage = selectedOption.data('salvage');

                    if (method) {
                        $(`#assets_${rowIndex}_method`).val(method);
                    }

                    if (life) {
                        $(`#assets_${rowIndex}_life_months`).val(life);
                    }

                    if (salvage) {
                        $(`#assets_${rowIndex}_salvage_value`).val(salvage);
                    }
                }
            });

            // Form validation
            $('#createAssetsForm').on('submit', function(e) {
                let isValid = true;
                let errorMessage = '';

                // Check if at least one asset is being created
                if ($('.asset-form-row').length === 0) {
                    isValid = false;
                    errorMessage = 'No assets to create. Please select at least one purchase order line.';
                }

                // Validate required fields
                $('.asset-form-row').each(function() {
                    const rowIndex = $(this).data('line-id');
                    const requiredFields = [
                        'code', 'name', 'category_id', 'acquisition_cost',
                        'method', 'life_months', 'placed_in_service_date'
                    ];

                    requiredFields.forEach(function(field) {
                        const fieldValue = $(`#assets_${rowIndex}_${field}`).val();
                        if (!fieldValue || fieldValue.trim() === '') {
                            isValid = false;
                            errorMessage =
                                `Please fill in all required fields for asset ${rowIndex + 1}.`;
                            return false;
                        }
                    });

                    if (!isValid) return false;
                });

                if (!isValid) {
                    e.preventDefault();
                    toastr.error(errorMessage);
                    return false;
                }

                // Show loading state
                $(this).find('button[type="submit"]').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Creating Assets...');
            });
        });
    </script>
@endpush
