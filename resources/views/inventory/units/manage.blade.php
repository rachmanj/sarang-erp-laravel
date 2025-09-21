@extends('layouts.main')

@section('title_page')
    Manage Units - {{ $inventoryItem->name }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory-items.index') }}">Inventory Items</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory-items.show', $inventoryItem) }}">{{ $inventoryItem->name }}</a>
    </li>
    <li class="breadcrumb-item active">Manage Units</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">
                            <i class="fas fa-cubes mr-1"></i>
                            Manage Units - {{ $inventoryItem->name }}
                        </h3>
                    </div>
                    <div>
                        <a href="{{ route('inventory-items.show', $inventoryItem) }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Item
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Item Information -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Item Information
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <b>Code:</b>
                                            <div><strong>{{ $inventoryItem->code }}</strong></div>
                                        </div>
                                        <div class="col-md-3">
                                            <b>Name:</b>
                                            <div>{{ $inventoryItem->name }}</div>
                                        </div>
                                        <div class="col-md-3">
                                            <b>Type:</b>
                                            <div>
                                                <span
                                                    class="badge badge-{{ $inventoryItem->item_type === 'item' ? 'success' : 'info' }}">
                                                    {{ ucfirst($inventoryItem->item_type) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <b>Base Unit:</b>
                                            <div>
                                                @if ($inventoryItem->baseUnit)
                                                    <span class="badge badge-primary">
                                                        {{ $inventoryItem->baseUnit->unit->display_name }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning">Not Set</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add New Unit -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add New Unit
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('inventory-items.units.store', $inventoryItem) }}"
                                        method="POST">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="unit_type">Unit Type</label>
                                                    <select class="form-control" id="unit_type" name="unit_type" required>
                                                        <option value="">Select Unit Type</option>
                                                        @foreach ($unitTypes as $type => $name)
                                                            <option value="{{ $type }}">{{ $name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="unit_id">Unit</label>
                                                    <select class="form-control" id="unit_id" name="unit_id" required>
                                                        <option value="">Select Unit Type First</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="conversion_quantity">Conversion Qty</label>
                                                    <input type="number" class="form-control" id="conversion_quantity" name="conversion_quantity"
                                                        step="0.01" min="0.01" value="1" required>
                                                    <small class="form-text text-muted">How many base units this unit represents</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="purchase_price">Purchase Price</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" class="form-control" id="purchase_price"
                                                            name="purchase_price" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="selling_price">Selling Price</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" class="form-control" id="selling_price"
                                                            name="selling_price" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="is_base_unit"
                                                            name="is_base_unit" value="1">
                                                        <label class="form-check-label" for="is_base_unit">
                                                            Set as Base Unit
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-plus mr-1"></i> Add Unit
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Units -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-list mr-2"></i>
                                        Current Units
                                    </h3>
                                </div>
                                <div class="card-body">
                                    @if ($inventoryItem->itemUnits->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Unit</th>
                                                        <th>Type</th>
                                                        <th>Conversion</th>
                                                        <th>Purchase Price</th>
                                                        <th>Selling Price</th>
                                                        <th>Base Unit</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($inventoryItem->itemUnits as $itemUnit)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $itemUnit->unit->display_name }}</strong>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-secondary">{{ ucfirst($itemUnit->unit->unit_type) }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-info">{{ $itemUnit->conversion_display }}</span>
                                                            </td>
                                                            <td>Rp {{ number_format($itemUnit->purchase_price, 2) }}</td>
                                                            <td>Rp {{ number_format($itemUnit->selling_price, 2) }}</td>
                                                            <td>
                                                                @if ($itemUnit->is_base_unit)
                                                                    <span class="badge badge-success">Yes</span>
                                                                @else
                                                                    <span class="badge badge-secondary">No</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($itemUnit->is_active)
                                                                    <span class="badge badge-success">Active</span>
                                                                @else
                                                                    <span class="badge badge-danger">Inactive</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    @if (!$itemUnit->is_base_unit)
                                                                        <form
                                                                            action="{{ route('inventory-items.units.set-base', $inventoryItem) }}"
                                                                            method="POST" class="d-inline">
                                                                            @csrf
                                                                            <input type="hidden" name="unit_id"
                                                                                value="{{ $itemUnit->unit_id }}">
                                                                            <button type="submit"
                                                                                class="btn btn-primary btn-sm"
                                                                                title="Set as Base Unit">
                                                                                <i class="fas fa-star"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                    <form
                                                                        action="{{ route('inventory-items.units.destroy', [$inventoryItem, $itemUnit]) }}"
                                                                        method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn btn-danger btn-sm"
                                                                            onclick="return confirm('Are you sure you want to remove this unit?')"
                                                                            title="Remove">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No units configured for this item. Add a unit to get started.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Load units by type
            $('#unit_type').change(function() {
                var type = $(this).val();
                var unitSelect = $('#unit_id');

                unitSelect.empty().append('<option value="">Loading...</option>');

                if (type) {
                    $.get('{{ route('unit-of-measures.api.units-by-type') }}', {
                            type: type
                        })
                        .done(function(data) {
                            unitSelect.empty().append('<option value="">Select Unit</option>');
                            $.each(data, function(index, unit) {
                                unitSelect.append('<option value="' + unit.id + '">' + unit
                                    .display_name + '</option>');
                            });
                        })
                        .fail(function() {
                            unitSelect.empty().append('<option value="">Error loading units</option>');
                        });
                } else {
                    unitSelect.empty().append('<option value="">Select Unit Type First</option>');
                }
            });
        });
    </script>
@endpush
