@extends('layouts.main')

@section('title_page')
    Edit Inventory Item
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.show', $item->id) }}">{{ $item->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Inventory Item</h3>
                </div>
                <form action="{{ route('inventory.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Item Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                        id="code" name="code" value="{{ old('code', $item->code) }}" required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Item Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $item->name) }}" required>
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
                                                {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>
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
                                    <label for="unit_of_measure">Unit of Measure <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('unit_of_measure') is-invalid @enderror"
                                        id="unit_of_measure" name="unit_of_measure"
                                        value="{{ old('unit_of_measure', $item->unit_of_measure) }}"
                                        readonly>
                                    <small class="form-text text-muted">
                                        Base unit is managed on the <strong>Manage Units</strong> page for this item (1 = base unit).
                                    </small>
                                    @error('unit_of_measure')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                rows="3">{{ old('description', $item->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_price">Purchase Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number"
                                            class="form-control @error('purchase_price') is-invalid @enderror"
                                            id="purchase_price" name="purchase_price"
                                            value="{{ old('purchase_price', $item->purchase_price) }}" step="0.01"
                                            min="0" required>
                                    </div>
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
                                            id="selling_price" name="selling_price"
                                            value="{{ old('selling_price', $item->selling_price) }}" step="0.01"
                                            min="0" required>
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
                                    <label for="min_stock_level">Minimum Stock Level <span
                                            class="text-danger">*</span></label>
                                    <input type="number"
                                        class="form-control @error('min_stock_level') is-invalid @enderror"
                                        id="min_stock_level" name="min_stock_level"
                                        value="{{ old('min_stock_level', $item->min_stock_level) }}" min="0"
                                        required>
                                    @error('min_stock_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_stock_level">Maximum Stock Level <span
                                            class="text-danger">*</span></label>
                                    <input type="number"
                                        class="form-control @error('max_stock_level') is-invalid @enderror"
                                        id="max_stock_level" name="max_stock_level"
                                        value="{{ old('max_stock_level', $item->max_stock_level) }}" min="0"
                                        required>
                                    @error('max_stock_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="reorder_point">Reorder Point <span class="text-danger">*</span></label>
                                    <input type="number"
                                        class="form-control @error('reorder_point') is-invalid @enderror"
                                        id="reorder_point" name="reorder_point"
                                        value="{{ old('reorder_point', $item->reorder_point) }}" min="0" required>
                                    @error('reorder_point')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="valuation_method">Valuation Method <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control @error('valuation_method') is-invalid @enderror"
                                        id="valuation_method" name="valuation_method" required>
                                        <option value="">Select Method</option>
                                        <option value="fifo"
                                            {{ old('valuation_method', $item->valuation_method) == 'fifo' ? 'selected' : '' }}>
                                            FIFO (First In, First Out)
                                        </option>
                                        <option value="lifo"
                                            {{ old('valuation_method', $item->valuation_method) == 'lifo' ? 'selected' : '' }}>
                                            LIFO (Last In, First Out)
                                        </option>
                                        <option value="weighted_average"
                                            {{ old('valuation_method', $item->valuation_method) == 'weighted_average' ? 'selected' : '' }}>
                                            Weighted Average
                                        </option>
                                    </select>
                                    @error('valuation_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Current Stock</label>
                                    <div class="form-control-plaintext">
                                        <span
                                            class="badge badge-{{ $item->current_stock <= 0 ? 'danger' : ($item->current_stock <= $item->min_stock_level ? 'warning' : 'success') }}">
                                            {{ $item->current_stock }} {{ $item->unit_of_measure }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                    {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active Item
                                </label>
                            </div>
                        </div>

                        @if ($item->transactions()->count() > 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Warning:</strong> This item has existing transactions.
                                Changing the valuation method will affect future cost calculations.
                            </div>
                        @endif
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Item
                            </button>
                            <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="{{ route('inventory-items.units.index', $item->id) }}" class="btn btn-secondary">
                                <i class="fas fa-cubes"></i> Manage Units
                            </a>
                        </div>
                        @if ($item->transactions()->count() == 0)
                            <button type="button" class="btn btn-danger" id="deleteItem">
                                <i class="fas fa-trash"></i> Delete Item
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Auto-calculate selling price based on purchase price
            $('#purchase_price').on('input', function() {
                const purchasePrice = parseFloat($(this).val()) || 0;
                const currentSellingPrice = parseFloat($('#selling_price').val()) || 0;

                // Only auto-calculate if selling price is empty or very close to old purchase price
                if (currentSellingPrice === 0 || Math.abs(currentSellingPrice -
                        {{ $item->purchase_price }}) < 0.01) {
                    const sellingPrice = purchasePrice * 1.2; // 20% markup
                    $('#selling_price').val(sellingPrice.toFixed(2));
                }
            });

            // Set reorder point to minimum stock level
            $('#min_stock_level').on('input', function() {
                const minLevel = parseInt($(this).val()) || 0;
                const currentReorderPoint = parseInt($('#reorder_point').val()) || 0;

                // Only auto-set if reorder point is empty or equal to old min level
                if (currentReorderPoint === 0 || currentReorderPoint === {{ $item->min_stock_level }}) {
                    $('#reorder_point').val(minLevel);
                }
            });

            // Validation
            $('form').on('submit', function(e) {
                const minLevel = parseInt($('#min_stock_level').val()) || 0;
                const maxLevel = parseInt($('#max_stock_level').val()) || 0;
                const reorderPoint = parseInt($('#reorder_point').val()) || 0;

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
            });

            // Delete item confirmation
            $('#deleteItem').on('click', function() {
                if (confirm(
                        'Are you sure you want to delete this inventory item? This action cannot be undone.'
                        )) {
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': '{{ route('inventory.destroy', $item->id) }}'
                    });

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    }));

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    }));

                    $('body').append(form);
                    form.submit();
                }
            });
        });
    </script>
@endsection
