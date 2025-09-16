@extends('layouts.main')

@section('title_page')
    Inventory Management
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Inventory</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Item
                        </a>
                        <a href="{{ route('inventory.low-stock') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-exclamation-triangle"></i> Low Stock
                        </a>
                        <a href="{{ route('inventory.valuation-report') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-chart-line"></i> Valuation Report
                        </a>
                    </div>
                    <form class="form-inline" id="filters">
                        <input type="text" name="q" class="form-control form-control-sm mr-1"
                            placeholder="Search items...">
                        <select name="category" class="form-control form-control-sm mr-1">
                            <option value="">All Categories</option>
                            @foreach (\App\Models\ProductCategory::where('is_active', true)->get() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <select name="valuation_method" class="form-control form-control-sm mr-1">
                            <option value="">All Methods</option>
                            <option value="fifo">FIFO</option>
                            <option value="lifo">LIFO</option>
                            <option value="weighted_average">Weighted Average</option>
                        </select>
                        <select name="stock_status" class="form-control form-control-sm mr-1">
                            <option value="">All Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="out">Out of Stock</option>
                            <option value="available">In Stock</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a class="btn btn-sm btn-outline-secondary ml-1" id="export" href="#">
                            <i class="fas fa-download"></i> Export
                        </a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tbl-inventory">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Current Stock</th>
                                <th>Min Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="adjustStockForm">
                    <div class="modal-body">
                        <input type="hidden" id="adjust_item_id" name="item_id">
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
                                required>
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
                    <h5 class="modal-title">Transfer Stock</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="transferStockForm">
                    <div class="modal-body">
                        <input type="hidden" id="transfer_from_item_id" name="from_item_id">
                        <div class="form-group">
                            <label>Transfer To</label>
                            <select class="form-control" name="to_item_id" required>
                                <option value="">Select Item</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Unit Cost</label>
                            <input type="number" class="form-control" name="unit_cost" step="0.01" min="0"
                                required>
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
            const table = $('#tbl-inventory').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('inventory.data') }}',
                    data: function(d) {
                        const f = $('#filters').serializeArray();
                        f.forEach(p => d[p.name] = p.value);
                    }
                },
                columns: [{
                        data: 'code'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'category'
                    },
                    {
                        data: 'unit_of_measure'
                    },
                    {
                        data: 'purchase_price',
                        render: function(data) {
                            return 'Rp ' + parseFloat(data).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'selling_price',
                        render: function(data) {
                            return 'Rp ' + parseFloat(data).toLocaleString('id-ID');
                        }
                    },
                    {
                        data: 'current_stock',
                        render: function(data, type, row) {
                            const stock = parseInt(data);
                            const minLevel = parseInt(row.min_stock_level);
                            let badge = '';
                            if (stock <= 0) {
                                badge = '<span class="badge badge-danger">Out</span>';
                            } else if (stock <= minLevel) {
                                badge = '<span class="badge badge-warning">Low</span>';
                            } else {
                                badge = '<span class="badge badge-success">OK</span>';
                            }
                            return stock + ' ' + badge;
                        }
                    },
                    {
                        data: 'min_stock_level'
                    },
                    {
                        data: 'is_active',
                        render: function(data) {
                            return data ? '<span class="badge badge-success">Active</span>' :
                                '<span class="badge badge-secondary">Inactive</span>';
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#filters').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $('#export').on('click', function(e) {
                e.preventDefault();
                this.href = '{{ route('inventory.export') }}?' + $('#filters').serialize();
                window.location = this.href;
            });

            // Stock adjustment modal
            $(document).on('click', '.btn-adjust-stock', function() {
                const itemId = $(this).data('item-id');
                const itemName = $(this).data('item-name');
                $('#adjust_item_id').val(itemId);
                $('#adjustStockModal .modal-title').text('Adjust Stock - ' + itemName);
                $('#adjustStockModal').modal('show');
            });

            $('#adjustStockForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: '/inventory/' + $('#adjust_item_id').val() + '/adjust-stock',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#adjustStockModal').modal('hide');
                        table.ajax.reload();
                        toastr.success('Stock adjusted successfully');
                    },
                    error: function(xhr) {
                        toastr.error('Error adjusting stock: ' + xhr.responseJSON.message);
                    }
                });
            });

            // Stock transfer modal
            $(document).on('click', '.btn-transfer-stock', function() {
                const itemId = $(this).data('item-id');
                const itemName = $(this).data('item-name');
                $('#transfer_from_item_id').val(itemId);
                $('#transferStockModal .modal-title').text('Transfer Stock - ' + itemName);

                // Load available items for transfer
                $.get('{{ route('inventory.get-items') }}', function(items) {
                    const select = $('#transferStockModal select[name="to_item_id"]');
                    select.empty().append('<option value="">Select Item</option>');
                    items.forEach(function(item) {
                        if (item.id != itemId) {
                            select.append('<option value="' + item.id + '">' + item.name +
                                ' (' + item.code + ')</option>');
                        }
                    });
                });

                $('#transferStockModal').modal('show');
            });

            $('#transferStockForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: '/inventory/' + $('#transfer_from_item_id').val() + '/transfer-stock',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#transferStockModal').modal('hide');
                        table.ajax.reload();
                        toastr.success('Stock transfer completed successfully');
                    },
                    error: function(xhr) {
                        toastr.error('Error transferring stock: ' + xhr.responseJSON.message);
                    }
                });
            });
        });
    </script>
@endsection
