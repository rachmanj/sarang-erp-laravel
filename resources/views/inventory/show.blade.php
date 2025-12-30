@extends('layouts.main')

@section('title_page')
    Inventory Item Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
    <li class="breadcrumb-item active">{{ $item->name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Item Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Code:</strong></td>
                            <td>{{ $item->code }}</td>
                        </tr>
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>{{ $item->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Category:</strong></td>
                            <td>{{ $item->category->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Description:</strong></td>
                            <td>{{ $item->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Unit:</strong></td>
                            <td>{{ $item->unit_of_measure }}</td>
                        </tr>
                        <tr>
                            <td><strong>Purchase Price:</strong></td>
                            <td>Rp {{ number_format($item->purchase_price, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Selling Price:</strong></td>
                            <td>Rp {{ number_format($item->selling_price, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Margin:</strong></td>
                            <td>
                                @php
                                    $margin = $item->selling_price - $item->purchase_price;
                                    $marginPercent =
                                        $item->purchase_price > 0 ? ($margin / $item->purchase_price) * 100 : 0;
                                @endphp
                                Rp {{ number_format($margin, 2) }} ({{ number_format($marginPercent, 1) }}%)
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stock Levels</h3>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Current Stock:</strong></td>
                            <td>
                                <span
                                    class="badge badge-{{ $item->current_stock <= 0 ? 'danger' : ($item->current_stock <= $item->min_stock_level ? 'warning' : 'success') }}">
                                    {{ $item->current_stock }} {{ $item->unit_of_measure }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Min Level:</strong></td>
                            <td>{{ $item->min_stock_level }} {{ $item->unit_of_measure }}</td>
                        </tr>
                        <tr>
                            <td><strong>Max Level:</strong></td>
                            <td>{{ $item->max_stock_level }} {{ $item->unit_of_measure }}</td>
                        </tr>
                        <tr>
                            <td><strong>Reorder Point:</strong></td>
                            <td>{{ $item->reorder_point }} {{ $item->unit_of_measure }}</td>
                        </tr>
                        <tr>
                            <td><strong>Valuation Method:</strong></td>
                            <td>
                                <span class="badge badge-info">
                                    {{ strtoupper($item->valuation_method) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Current Value:</strong></td>
                            <td>Rp {{ number_format($item->current_value, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Actions</h3>
                    <div class="btn-group" role="group">
                        <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @can('admin.view')
                            <a href="{{ route('audit-logs.show', ['inventory_item', $item->id]) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-history"></i> Audit Trail
                            </a>
                        @endcan
                        <a href="{{ route('inventory-items.units.index', $item->id) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-cubes"></i> Manage Units
                        </a>
                        <button class="btn btn-warning btn-sm btn-adjust-stock" data-item-id="{{ $item->id }}"
                            data-item-name="{{ $item->name }}">
                            <i class="fas fa-adjust"></i> Adjust Stock
                        </button>
                        <button class="btn btn-info btn-sm btn-transfer-stock" data-item-id="{{ $item->id }}"
                            data-item-name="{{ $item->name }}">
                            <i class="fas fa-exchange-alt"></i> Transfer Stock
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-boxes"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Current Stock</span>
                                    <span class="info-box-number">{{ $item->current_stock }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Value</span>
                                    <span class="info-box-number">Rp {{ number_format($item->current_value, 0) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Reorder Point</span>
                                    <span class="info-box-number">{{ $item->reorder_point }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-chart-line"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Margin %</span>
                                    <span class="info-box-number">
                                        @php
                                            $marginPercent =
                                                $item->purchase_price > 0
                                                    ? (($item->selling_price - $item->purchase_price) /
                                                            $item->purchase_price) *
                                                        100
                                                    : 0;
                                        @endphp
                                        {{ number_format($marginPercent, 1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                </div>
                <div class="card-body">
                    @if ($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Cost</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge badge-{{ $transaction->transaction_type == 'purchase'
                                                        ? 'success'
                                                        : ($transaction->transaction_type == 'sale'
                                                            ? 'danger'
                                                            : ($transaction->transaction_type == 'adjustment'
                                                                ? 'warning'
                                                                : 'info')) }}">
                                                    {{ ucfirst($transaction->transaction_type) }}
                                                </span>
                                            </td>
                                            <td class="{{ $transaction->quantity < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $transaction->quantity > 0 ? '+' : '' }}{{ $transaction->quantity }}
                                            </td>
                                            <td>Rp {{ number_format($transaction->unit_cost, 2) }}</td>
                                            <td
                                                class="{{ $transaction->total_cost < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $transaction->total_cost > 0 ? '+' : '' }}Rp
                                                {{ number_format($transaction->total_cost, 2) }}
                                            </td>
                                            <td>
                                                @if ($transaction->reference_type && $transaction->reference_id)
                                                    {{ ucfirst(str_replace('_', ' ', $transaction->reference_type)) }}
                                                    #{{ $transaction->reference_id }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $transaction->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $transactions->links() }}
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No transactions found for this item.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Valuation History</h3>
                </div>
                <div class="card-body">
                    @if ($valuations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Value</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($valuations as $valuation)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($valuation->valuation_date)->format('d/m/Y') }}
                                            </td>
                                            <td>{{ $valuation->quantity_on_hand }}</td>
                                            <td>Rp {{ number_format($valuation->unit_cost, 2) }}</td>
                                            <td>Rp {{ number_format($valuation->total_value, 2) }}</td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ strtoupper($valuation->valuation_method) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $valuations->links() }}
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <p>No valuation history found for this item.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail Widget -->
    @can('admin.view')
    <div class="row">
        <div class="col-12">
            <x-audit-trail-widget 
                entity-type="inventory_item" 
                :entity-id="$item->id" 
                :limit="10" 
                :collapsible="true" />
        </div>
    </div>
    @endcan

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock - {{ $item->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="adjustStockForm">
                    <div class="modal-body">
                        <input type="hidden" id="adjust_item_id" name="item_id" value="{{ $item->id }}">
                        <div class="form-group">
                            <label>Adjustment Type</label>
                            <select class="form-control" name="adjustment_type" required>
                                <option value="increase">Increase Stock</option>
                                <option value="decrease">Decrease Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Unit Cost</label>
                            <input type="number" class="form-control" name="unit_cost" step="0.01" min="0"
                                value="{{ $item->purchase_price }}" required>
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

    <!-- Stock Transfer Modal -->
    <div class="modal fade" id="transferStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Stock - {{ $item->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="transferStockForm">
                    <div class="modal-body">
                        <input type="hidden" id="transfer_from_item_id" name="from_item_id"
                            value="{{ $item->id }}">
                        <div class="form-group">
                            <label>Transfer To</label>
                            <select class="form-control" name="to_item_id" required>
                                <option value="">Select Item</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="1"
                                max="{{ $item->current_stock }}" required>
                        </div>
                        <div class="form-group">
                            <label>Unit Cost</label>
                            <input type="number" class="form-control" name="unit_cost" step="0.01" min="0"
                                value="{{ $item->purchase_price }}" required>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Transfer Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            // Stock adjustment modal
            $('.btn-adjust-stock').on('click', function() {
                const itemId = $(this).data('item-id');
                const itemName = $(this).data('item-name');
                $('#adjust_item_id').val(itemId);
                $('#adjustStockModal .modal-title').text('Adjust Stock - ' + itemName);
                $('#adjustStockModal').modal('show');
            });

            // Stock transfer modal
            $('.btn-transfer-stock').on('click', function() {
                const itemId = $(this).data('item-id');
                const itemName = $(this).data('item-name');
                $('#transfer_from_item_id').val(itemId);
                $('#transferStockModal .modal-title').text('Transfer Stock - ' + itemName);
                $('#transferStockModal').modal('show');
            });

            // Stock adjustment form
            $('#adjustStockForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: '{{ route('inventory.adjust-stock', $item->id) }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#adjustStockModal').modal('hide');
                        location.reload();
                    },
                    error: function(xhr) {
                        toastr.error('Error adjusting stock: ' + xhr.responseJSON.message);
                    }
                });
            });

            // Stock transfer form
            $('#transferStockForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: '{{ route('inventory.transfer-stock', $item->id) }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#transferStockModal').modal('hide');
                        location.reload();
                    },
                    error: function(xhr) {
                        toastr.error('Error transferring stock: ' + xhr.responseJSON.message);
                    }
                });
            });

            // Load available items for transfer
            $('#transferStockModal').on('show.bs.modal', function() {
                $.get('{{ route('inventory.get-items') }}', function(items) {
                    const select = $('#transferStockModal select[name="to_item_id"]');
                    select.empty().append('<option value="">Select Item</option>');
                    items.forEach(function(item) {
                        if (item.id != {{ $item->id }}) {
                            select.append('<option value="' + item.id + '">' + item.name +
                                ' (' + item.code + ')</option>');
                        }
                    });
                });
            });
        });
    </script>
@endsection
