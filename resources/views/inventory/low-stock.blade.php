@extends('layouts.main')

@section('title_page')
    Low Stock Alert
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active">Low Stock</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        Low Stock Alert
                    </h3>
                    <div>
                        <a href="{{ route('inventory.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Inventory
                        </a>
                        <button class="btn btn-primary btn-sm" id="generatePurchaseOrders">
                            <i class="fas fa-shopping-cart"></i> Generate Purchase Orders
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($items->count() > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> {{ $items->count() }} item(s) are below their reorder points and need
                            restocking.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Min Level</th>
                                        <th>Reorder Point</th>
                                        <th>Shortage</th>
                                        <th>Purchase Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td>{{ $item->code }}</td>
                                            <td>
                                                <a href="{{ route('inventory.show', $item->id) }}">
                                                    {{ $item->name }}
                                                </a>
                                            </td>
                                            <td>{{ $item->category->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    {{ $item->current_stock }} {{ $item->unit_of_measure }}
                                                </span>
                                            </td>
                                            <td>{{ $item->min_stock_level }} {{ $item->unit_of_measure }}</td>
                                            <td>{{ $item->reorder_point }} {{ $item->unit_of_measure }}</td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    {{ $item->reorder_point - $item->current_stock }}
                                                    {{ $item->unit_of_measure }}
                                                </span>
                                            </td>
                                            <td>Rp {{ number_format($item->purchase_price, 2) }}</td>
                                            <td>
                                                <a href="{{ route('inventory.show', $item->id) }}"
                                                    class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <button class="btn btn-sm btn-warning btn-adjust-stock"
                                                    data-item-id="{{ $item->id }}"
                                                    data-item-name="{{ $item->name }}">
                                                    <i class="fas fa-adjust"></i> Adjust
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <button class="btn btn-success btn-block mb-2" id="bulkAdjustStock">
                                            <i class="fas fa-adjust"></i> Bulk Stock Adjustment
                                        </button>
                                        <button class="btn btn-primary btn-block mb-2" id="bulkPurchaseOrder">
                                            <i class="fas fa-shopping-cart"></i> Create Bulk Purchase Order
                                        </button>
                                        <button class="btn btn-info btn-block" id="exportLowStock">
                                            <i class="fas fa-download"></i> Export Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-box">
                                                    <span class="info-box-icon bg-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Low Stock Items</span>
                                                        <span class="info-box-number">{{ $items->count() }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-box">
                                                    <span class="info-box-icon bg-danger">
                                                        <i class="fas fa-times"></i>
                                                    </span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Out of Stock</span>
                                                        <span
                                                            class="info-box-number">{{ $items->where('current_stock', 0)->count() }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-success">
                            <i class="fas fa-check-circle fa-5x mb-3"></i>
                            <h3>All Good!</h3>
                            <p class="lead">No items are currently below their reorder points.</p>
                            <a href="{{ route('inventory.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to Inventory
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Stock Adjustment Modal -->
    <div class="modal fade" id="bulkAdjustStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Stock Adjustment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="bulkAdjustStockForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Adjustment Type</label>
                            <select class="form-control" name="adjustment_type" required>
                                <option value="increase">Increase Stock</option>
                                <option value="decrease">Decrease Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Items to Adjust</label>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAll"></th>
                                            <th>Item</th>
                                            <th>Current Stock</th>
                                            <th>Adjustment Qty</th>
                                            <th>Unit Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="items[{{ $item->id }}][selected]"
                                                        class="item-checkbox" value="1">
                                                </td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->current_stock }} {{ $item->unit_of_measure }}</td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        name="items[{{ $item->id }}][quantity]" min="1"
                                                        value="1">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        name="items[{{ $item->id }}][unit_cost]" step="0.01"
                                                        min="0" value="{{ $item->purchase_price }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Adjust Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Purchase Order Modal -->
    <div class="modal fade" id="bulkPurchaseOrderModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Bulk Purchase Order</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="bulkPurchaseOrderForm" action="{{ route('purchase-orders.create') }}" method="GET">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Vendor</label>
                            <select class="form-control" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                @foreach (\App\Models\Vendor::where('is_active', true)->get() as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Items to Order</label>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAllPO"></th>
                                            <th>Item</th>
                                            <th>Current Stock</th>
                                            <th>Reorder Point</th>
                                            <th>Order Qty</th>
                                            <th>Unit Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="items[{{ $item->id }}][selected]"
                                                        class="po-item-checkbox" value="1">
                                                </td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->current_stock }} {{ $item->unit_of_measure }}</td>
                                                <td>{{ $item->reorder_point }} {{ $item->unit_of_measure }}</td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        name="items[{{ $item->id }}][quantity]" min="1"
                                                        value="{{ $item->reorder_point - $item->current_stock }}">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        name="items[{{ $item->id }}][unit_price]" step="0.01"
                                                        min="0" value="{{ $item->purchase_price }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Select all checkboxes
            $('#selectAll').on('change', function() {
                $('.item-checkbox').prop('checked', this.checked);
            });

            $('#selectAllPO').on('change', function() {
                $('.po-item-checkbox').prop('checked', this.checked);
            });

            // Individual checkbox change
            $('.item-checkbox').on('change', function() {
                if (!this.checked) {
                    $('#selectAll').prop('checked', false);
                }
            });

            $('.po-item-checkbox').on('change', function() {
                if (!this.checked) {
                    $('#selectAllPO').prop('checked', false);
                }
            });

            // Bulk stock adjustment
            $('#bulkAdjustStock').on('click', function() {
                $('#bulkAdjustStockModal').modal('show');
            });

            $('#bulkAdjustStockForm').on('submit', function(e) {
                e.preventDefault();

                const selectedItems = [];
                $('.item-checkbox:checked').each(function() {
                    const itemId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                    const quantity = $(`input[name="items[${itemId}][quantity]"]`).val();
                    const unitCost = $(`input[name="items[${itemId}][unit_cost]"]`).val();

                    if (quantity && unitCost) {
                        selectedItems.push({
                            item_id: itemId,
                            quantity: quantity,
                            unit_cost: unitCost
                        });
                    }
                });

                if (selectedItems.length === 0) {
                    toastr.error('Please select at least one item to adjust');
                    return;
                }

                const adjustmentType = $('select[name="adjustment_type"]').val();
                const notes = $('textarea[name="notes"]').val();

                // Process each adjustment
                let completed = 0;
                selectedItems.forEach(function(item) {
                    $.ajax({
                        url: '{{ route('inventory.adjust-stock', '') }}/' + item.item_id,
                        method: 'POST',
                        data: {
                            adjustment_type: adjustmentType,
                            quantity: item.quantity,
                            unit_cost: item.unit_cost,
                            notes: notes,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            completed++;
                            if (completed === selectedItems.length) {
                                $('#bulkAdjustStockModal').modal('hide');
                                location.reload();
                            }
                        },
                        error: function(xhr) {
                            toastr.error('Error adjusting stock for item: ' + xhr
                                .responseJSON.message);
                        }
                    });
                });
            });

            // Bulk purchase order
            $('#bulkPurchaseOrder').on('click', function() {
                $('#bulkPurchaseOrderModal').modal('show');
            });

            $('#bulkPurchaseOrderForm').on('submit', function(e) {
                const selectedItems = [];
                $('.po-item-checkbox:checked').each(function() {
                    const itemId = $(this).attr('name').match(/\[(\d+)\]/)[1];
                    const quantity = $(`input[name="items[${itemId}][quantity]"]`).val();
                    const unitPrice = $(`input[name="items[${itemId}][unit_price]"]`).val();

                    if (quantity && unitPrice) {
                        selectedItems.push({
                            item_id: itemId,
                            quantity: quantity,
                            unit_price: unitPrice
                        });
                    }
                });

                if (selectedItems.length === 0) {
                    e.preventDefault();
                    toastr.error('Please select at least one item to order');
                    return false;
                }

                // Add selected items as hidden inputs
                selectedItems.forEach(function(item, index) {
                    $(this).append(
                        `<input type="hidden" name="lines[${index}][item_id]" value="${item.item_id}">`
                        );
                    $(this).append(
                        `<input type="hidden" name="lines[${index}][quantity]" value="${item.quantity}">`
                        );
                    $(this).append(
                        `<input type="hidden" name="lines[${index}][unit_price]" value="${item.unit_price}">`
                        );
                }.bind(this));
            });

            // Export low stock report
            $('#exportLowStock').on('click', function() {
                window.location = '{{ route('inventory.export-low-stock') }}';
            });

            // Individual stock adjustment
            $('.btn-adjust-stock').on('click', function() {
                const itemId = $(this).data('item-id');
                const itemName = $(this).data('item-name');

                // Redirect to inventory show page with adjustment modal
                window.location = '{{ route('inventory.index') }}' + '#' + itemId;
            });
        });
    </script>
@endsection
