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
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="unit_id">Unit</label>
                                                    <div class="input-group">
                                                        <select class="form-control" id="unit_id" name="unit_id" required>
                                                            <option value="">Select Unit</option>
                                                            @foreach (\App\Models\UnitOfMeasure::active()->orderBy('name')->get() as $unit)
                                                                <option value="{{ $unit->id }}">
                                                                    {{ $unit->display_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                id="btn-add-unit">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Select an existing unit or click + to create a new one.
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="conversion_quantity">Conversion Qty</label>
                                                    <input type="number" class="form-control" id="conversion_quantity" name="conversion_quantity"
                                                        step="0.01" min="0.01" value="1" required>
                                                    <small class="form-text text-muted">How many base units this unit represents</small>
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
                                                            Set as Base Unit (1 = base unit)
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
                                                        <th>Conversion</th>
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
                                                                <span class="badge badge-info">{{ $itemUnit->conversion_display }}</span>
                                                            </td>
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
            // Open quick-add unit modal
            $('#btn-add-unit').on('click', function() {
                $('#quickAddUnitModal').modal('show');
            });

            // Handle quick-add unit form submission
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
                            const select = $('#unit_id');
                            if (select.find('option[value="' + unit.id + '"]').length === 0) {
                                select.append('<option value="' + unit.id + '">' + unit.display_name +
                                    '</option>');
                            }
                            select.val(unit.id);
                        }
                        $('#quickAddUnitModal').modal('hide');
                        form[0].reset();
                    },
                    error: function(xhr) {
                        toastr.error('Failed to create unit of measure');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush

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
                            <label for="quick_unit_code">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_unit_code" name="code" required>
                        </div>
                        <div class="form-group">
                            <label for="quick_unit_name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_unit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="quick_unit_description">Description</label>
                            <textarea class="form-control" id="quick_unit_description" name="description" rows="2"></textarea>
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
