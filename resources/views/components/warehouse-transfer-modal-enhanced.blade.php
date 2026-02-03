<!-- Enhanced Warehouse Transfer Modal -->
<div class="modal fade" id="warehouseTransferModalEnhanced" tabindex="-1" role="dialog"
    aria-labelledby="warehouseTransferModalEnhancedLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="warehouseTransferModalEnhancedLabel">
                    <i class="fas fa-exchange-alt mr-1"></i>
                    Warehouse Transfer
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Transfer Type Selection -->
                <div class="form-group">
                    <label for="transfer_type">Transfer Type:</label>
                    <select class="form-control" id="transfer_type" name="transfer_type">
                        <option value="direct">Direct Transfer (Immediate)</option>
                        <option value="ito">Inventory Transfer Out (ITO)</option>
                        <option value="iti">Inventory Transfer In (ITI)</option>
                    </select>
                </div>

                <!-- Direct Transfer Form -->
                <div id="direct_transfer_form">
                    <form id="directTransferForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direct_item_id">Item:</label>
                                    <select class="form-control" id="direct_item_id" name="item_id" required>
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direct_quantity">Quantity:</label>
                                    <input type="number" class="form-control" id="direct_quantity" name="quantity"
                                        min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direct_from_warehouse">From Warehouse:</label>
                                    <select class="form-control" id="direct_from_warehouse" name="from_warehouse_id"
                                        required>
                                        <option value="">Select Source Warehouse</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direct_to_warehouse">To Warehouse:</label>
                                    <select class="form-control" id="direct_to_warehouse" name="to_warehouse_id"
                                        required>
                                        <option value="">Select Destination Warehouse</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="direct_notes">Notes:</label>
                            <textarea class="form-control" id="direct_notes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>

                <!-- ITO Form -->
                <div id="ito_form" style="display: none;">
                    <form id="itoForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ito_item_id">Item:</label>
                                    <select class="form-control" id="ito_item_id" name="item_id" required>
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ito_quantity">Quantity:</label>
                                    <input type="number" class="form-control" id="ito_quantity" name="quantity"
                                        min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ito_from_warehouse">From Warehouse:</label>
                                    <select class="form-control" id="ito_from_warehouse" name="from_warehouse_id"
                                        required>
                                        <option value="">Select Source Warehouse</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ito_to_warehouse">To Warehouse:</label>
                                    <select class="form-control" id="ito_to_warehouse" name="to_warehouse_id" required>
                                        <option value="">Select Destination Warehouse</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ito_notes">Notes:</label>
                            <textarea class="form-control" id="ito_notes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>

                <!-- ITI Form -->
                <div id="iti_form" style="display: none;">
                    <form id="itiForm">
                        <div class="form-group">
                            <label for="iti_transfer_out_id">Select Transfer Out (ITO):</label>
                            <select class="form-control" id="iti_transfer_out_id" name="transfer_out_id" required>
                                <option value="">Select Pending Transfer</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="iti_received_quantity">Received Quantity:</label>
                                    <input type="number" class="form-control" id="iti_received_quantity"
                                        name="received_quantity" min="1">
                                    <small class="form-text text-muted">Leave empty to receive full quantity</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="iti_original_quantity">Original Quantity:</label>
                                    <input type="text" class="form-control" id="iti_original_quantity" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="iti_notes">Notes:</label>
                            <textarea class="form-control" id="iti_notes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>

                <!-- Stock Information Card -->
                <div class="card mt-3" id="stock_info_card" style="display: none;">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-boxes mr-1"></i>
                            Stock Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Item Details -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Selected Item:</strong>
                                <span id="selected_item_info">-</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Unit of Measure:</strong>
                                <span id="item_unit">-</span>
                            </div>
                        </div>

                        <!-- Stock Levels -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <strong>Source Warehouse</strong><br>
                                    <span class="badge badge-info" id="source_warehouse_name">-</span><br>
                                    <span class="h5 text-primary" id="source_stock">0</span>
                                    <small class="text-muted d-block">Available</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <strong>Destination Warehouse</strong><br>
                                    <span class="badge badge-success" id="destination_warehouse_name">-</span><br>
                                    <span class="h5 text-success" id="destination_stock">0</span>
                                    <small class="text-muted d-block">Current Stock</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <strong>After Transfer</strong><br>
                                    <span class="badge badge-warning"
                                        id="destination_warehouse_name_after">-</span><br>
                                    <span class="h5 text-warning" id="after_transfer">0</span>
                                    <small class="text-muted d-block">Final Stock</small>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Status Indicators -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info mb-0" id="stock_status_alert" style="display: none;">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <span id="stock_status_message"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="transfer_submit_btn" disabled>
                    <i class="fas fa-exchange-alt mr-1"></i>
                    Process Transfer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Make functions available globally to avoid scope issues
    let warehouses = [];
    let items = [];
    let currentStock = {};
    let pendingTransfers = [];

    // Load warehouses
    function loadWarehouses() {
        console.log('Loading warehouses...');
        $.get('/warehouses/api/warehouses')
            .done(function(data) {
                console.log('Warehouses loaded:', data.length);
                warehouses = data;
                populateWarehouseSelects();
            })
            .fail(function(xhr, status, error) {
                console.error('Error loading warehouses:', error, xhr.responseText);
                showAlert('Error loading warehouses: ' + error, 'error');
            });
    }

    // Load items
    function loadItems() {
        console.log('Loading items...');
        $.get('/inventory/api/items')
            .done(function(data) {
                console.log('Items loaded:', data.length);
                items = data;
                populateItemSelects();
            })
            .fail(function(xhr, status, error) {
                console.error('Error loading items:', error, xhr.responseText);
                showAlert('Error loading items: ' + error, 'error');
            });
    }

    // Load pending transfers
    function loadPendingTransfers() {
        $.get('{{ route('warehouses.pending-transfers') }}')
            .done(function(data) {
                pendingTransfers = data;
                populatePendingTransfers();
            })
            .fail(function() {
                showAlert('Error loading pending transfers', 'error');
            });
    }

    // Populate warehouse selects
    function populateWarehouseSelects() {
        const selects = ['#direct_from_warehouse', '#direct_to_warehouse', '#ito_from_warehouse',
            '#ito_to_warehouse'
        ];

        selects.forEach(function(selector) {
            const select = $(selector);
            select.empty().append('<option value="">Select Warehouse</option>');

            warehouses.forEach(function(warehouse) {
                if (!warehouse.is_transit) { // Only show physical warehouses
                    select.append(
                        `<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name}</option>`
                    );
                }
            });
        });
    }

    // Populate item selects
    function populateItemSelects() {
        const selects = ['#direct_item_id', '#ito_item_id'];

        selects.forEach(function(selector) {
            const select = $(selector);
            select.empty().append('<option value="">Select Item</option>');

            items.forEach(function(item) {
                select.append(
                    `<option value="${item.id}">${item.code} - ${item.name}</option>`);
            });
        });
    }

    // Populate pending transfers
    function populatePendingTransfers() {
        const select = $('#iti_transfer_out_id');
        select.empty().append('<option value="">Select Pending Transfer</option>');

        pendingTransfers.forEach(function(transfer) {
            const itemName = transfer.item ? `${transfer.item.code} - ${transfer.item.name}` :
                'Unknown Item';
            const warehouseName = transfer.warehouse ? transfer.warehouse.name :
                'Unknown Warehouse';
            select.append(
                `<option value="${transfer.id}" data-quantity="${Math.abs(transfer.quantity)}">${itemName} from ${warehouseName} (Qty: ${Math.abs(transfer.quantity)})</option>`
            );
        });
    }

    $(document).ready(function() {
        // Use event delegation to ensure handlers are attached
        $(document).on('show.bs.modal', '#warehouseTransferModalEnhanced', function() {
            console.log('Modal opening, loading data...');
            loadWarehouses();
            loadItems();
            loadPendingTransfers();
        });

        // Also trigger on shown event as a fallback
        $(document).on('shown.bs.modal', '#warehouseTransferModalEnhanced', function() {
            console.log('Modal shown, checking data...');
            // Ensure data is loaded even if show event didn't fire
            const itemSelect = $('#direct_item_id');
            const warehouseSelect = $('#direct_from_warehouse');
            if (itemSelect.length > 0 && itemSelect.find('option').length <= 1) {
                console.log('Items not loaded, triggering loadItems...');
                loadItems();
            }
            if (warehouseSelect.length > 0 && warehouseSelect.find('option').length <= 1) {
                console.log('Warehouses not loaded, triggering loadWarehouses...');
                loadWarehouses();
            }
        });

        // Also handle button clicks that open the modal
        $(document).on('click',
            '[data-target="#warehouseTransferModalEnhanced"], [data-toggle="modal"][data-target="#warehouseTransferModalEnhanced"]',
            function() {
                console.log('Transfer Stock button clicked, pre-loading data...');
                // Pre-load data when button is clicked
                setTimeout(function() {
                    loadWarehouses();
                    loadItems();
                    loadPendingTransfers();
                }, 100);
            });

        // Handle transfer type change
        $('#transfer_type').on('change', function() {
            const transferType = $(this).val();

            // Hide all forms
            $('#direct_transfer_form, #ito_form, #iti_form').hide();

            // Show selected form
            if (transferType === 'direct') {
                $('#direct_transfer_form').show();
            } else if (transferType === 'ito') {
                $('#ito_form').show();
            } else if (transferType === 'iti') {
                $('#iti_form').show();
            }

            resetStockInfo();
            $('#transfer_submit_btn').prop('disabled', true);
        });

        // Handle ITI transfer selection
        $('#iti_transfer_out_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const quantity = selectedOption.data('quantity');

            if (quantity) {
                $('#iti_original_quantity').val(quantity);
                $('#iti_received_quantity').attr('max', quantity);
            } else {
                $('#iti_original_quantity').val('');
                $('#iti_received_quantity').attr('max', '');
            }
        });

        // Handle item selection for stock info
        $('#direct_item_id, #ito_item_id').on('change', function() {
            const itemId = $(this).val();
            if (itemId) {
                loadItemStock(itemId);
            } else {
                resetStockInfo();
            }
        });

        // Handle warehouse selection for stock info
        $('#direct_from_warehouse, #direct_to_warehouse, #ito_from_warehouse, #ito_to_warehouse').on('change',
            function() {
                updateStockInfo();
            });

        // Handle quantity change for stock info
        $('#direct_quantity, #ito_quantity').on('input', function() {
            updateStockInfo();
        });

        // Load item stock for all warehouses
        function loadItemStock(itemId) {
            $.get(`{{ route('warehouses.get-item-stock', ':itemId') }}`.replace(':itemId', itemId))
                .done(function(data) {
                    currentStock = {};
                    data.forEach(function(stock) {
                        currentStock[stock.warehouse_id] = stock.quantity_on_hand;
                    });
                    updateStockInfo();
                })
                .fail(function() {
                    showAlert('Error loading stock information', 'error');
                    currentStock = {};
                });
        }

        // Update stock information display
        function updateStockInfo() {
            const transferType = $('#transfer_type').val();

            if (transferType === 'iti') {
                $('#stock_info_card').hide();
                return;
            }

            const itemId = transferType === 'direct' ? $('#direct_item_id').val() : $('#ito_item_id').val();
            const fromWarehouseId = transferType === 'direct' ? $('#direct_from_warehouse').val() : $(
                '#ito_from_warehouse').val();
            const toWarehouseId = transferType === 'direct' ? $('#direct_to_warehouse').val() : $(
                '#ito_to_warehouse').val();
            const quantity = parseInt(transferType === 'direct' ? $('#direct_quantity').val() : $(
                '#ito_quantity').val()) || 0;

            if (!itemId || !fromWarehouseId || !toWarehouseId) {
                $('#stock_info_card').hide();
                return;
            }

            // Get item information
            const selectedItem = items.find(item => item.id == itemId);
            const fromWarehouse = warehouses.find(w => w.id == fromWarehouseId);
            const toWarehouse = warehouses.find(w => w.id == toWarehouseId);

            // Update item information
            if (selectedItem) {
                $('#selected_item_info').text(`${selectedItem.code} - ${selectedItem.name}`);
                $('#item_unit').text(selectedItem.unit_of_measure || 'PCS');
            }

            // Update warehouse names
            if (fromWarehouse) {
                $('#source_warehouse_name').text(`${fromWarehouse.code} - ${fromWarehouse.name}`);
            }
            if (toWarehouse) {
                $('#destination_warehouse_name').text(`${toWarehouse.code} - ${toWarehouse.name}`);
                $('#destination_warehouse_name_after').text(`${toWarehouse.code} - ${toWarehouse.name}`);
            }

            const fromStock = currentStock[fromWarehouseId] || 0;
            const toStock = currentStock[toWarehouseId] || 0;
            const afterTransfer = toStock + quantity;

            // Update stock numbers with formatting
            $('#source_stock').text(formatNumber(fromStock));
            $('#destination_stock').text(formatNumber(toStock));
            $('#after_transfer').text(formatNumber(afterTransfer));

            // Update quantity max
            const quantityInput = transferType === 'direct' ? $('#direct_quantity') : $('#ito_quantity');
            quantityInput.attr('max', fromStock);

            // Show stock status alerts
            showStockStatusAlerts(fromStock, toStock, quantity, selectedItem);

            // Show stock info card
            $('#stock_info_card').show();

            // Validate transfer
            validateTransfer();
        }

        // Show stock status alerts
        function showStockStatusAlerts(fromStock, toStock, quantity, selectedItem) {
            const alertDiv = $('#stock_status_alert');
            const messageSpan = $('#stock_status_message');

            // Hide alert by default
            alertDiv.hide();

            // Check for low stock warnings
            if (fromStock < quantity) {
                alertDiv.removeClass('alert-info alert-warning alert-danger').addClass('alert-danger');
                messageSpan.html(
                    `<strong>Insufficient Stock!</strong> Only ${formatNumber(fromStock)} ${selectedItem?.unit_of_measure || 'units'} available in source warehouse.`
                );
                alertDiv.show();
            } else if (fromStock === quantity) {
                alertDiv.removeClass('alert-info alert-warning alert-danger').addClass('alert-warning');
                messageSpan.html(
                    `<strong>Stock Depletion Warning!</strong> This transfer will completely deplete the source warehouse stock.`
                );
                alertDiv.show();
            } else if (fromStock - quantity < 10) { // Assuming 10 is low stock threshold
                alertDiv.removeClass('alert-info alert-warning alert-danger').addClass('alert-warning');
                messageSpan.html(
                    `<strong>Low Stock Alert!</strong> Source warehouse will have only ${formatNumber(fromStock - quantity)} ${selectedItem?.unit_of_measure || 'units'} remaining after transfer.`
                );
                alertDiv.show();
            } else if (toStock === 0) {
                alertDiv.removeClass('alert-info alert-warning alert-danger').addClass('alert-info');
                messageSpan.html(
                    `<strong>New Stock!</strong> This will be the first stock of this item in the destination warehouse.`
                );
                alertDiv.show();
            }
        }

        // Format numbers with commas
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Reset stock information
        function resetStockInfo() {
            $('#stock_info_card').hide();
            currentStock = {};
        }

        // Validate transfer
        function validateTransfer() {
            const transferType = $('#transfer_type').val();
            let isValid = true;
            let errorMessage = '';

            if (transferType === 'direct') {
                const itemId = $('#direct_item_id').val();
                const fromWarehouseId = $('#direct_from_warehouse').val();
                const toWarehouseId = $('#direct_to_warehouse').val();
                const quantity = parseInt($('#direct_quantity').val()) || 0;

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
            } else if (transferType === 'ito') {
                const itemId = $('#ito_item_id').val();
                const fromWarehouseId = $('#ito_from_warehouse').val();
                const toWarehouseId = $('#ito_to_warehouse').val();
                const quantity = parseInt($('#ito_quantity').val()) || 0;

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
            } else if (transferType === 'iti') {
                const transferOutId = $('#iti_transfer_out_id').val();
                const receivedQuantity = parseInt($('#iti_received_quantity').val()) || 0;
                const originalQuantity = parseInt($('#iti_original_quantity').val()) || 0;

                if (!transferOutId) {
                    isValid = false;
                    errorMessage = 'Please select a pending transfer';
                } else if (receivedQuantity > originalQuantity) {
                    isValid = false;
                    errorMessage = 'Received quantity cannot exceed original quantity';
                }
            }

            $('#transfer_submit_btn').prop('disabled', !isValid);

            if (!isValid && errorMessage) {
                $('#transfer_submit_btn').attr('title', errorMessage);
            } else {
                $('#transfer_submit_btn').removeAttr('title');
            }
        }

        // Handle form submission
        $('#transfer_submit_btn').on('click', function() {
            const transferType = $('#transfer_type').val();

            if (transferType === 'direct') {
                submitDirectTransfer();
            } else if (transferType === 'ito') {
                submitITO();
            } else if (transferType === 'iti') {
                submitITI();
            }
        });

        // Submit direct transfer
        function submitDirectTransfer() {
            const formData = {
                item_id: $('#direct_item_id').val(),
                from_warehouse_id: $('#direct_from_warehouse').val(),
                to_warehouse_id: $('#direct_to_warehouse').val(),
                quantity: $('#direct_quantity').val(),
                notes: $('#direct_notes').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            $('#transfer_submit_btn').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

            $.ajax({
                url: '{{ route('warehouses.transfer-stock') }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        $('#warehouseTransferModalEnhanced').modal('hide');
                        resetForm();
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
                        '<i class="fas fa-exchange-alt mr-1"></i>Process Transfer');
                }
            });
        }

        // Submit ITO
        function submitITO() {
            const formData = {
                item_id: $('#ito_item_id').val(),
                from_warehouse_id: $('#ito_from_warehouse').val(),
                to_warehouse_id: $('#ito_to_warehouse').val(),
                quantity: $('#ito_quantity').val(),
                notes: $('#ito_notes').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            $('#transfer_submit_btn').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i>Creating ITO...');

            $.ajax({
                url: '{{ route('warehouses.transfer-out') }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        $('#warehouseTransferModalEnhanced').modal('hide');
                        resetForm();
                        if (typeof refreshData === 'function') {
                            refreshData();
                        }
                    } else {
                        showAlert(response.message || 'ITO creation failed', 'error');
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'ITO creation failed';
                    showAlert(errorMessage, 'error');
                },
                complete: function() {
                    $('#transfer_submit_btn').prop('disabled', false).html(
                        '<i class="fas fa-exchange-alt mr-1"></i>Process Transfer');
                }
            });
        }

        // Submit ITI
        function submitITI() {
            const formData = {
                transfer_out_id: $('#iti_transfer_out_id').val(),
                received_quantity: $('#iti_received_quantity').val(),
                notes: $('#iti_notes').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            $('#transfer_submit_btn').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i>Processing ITI...');

            $.ajax({
                url: '{{ route('warehouses.transfer-in') }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        $('#warehouseTransferModalEnhanced').modal('hide');
                        resetForm();
                        if (typeof refreshData === 'function') {
                            refreshData();
                        }
                    } else {
                        showAlert(response.message || 'ITI processing failed', 'error');
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'ITI processing failed';
                    showAlert(errorMessage, 'error');
                },
                complete: function() {
                    $('#transfer_submit_btn').prop('disabled', false).html(
                        '<i class="fas fa-exchange-alt mr-1"></i>Process Transfer');
                }
            });
        }

        // Reset form
        function resetForm() {
            $('#transfer_type').val('direct');
            $('#direct_transfer_form').show();
            $('#ito_form, #iti_form').hide();
            $('#directTransferForm, #itoForm, #itiForm')[0].reset();
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
