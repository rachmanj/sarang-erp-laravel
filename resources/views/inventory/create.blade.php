@extends('layouts.main')

@section('title_page')
    Add Inventory Item
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active">Add Item</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Inventory Item</h3>
                </div>
                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Item Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                        id="code" name="code" value="{{ old('code') }}" required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id">Category <span class="text-danger">*</span></label>
                                    <select class="form-control @error('category_id') is-invalid @enderror" id="category_id"
                                        name="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->getHierarchicalName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="default_warehouse_id">Default Warehouse</label>
                                    <select class="form-control @error('default_warehouse_id') is-invalid @enderror"
                                        id="default_warehouse_id" name="default_warehouse_id">
                                        <option value="">Select Warehouse (Optional)</option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}"
                                                {{ old('default_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                                {{ $warehouse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('default_warehouse_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_unit_id">
                                        Unit of Measure <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select class="form-control @error('base_unit_id') is-invalid @enderror"
                                            id="base_unit_id" name="base_unit_id" required>
                                            <option value="">Select Unit</option>
                                            @foreach (\App\Models\UnitOfMeasure::active()->orderBy('name')->get() as $unit)
                                                <option value="{{ $unit->id }}"
                                                    {{ old('base_unit_id') == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->display_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btn-add-base-unit">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        This unit will become the <strong>base unit</strong> for this item (1 = base unit).
                                    </small>
                                    @error('base_unit_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_price">Default Purchase Price</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number"
                                            class="form-control @error('purchase_price') is-invalid @enderror"
                                            id="purchase_price" name="purchase_price" value="{{ old('purchase_price') }}"
                                            step="0.01" min="0">
                                    </div>
                                    <small class="form-text text-muted">
                                        Optional: used for initial stock and estimates. Actual COGS comes from purchase transactions.
                                    </small>
                                    @error('purchase_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="selling_price">Selling Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number"
                                            class="form-control @error('selling_price') is-invalid @enderror"
                                            id="selling_price" name="selling_price" value="{{ old('selling_price') }}"
                                            step="0.01" min="0" required>
                                    </div>
                                    @error('selling_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_stock_level">Minimum Stock Level</label>
                                    <input type="number"
                                        class="form-control @error('min_stock_level') is-invalid @enderror"
                                        id="min_stock_level" name="min_stock_level"
                                        value="{{ old('min_stock_level', 0) }}" min="0">
                                    @error('min_stock_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_stock_level">Maximum Stock Level</label>
                                    <input type="number"
                                        class="form-control @error('max_stock_level') is-invalid @enderror"
                                        id="max_stock_level" name="max_stock_level"
                                        value="{{ old('max_stock_level', 0) }}" min="0">
                                    @error('max_stock_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="reorder_point">Reorder Point</label>
                                    <input type="number"
                                        class="form-control @error('reorder_point') is-invalid @enderror"
                                        id="reorder_point" name="reorder_point" value="{{ old('reorder_point', 0) }}"
                                        min="0">
                                    @error('reorder_point')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_type">Item Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('item_type') is-invalid @enderror" id="item_type"
                                        name="item_type" required>
                                        <option value="">Select Type</option>
                                        <option value="item" {{ old('item_type', 'item') == 'item' ? 'selected' : '' }}>
                                            Item (Physical Inventory)
                                        </option>
                                        <option value="service" {{ old('item_type') == 'service' ? 'selected' : '' }}>
                                            Service (Non-Inventory)
                                        </option>
                                    </select>
                                    @error('item_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="valuation_method">Valuation Method <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control @error('valuation_method') is-invalid @enderror"
                                        id="valuation_method" name="valuation_method" required>
                                        <option value="">Select Method</option>
                                        <option value="fifo" {{ old('valuation_method') == 'fifo' ? 'selected' : '' }}>
                                            FIFO (First In, First Out)
                                        </option>
                                        <option value="lifo" {{ old('valuation_method') == 'lifo' ? 'selected' : '' }}>
                                            LIFO (Last In, First Out)
                                        </option>
                                        <option value="weighted_average"
                                            {{ old('valuation_method') == 'weighted_average' ? 'selected' : '' }}>
                                            Weighted Average
                                        </option>
                                    </select>
                                    @error('valuation_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="initial_stock">Initial Stock</label>
                                    <input type="number"
                                        class="form-control @error('initial_stock') is-invalid @enderror"
                                        id="initial_stock" name="initial_stock" value="{{ old('initial_stock', 0) }}"
                                        min="0">
                                    <small class="form-text text-muted">Optional: Set initial stock quantity (only for Item
                                        type)</small>
                                    @error('initial_stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Item
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Item
                        </button>
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Set reorder point to minimum stock level
            $('#min_stock_level').on('input', function() {
                const minLevel = parseInt($(this).val()) || 0;
                $('#reorder_point').val(minLevel);
            });

            // Handle item type changes
            function toggleStockFields() {
                const itemType = $('#item_type').val();
                const stockFields = ['#min_stock_level', '#max_stock_level', '#reorder_point', '#initial_stock'];

                if (itemType === 'service') {
                    // Hide and disable stock-related fields for services
                    stockFields.forEach(function(field) {
                        $(field).closest('.form-group').hide();
                        $(field).prop('disabled', true);
                    });
                } else {
                    // Show and enable stock-related fields for items
                    stockFields.forEach(function(field) {
                        $(field).closest('.form-group').show();
                        $(field).prop('disabled', false);
                    });
                }
            }

            // Initial toggle
            toggleStockFields();

            // Toggle on change
            $('#item_type').on('change', toggleStockFields);

            // Validation
            $('form').on('submit', function(e) {
                const itemType = $('#item_type').val();
                const minLevel = parseInt($('#min_stock_level').val()) || 0;
                const maxLevel = parseInt($('#max_stock_level').val()) || 0;
                const reorderPoint = parseInt($('#reorder_point').val()) || 0;

                // Only validate stock fields for item type
                if (itemType === 'item') {
                    if (maxLevel > 0 && minLevel > maxLevel) {
                        e.preventDefault();
                        toastr.error('Minimum stock level cannot be greater than maximum stock level');
                        return false;
                    }

                    if (reorderPoint > minLevel) {
                        e.preventDefault();
                        toastr.error('Reorder point cannot be greater than minimum stock level');
                        return false;
                    }
                }
            });
            // Quick-add base unit modal
            $('#btn-add-base-unit').on('click', function() {
                $('#quickAddUnitModal').modal('show');
            });

            $('#quickAddUnitForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const btn = form.find('button[type="submit"]');
                btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route('unit-of-measures.api.store') }}',
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.unit) {
                            const unit = response.unit;
                            const select = $('#base_unit_id');
                            if (select.find('option[value="' + unit.id + '"]').length === 0) {
                                select.append('<option value="' + unit.id + '">' + unit.display_name +
                                    '</option>');
                            }
                            select.val(unit.id);
                        }
                        $('#quickAddUnitModal').modal('hide');
                        form[0].reset();
                    },
                    error: function() {
                        toastr.error('Failed to create unit of measure');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection

@push('modals')
    <div class="modal fade" id="quickAddUnitModal" tabindex="-1" role="dialog" aria-labelledby="quickAddUnitLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddUnitLabel">Add Unit of Measure</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="quickAddUnitForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="quick_unit_code_create">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_unit_code_create" name="code" required>
                        </div>
                        <div class="form-group">
                            <label for="quick_unit_name_create">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_unit_name_create" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="quick_unit_description_create">Description</label>
                            <textarea class="form-control" id="quick_unit_description_create" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Unit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
