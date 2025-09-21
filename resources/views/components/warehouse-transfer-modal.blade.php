<!-- Warehouse Transfer Modal -->
<div class="modal fade" id="warehouseTransferModal" tabindex="-1" role="dialog"
    aria-labelledby="warehouseTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="warehouseTransferModalLabel">
                    <i class="fas fa-exchange-alt mr-2"></i>Transfer Stock Between Warehouses
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="warehouseTransferForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transfer_item_id">Item <span class="text-danger">*</span></label>
                                <select class="form-control" id="transfer_item_id" name="item_id" required>
                                    <option value="">Select Item</option>
                                </select>
                                <small class="form-text text-muted">Select the item to transfer</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transfer_quantity">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="transfer_quantity" name="quantity"
                                    min="1" required>
                                <small class="form-text text-muted">Available: <span id="available_stock">0</span>
                                    units</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transfer_from_warehouse">From Warehouse <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="transfer_from_warehouse" name="from_warehouse_id"
                                    required>
                                    <option value="">Select Source Warehouse</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transfer_to_warehouse">To Warehouse <span
                                        class="text-danger">*</span></label>
                                <select class="form-control" id="transfer_to_warehouse" name="to_warehouse_id" required>
                                    <option value="">Select Destination Warehouse</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="transfer_notes">Notes</label>
                        <textarea class="form-control" id="transfer_notes" name="notes" rows="3"
                            placeholder="Optional notes about this transfer..."></textarea>
                    </div>

                    <!-- Stock Information Display -->
                    <div class="card bg-light" id="stock_info_card" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>Stock Information
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Source Stock:</small>
                                    <div class="font-weight-bold" id="source_stock">0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Destination Stock:</small>
                                    <div class="font-weight-bold" id="destination_stock">0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">After Transfer:</small>
                                    <div class="font-weight-bold" id="after_transfer">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="transfer_submit_btn">
                        <i class="fas fa-exchange-alt mr-1"></i>Transfer Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            let warehouses = [];
            let items = [];
            let currentStock = {};

            // Load warehouses when modal opens
            $('#warehouseTransferModal').on('show.bs.modal', function() {
                loadWarehouses();
                loadItems();
            });

            // Load warehouses
            function loadWarehouses() {
                $.get('{{ route('warehouses.get-warehouses') }}')
                    .done(function(data) {
                        warehouses = data;
                        const fromSelect = $('#transfer_from_warehouse');
                        const toSelect = $('#transfer_to_warehouse');

                        fromSelect.empty().append('<option value="">Select Source Warehouse</option>');
                        toSelect.empty().append('<option value="">Select Destination Warehouse</option>');

                        data.forEach(function(warehouse) {
                            fromSelect.append(
                                `<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name}</option>`
                                );
                            toSelect.append(
                                `<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name}</option>`
                                );
                        });
                    })
                    .fail(function() {
                        showAlert('Error loading warehouses', 'error');
                    });
            }

            // Load items
            function loadItems() {
                $.get('{{ route('inventory.get-items') }}')
                    .done(function(data) {
                        items = data;
                        const itemSelect = $('#transfer_item_id');

                        itemSelect.empty().append('<option value="">Select Item</option>');

                        data.forEach(function(item) {
                            itemSelect.append(
                                `<option value="${item.id}">${item.code} - ${item.name}</option>`);
                        });
                    })
                    .fail(function() {
                        showAlert('Error loading items', 'error');
                    });
            }

            // Handle item selection
            $('#transfer_item_id').on('change', function() {
                const itemId = $(this).val();
                if (itemId) {
                    loadItemStock(itemId);
                } else {
                    resetStockInfo();
                }
            });

            // Handle warehouse selection
            $('#transfer_from_warehouse, #transfer_to_warehouse').on('change', function() {
                updateStockInfo();
            });

            // Handle quantity change
            $('#transfer_quantity').on('input', function() {
                updateStockInfo();
            });

            // Load item stock for all warehouses
            function loadItemStock(itemId) {
                $.get(`{{ route('warehouses.get-item-stock', ':itemId') }}`.replace(':itemId', itemId))
                    .done(function(data) {
                        currentStock = data;
                        updateStockInfo();
                    })
                    .fail(function() {
                        showAlert('Error loading stock information', 'error');
                        currentStock = {};
                    });
            }

            // Update stock information display
            function updateStockInfo() {
                const itemId = $('#transfer_item_id').val();
                const fromWarehouseId = $('#transfer_from_warehouse').val();
                const toWarehouseId = $('#transfer_to_warehouse').val();
                const quantity = parseInt($('#transfer_quantity').val()) || 0;

                if (!itemId || !fromWarehouseId || !toWarehouseId) {
                    $('#stock_info_card').hide();
                    return;
                }

                const fromStock = currentStock[fromWarehouseId] || 0;
                const toStock = currentStock[toWarehouseId] || 0;
                const afterTransfer = toStock + quantity;

                $('#source_stock').text(fromStock);
                $('#destination_stock').text(toStock);
                $('#after_transfer').text(afterTransfer);
                $('#available_stock').text(fromStock);

                // Update quantity max
                $('#transfer_quantity').attr('max', fromStock);

                // Show/hide stock info card
                $('#stock_info_card').show();

                // Validate quantity
                validateTransfer();
            }

            // Reset stock information
            function resetStockInfo() {
                $('#stock_info_card').hide();
                $('#available_stock').text('0');
                $('#transfer_quantity').attr('max', '');
                currentStock = {};
            }

            // Validate transfer
            function validateTransfer() {
                const itemId = $('#transfer_item_id').val();
                const fromWarehouseId = $('#transfer_from_warehouse').val();
                const toWarehouseId = $('#transfer_to_warehouse').val();
                const quantity = parseInt($('#transfer_quantity').val()) || 0;

                let isValid = true;
                let errorMessage = '';

                if (!itemId) {
                    isValid = false;
                    errorMessage = 'Please select an item';
                } else if (!fromWarehouseId) {
                    isValid = false;
                    errorMessage = 'Please select source warehouse';
                } else if (!toWarehouseId) {
                    isValid = false;
                    errorMessage = 'Please select destination warehouse';
                } else if (fromWarehouseId === toWarehouseId) {
                    isValid = false;
                    errorMessage = 'Source and destination warehouses must be different';
                } else if (quantity <= 0) {
                    isValid = false;
                    errorMessage = 'Quantity must be greater than 0';
                } else if (quantity > (currentStock[fromWarehouseId] || 0)) {
                    isValid = false;
                    errorMessage = 'Insufficient stock in source warehouse';
                }

                $('#transfer_submit_btn').prop('disabled', !isValid);

                if (!isValid && errorMessage) {
                    $('#transfer_submit_btn').attr('title', errorMessage);
                } else {
                    $('#transfer_submit_btn').removeAttr('title');
                }
            }

            // Handle form submission
            $('#warehouseTransferForm').on('submit', function(e) {
                e.preventDefault();

                const formData = {
                    item_id: $('#transfer_item_id').val(),
                    from_warehouse_id: $('#transfer_from_warehouse').val(),
                    to_warehouse_id: $('#transfer_to_warehouse').val(),
                    quantity: $('#transfer_quantity').val(),
                    notes: $('#transfer_notes').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $('#transfer_submit_btn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i>Transferring...');

                $.ajax({
                    url: '{{ route('warehouses.transfer-stock') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            $('#warehouseTransferModal').modal('hide');
                            resetForm();

                            // Refresh data if on a specific page
                            if (typeof refreshData === 'function') {
                                refreshData();
                            }
                        } else {
                            showAlert(response.message || 'Transfer failed', 'error');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'Transfer failed';
                        showAlert(errorMessage, 'error');
                    },
                    complete: function() {
                        $('#transfer_submit_btn').prop('disabled', false).html(
                            '<i class="fas fa-exchange-alt mr-1"></i>Transfer Stock');
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#warehouseTransferForm')[0].reset();
                resetStockInfo();
                $('#transfer_submit_btn').prop('disabled', true);
            }

            // Show alert
            function showAlert(message, type) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;

                // Remove existing alerts
                $('.alert').remove();

                // Add new alert
                $('.content').prepend(alertHtml);

                // Auto-hide after 5 seconds
                setTimeout(function() {
                    $('.alert').fadeOut();
                }, 5000);
            }
        });
    </script>
@endpush
