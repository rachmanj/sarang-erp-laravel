@extends('layouts.main')

@section('title', 'Pending Transfers')

@section('title_page')
    Pending Transfers
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">Pending Transfers</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock mr-1"></i>
                                Pending Transfers (Items in Transit)
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('warehouses.index') }}" class="btn btn-tool btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to Warehouses
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filter Form -->
                            <form class="form-inline mb-3" id="filterForm">
                                <div class="form-group mr-2">
                                    <label for="filter_warehouse_id" class="sr-only">Warehouse:</label>
                                    <select class="form-control form-control-sm" id="filter_warehouse_id"
                                        name="warehouse_id">
                                        <option value="">All Warehouses</option>
                                        {{-- Options populated by JS --}}
                                    </select>
                                </div>
                                <div class="form-group mr-2">
                                    <label for="filter_item_id" class="sr-only">Item:</label>
                                    <select class="form-control form-control-sm" id="filter_item_id" name="item_id">
                                        <option value="">All Items</option>
                                        {{-- Options populated by JS --}}
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mr-1">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="{{ route('warehouses.pending-transfers') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </form>

                            <!-- Pending Transfers Table -->
                            <div id="pending-transfers-container">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                    <p class="text-muted mt-2">Loading pending transfers...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ITI Processing Modal -->
    <div class="modal fade" id="itiProcessingModal" tabindex="-1" role="dialog" aria-labelledby="itiProcessingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itiProcessingModalLabel">
                        <i class="fas fa-check-circle mr-1"></i>
                        Process Transfer In (ITI)
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="itiProcessingForm">
                        <input type="hidden" id="iti_transfer_out_id" name="transfer_out_id">

                        <div class="form-group">
                            <label for="iti_received_quantity">Received Quantity:</label>
                            <input type="number" class="form-control" id="iti_received_quantity" name="received_quantity"
                                min="1">
                            <small class="form-text text-muted">Leave empty to receive full quantity</small>
                        </div>

                        <div class="form-group">
                            <label for="iti_notes">Notes:</label>
                            <textarea class="form-control" id="iti_notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="process_iti_btn">
                        <i class="fas fa-check mr-1"></i>
                        Process ITI
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let warehouses = [];
            let items = [];
            let pendingTransfers = [];

            // Load initial data
            loadWarehouses();
            loadItems();
            loadPendingTransfers();

            // Load warehouses
            function loadWarehouses() {
                $.get('{{ route('warehouses.get-warehouses') }}')
                    .done(function(data) {
                        warehouses = data.filter(w => !w.is_transit); // Only physical warehouses
                        populateWarehouseFilter();
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
                        populateItemFilter();
                    })
                    .fail(function() {
                        showAlert('Error loading items', 'error');
                    });
            }

            // Load pending transfers
            function loadPendingTransfers() {
                $.get('{{ route('warehouses.pending-transfers') }}')
                    .done(function(data) {
                        pendingTransfers = data;
                        displayPendingTransfers();
                    })
                    .fail(function() {
                        showAlert('Error loading pending transfers', 'error');
                        $('#pending-transfers-container').html(
                            '<div class="alert alert-danger">Error loading pending transfers</div>');
                    });
            }

            // Populate warehouse filter
            function populateWarehouseFilter() {
                const select = $('#filter_warehouse_id');
                select.empty().append('<option value="">All Warehouses</option>');

                warehouses.forEach(function(warehouse) {
                    select.append(
                        `<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name}</option>`
                        );
                });
            }

            // Populate item filter
            function populateItemFilter() {
                const select = $('#filter_item_id');
                select.empty().append('<option value="">All Items</option>');

                items.forEach(function(item) {
                    select.append(`<option value="${item.id}">${item.code} - ${item.name}</option>`);
                });
            }

            // Display pending transfers
            function displayPendingTransfers() {
                if (pendingTransfers.length === 0) {
                    $('#pending-transfers-container').html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No pending transfers found.
                </div>
            `);
                    return;
                }

                let html = `
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Transfer ID</th>
                            <th>Item</th>
                            <th>From Warehouse</th>
                            <th>To Warehouse</th>
                            <th>Quantity</th>
                            <th>Transit Date</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

                pendingTransfers.forEach(function(transfer) {
                    const itemName = transfer.item ? `${transfer.item.code} - ${transfer.item.name}` :
                        'Unknown Item';
                    const warehouseName = transfer.warehouse ? transfer.warehouse.name :
                    'Unknown Warehouse';
                    const transitDate = transfer.transit_date ? new Date(transfer.transit_date)
                        .toLocaleDateString() : '-';
                    const quantity = Math.abs(transfer.quantity);

                    html += `
                <tr>
                    <td><span class="badge badge-info">#${transfer.id}</span></td>
                    <td>${itemName}</td>
                    <td>${warehouseName}</td>
                    <td>${getWarehouseName(transfer.reference_id)}</td>
                    <td><span class="badge badge-primary">${quantity}</span></td>
                    <td>${transitDate}</td>
                    <td>${transfer.transfer_notes || '-'}</td>
                    <td>
                        <button class="btn btn-success btn-sm" onclick="processITI(${transfer.id}, ${quantity})">
                            <i class="fas fa-check mr-1"></i>
                            Process ITI
                        </button>
                    </td>
                </tr>
            `;
                });

                html += `
                    </tbody>
                </table>
            </div>
        `;

                $('#pending-transfers-container').html(html);
            }

            // Get warehouse name by ID
            function getWarehouseName(warehouseId) {
                const warehouse = warehouses.find(w => w.id == warehouseId);
                return warehouse ? `${warehouse.code} - ${warehouse.name}` : 'Unknown Warehouse';
            }

            // Process ITI
            window.processITI = function(transferOutId, originalQuantity) {
                $('#iti_transfer_out_id').val(transferOutId);
                $('#iti_received_quantity').attr('max', originalQuantity);
                $('#iti_received_quantity').val(originalQuantity);
                $('#iti_notes').val('');
                $('#itiProcessingModal').modal('show');
            };

            // Handle ITI form submission
            $('#process_iti_btn').on('click', function() {
                const formData = {
                    transfer_out_id: $('#iti_transfer_out_id').val(),
                    received_quantity: $('#iti_received_quantity').val(),
                    notes: $('#iti_notes').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $(this).prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

                $.ajax({
                    url: '{{ route('warehouses.transfer-in') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            $('#itiProcessingModal').modal('hide');
                            loadPendingTransfers(); // Reload the list
                        } else {
                            showAlert(response.message || 'ITI processing failed', 'error');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message ||
                            'ITI processing failed';
                        showAlert(errorMessage, 'error');
                    },
                    complete: function() {
                        $('#process_iti_btn').prop('disabled', false).html(
                            '<i class="fas fa-check mr-1"></i>Process ITI');
                    }
                });
            });

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                const warehouseId = $('#filter_warehouse_id').val();
                const itemId = $('#filter_item_id').val();

                // Filter pending transfers
                let filteredTransfers = pendingTransfers;

                if (warehouseId) {
                    filteredTransfers = filteredTransfers.filter(t => t.reference_id == warehouseId);
                }

                if (itemId) {
                    filteredTransfers = filteredTransfers.filter(t => t.item_id == itemId);
                }

                // Temporarily replace pendingTransfers for display
                const originalTransfers = pendingTransfers;
                pendingTransfers = filteredTransfers;
                displayPendingTransfers();
                pendingTransfers = originalTransfers;
            });

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
