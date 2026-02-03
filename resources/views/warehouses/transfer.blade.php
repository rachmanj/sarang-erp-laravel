@extends('layouts.main')

@section('title_page')
    Warehouse Transfer
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item active">Transfer Stock</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exchange-alt mr-1"></i>
                        Warehouse Stock Transfer
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Warehouses
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="transferForm">
                        @csrf

                        <!-- Transfer Type Selection -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="transfer_type">Transfer Type:</label>
                                <select class="form-control" id="transfer_type" name="transfer_type" required>
                                    <option value="direct">Direct Transfer (Immediate)</option>
                                    <option value="ito">Inventory Transfer Out (ITO)</option>
                                    <option value="iti">Inventory Transfer In (ITI)</option>
                                </select>
                                <small class="form-text text-muted">
                                    <strong>Direct:</strong> Immediate transfer between warehouses.
                                    <strong>ITO:</strong> Two-step transfer through transit warehouse.
                                    <strong>ITI:</strong> Complete a pending ITO transfer.
                                </small>
                            </div>
                        </div>

                        <!-- Direct Transfer & ITO Form -->
                        <div id="direct_ito_form">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="item_search">Item <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="item_search"
                                                placeholder="Start typing to search for items by code or name..."
                                                autocomplete="off">
                                            <div class="input-group-append" id="item_clear_btn" style="display: none;">
                                                <button class="btn btn-outline-secondary" type="button" id="clear_item_btn"
                                                    tabindex="-1">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" id="item_id" name="item_id" required>
                                        <div id="item_search_results" class="list-group position-absolute w-100"
                                            style="display: none; z-index: 1000; max-height: 300px; overflow-y: auto; margin-top: 2px; border-radius: 0.25rem;">
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Start typing to search for items by code or
                                        name</small>
                                </div>
                            </div>

                            <!-- Item Information Card -->
                            <div class="card mb-3" id="item_info_card" style="display: none;">
                                <div class="card-header bg-info">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-box mr-1"></i>
                                        Item Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Item Code:</strong>
                                            <p id="item_code" class="mb-0">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Item Name:</strong>
                                            <p id="item_name" class="mb-0">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Category:</strong>
                                            <p id="item_category" class="mb-0">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Unit of Measure:</strong>
                                            <p id="item_unit" class="mb-0">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="from_warehouse_id">From Warehouse <span class="text-danger">*</span></label>
                                    <select class="form-control" id="from_warehouse_id" name="from_warehouse_id" required>
                                        <option value="">Select Source Warehouse</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="to_warehouse_id">To Warehouse <span class="text-danger">*</span></label>
                                    <select class="form-control" id="to_warehouse_id" name="to_warehouse_id" required>
                                        <option value="">Select Destination Warehouse</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Stock Information Card -->
                            <div class="card mb-3" id="stock_info_card" style="display: none;">
                                <div class="card-header bg-primary">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-warehouse mr-1"></i>
                                        Stock Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-box bg-info">
                                                <span class="info-box-icon"><i class="fas fa-boxes"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Source Warehouse</span>
                                                    <span class="info-box-number" id="source_warehouse_name">-</span>
                                                    <div class="progress">
                                                        <div class="progress-bar" id="source_stock_bar"
                                                            style="width: 0%">
                                                        </div>
                                                    </div>
                                                    <span class="progress-description">
                                                        Available: <strong id="source_stock">0</strong> <span
                                                            id="source_unit">units</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-success">
                                                <span class="info-box-icon"><i class="fas fa-warehouse"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Destination Warehouse</span>
                                                    <span class="info-box-number" id="destination_warehouse_name">-</span>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" id="destination_stock_bar"
                                                            style="width: 0%"></div>
                                                    </div>
                                                    <span class="progress-description">
                                                        Current Stock: <strong id="destination_stock">0</strong> <span
                                                            id="destination_unit">units</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-box bg-warning">
                                                <span class="info-box-icon"><i class="fas fa-exchange-alt"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">After Transfer</span>
                                                    <span class="info-box-number"
                                                        id="destination_warehouse_name_after">-</span>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-warning" id="after_transfer_bar"
                                                            style="width: 0%"></div>
                                                    </div>
                                                    <span class="progress-description">
                                                        Final Stock: <strong id="after_transfer">0</strong> <span
                                                            id="after_unit">units</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Status Alert -->
                                    <div class="alert mt-3" id="stock_status_alert" style="display: none;">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <span id="stock_status_message"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="quantity">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity"
                                        min="1" required>
                                    <small class="form-text text-muted" id="quantity_help">Enter the quantity to
                                        transfer</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"
                                        placeholder="Optional notes about this transfer"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ITI Form -->
                        <div id="iti_form" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="transfer_out_id">Select Pending Transfer (ITO) <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" id="transfer_out_id" name="transfer_out_id" required>
                                        <option value="">Select Pending Transfer</option>
                                    </select>
                                    <small class="form-text text-muted">Select an Inventory Transfer Out (ITO) that is
                                        pending completion</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="received_quantity">Received Quantity</label>
                                    <input type="number" class="form-control" id="received_quantity"
                                        name="received_quantity" min="1">
                                    <small class="form-text text-muted">Leave empty to receive full quantity</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="original_quantity">Original Quantity</label>
                                    <input type="text" class="form-control" id="original_quantity" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="iti_notes">Notes</label>
                                    <textarea class="form-control" id="iti_notes" name="notes" rows="2"
                                        placeholder="Optional notes about this transfer"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-lg" id="submit_btn">
                                    <i class="fas fa-exchange-alt mr-1"></i>
                                    Process Transfer
                                </button>
                                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-times mr-1"></i>
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        #item_search_results {
            background: white;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        #item_search_results .list-group-item {
            border-left: none;
            border-right: none;
            cursor: pointer;
        }

        #item_search_results .list-group-item:first-child {
            border-top: none;
        }

        #item_search_results .list-group-item:last-child {
            border-bottom: none;
        }

        #item_search_results .list-group-item:hover,
        #item_search_results .list-group-item.active {
            background-color: #007bff;
            color: white;
            z-index: 1;
        }

        #item_search_results .list-group-item.active {
            background-color: #0056b3;
        }

        #item_search_results mark {
            background-color: #ffc107;
            color: #000;
            padding: 0;
            font-weight: bold;
        }

        .position-relative {
            position: relative;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let warehouses = [];
            let selectedItem = null;
            let stockData = {};

            // Custom Autocomplete for Item Search
            let searchTimeout = null;
            let currentSearchResults = [];

            // Handle item search input
            $('#item_search').on('input', function() {
                const searchTerm = $(this).val().trim();

                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                // Hide results if input is cleared
                if (searchTerm.length === 0) {
                    $('#item_search_results').hide();
                    $('#item_id').val('');
                    selectedItem = null;
                    $('#item_info_card').hide();
                    $('#stock_info_card').hide();
                    $('#item_clear_btn').hide();
                    return;
                }

                // Minimum 2 characters
                if (searchTerm.length < 2) {
                    $('#item_search_results').html(
                        '<div class="list-group-item text-muted">Please enter at least 2 characters</div>'
                    ).show();
                    return;
                }

                // Show loading
                $('#item_search_results').html(
                    '<div class="list-group-item text-muted"><i class="fas fa-spinner fa-spin"></i> Searching...</div>'
                ).show();

                // Debounce search
                searchTimeout = setTimeout(function() {
                    performItemSearch(searchTerm);
                }, 300);
            });

            // Perform AJAX search
            function performItemSearch(searchTerm) {
                $.ajax({
                    url: '{{ route('inventory.search') }}',
                    method: 'GET',
                    data: {
                        q: searchTerm, // Single search parameter - searches both code and name
                        per_page: 50 // Increased to show more results
                    },
                    dataType: 'json',
                    success: function(response) {
                        currentSearchResults = [];
                        const resultsDiv = $('#item_search_results');

                        if (!response || !response.items || response.items.length === 0) {
                            resultsDiv.html(
                                    '<div class="list-group-item text-muted">No results found</div>')
                                .show();
                            return;
                        }

                        // Store results
                        currentSearchResults = response.items;

                        // Build results HTML with highlighting
                        let html = '';
                        const searchLower = searchTerm.toLowerCase();
                        response.items.forEach(function(item) {
                            // Highlight matching parts
                            let codeDisplay = item.code || '';
                            let nameDisplay = item.name || '';

                            // Highlight code if it matches
                            if (codeDisplay.toLowerCase().includes(searchLower)) {
                                const index = codeDisplay.toLowerCase().indexOf(searchLower);
                                codeDisplay = codeDisplay.substring(0, index) +
                                    '<mark>' + codeDisplay.substring(index, index + searchTerm
                                        .length) + '</mark>' +
                                    codeDisplay.substring(index + searchTerm.length);
                            }

                            // Highlight name if it matches
                            if (nameDisplay.toLowerCase().includes(searchLower)) {
                                const index = nameDisplay.toLowerCase().indexOf(searchLower);
                                nameDisplay = nameDisplay.substring(0, index) +
                                    '<mark>' + nameDisplay.substring(index, index + searchTerm
                                        .length) + '</mark>' +
                                    nameDisplay.substring(index + searchTerm.length);
                            }

                            html +=
                                '<a href="#" class="list-group-item list-group-item-action item-result" ' +
                                'data-item-id="' + item.id + '" ' +
                                'data-item-code="' + (item.code || '') + '" ' +
                                'data-item-name="' + (item.name || '') + '">' +
                                '<strong>' + codeDisplay + '</strong> - ' + nameDisplay +
                                '</a>';
                        });

                        resultsDiv.html(html).show();

                        // Attach click handlers
                        $('.item-result').on('click', function(e) {
                            e.preventDefault();
                            const itemId = $(this).data('item-id');
                            selectItem(itemId);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Item search error:', error);
                        $('#item_search_results').html(
                            '<div class="list-group-item text-danger">Error searching items. Please try again.</div>'
                        ).show();
                    }
                });
            }

            // Select an item
            function selectItem(itemId) {
                // Find the item in current results
                const item = currentSearchResults.find(function(i) {
                    return i.id == itemId;
                });

                if (item) {
                    // Set hidden input
                    $('#item_id').val(item.id);

                    // Set display text
                    $('#item_search').val(item.code + ' - ' + item.name);

                    // Hide results and show clear button
                    $('#item_search_results').hide();
                    $('#item_clear_btn').show();

                    // Handle selection
                    handleItemSelection({
                        id: item.id,
                        text: item.code + ' - ' + item.name,
                        item: item
                    });
                } else {
                    // If not in results, fetch details
                    fetchItemDetails(itemId);
                }
            }

            // Clear item selection
            $('#clear_item_btn').on('click', function() {
                $('#item_search').val('');
                $('#item_id').val('');
                $('#item_search_results').hide();
                $('#item_clear_btn').hide();
                selectedItem = null;
                $('#item_info_card').hide();
                $('#stock_info_card').hide();
                $('#item_search').focus();
            });

            // Hide results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#item_search, #item_search_results').length) {
                    $('#item_search_results').hide();
                }
            });

            // Handle keyboard navigation
            $('#item_search').on('keydown', function(e) {
                const results = $('.item-result');
                const active = results.filter('.active');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (active.length) {
                        active.removeClass('active').next().addClass('active');
                    } else {
                        results.first().addClass('active');
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (active.length) {
                        active.removeClass('active').prev().addClass('active');
                    } else {
                        results.last().addClass('active');
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const activeItem = results.filter('.active');
                    if (activeItem.length) {
                        const itemId = activeItem.data('item-id');
                        selectItem(itemId);
                    }
                } else if (e.key === 'Escape') {
                    $('#item_search_results').hide();
                }
            });

            // Unified function to handle item selection
            function handleItemSelection(data) {
                if (!data) {
                    return;
                }

                // Check if item data is available in the event
                if (data.item) {
                    selectedItem = data.item;
                    displayItemInfo(selectedItem);
                    loadItemStock(selectedItem.id);
                } else if (data.id) {
                    // If item data is missing, fetch it
                    fetchItemDetails(data.id);
                }
            }

            // Fetch item details if not available in current results
            function fetchItemDetails(itemId) {
                // Try to get from current search results first
                const cachedItem = currentSearchResults.find(function(i) {
                    return i.id == itemId;
                });
                if (cachedItem) {
                    handleItemSelection({
                        id: cachedItem.id,
                        text: cachedItem.code + ' - ' + cachedItem.name,
                        item: cachedItem
                    });
                    return;
                }

                // Check if itemId is numeric (ID) or string (code)
                const isNumericId = /^\d+$/.test(itemId.toString());

                if (isNumericId) {
                    // Use direct item details endpoint for numeric IDs
                    $.ajax({
                        url: `{{ route('inventory.get-item-details', ':id') }}`.replace(':id', itemId),
                        method: 'GET',
                        dataType: 'json'
                    }).done(function(item) {
                        // Transform the response to match expected format
                        // getItemDetails returns category as string, but we need object format
                        const transformedItem = {
                            id: item.id,
                            code: item.code,
                            name: item.name,
                            description: item.description,
                            category: {
                                name: item.category || 'N/A'
                            },
                            unit_of_measure: item.unit_of_measure,
                            purchase_price: item.purchase_price,
                            selling_price: item.selling_price,
                            current_stock: item.current_stock,
                            min_stock_level: item.min_stock_level,
                            max_stock_level: item.max_stock_level,
                            reorder_point: item.reorder_point,
                            valuation_method: item.valuation_method
                        };
                        selectedItem = transformedItem;
                        // Update search input and show clear button
                        $('#item_search').val(transformedItem.code + ' - ' + transformedItem.name);
                        $('#item_clear_btn').show();
                        displayItemInfo(transformedItem);
                        loadItemStock(transformedItem.id);
                    }).fail(function(xhr, status, error) {
                        console.error('Error loading item details:', error);
                        // Fallback to search endpoint
                        fetchItemBySearch(itemId);
                    });
                } else {
                    // Use search endpoint for codes/names
                    fetchItemBySearch(itemId);
                }
            }

            // Helper function to fetch item by search
            function fetchItemBySearch(searchTerm) {
                $.ajax({
                    url: `{{ route('inventory.search') }}`,
                    method: 'GET',
                    data: {
                        q: searchTerm,
                        per_page: 50
                    },
                    dataType: 'json'
                }).done(function(response) {
                    if (response.items && response.items.length > 0) {
                        // Try to find by ID first (if searchTerm is numeric)
                        let item = null;
                        if (/^\d+$/.test(searchTerm.toString())) {
                            item = response.items.find(i => i.id == searchTerm);
                        }

                        // If not found by ID, try to find by code or name match
                        if (!item) {
                            item = response.items.find(i =>
                                i.code.toLowerCase() === searchTerm.toString().toLowerCase() ||
                                i.name.toLowerCase().includes(searchTerm.toString().toLowerCase())
                            );
                        }

                        // If still not found, use first result
                        if (!item && response.items.length > 0) {
                            item = response.items[0];
                        }

                        if (item) {
                            selectedItem = item;
                            // Update search input and show clear button
                            $('#item_search').val(item.code + ' - ' + item.name);
                            $('#item_id').val(item.id);
                            $('#item_clear_btn').show();
                            displayItemInfo(item);
                            loadItemStock(item.id);
                        } else {
                            toastr.warning('Item details not found');
                        }
                    } else {
                        toastr.warning('Item details not found');
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Error loading item details:', error);
                    toastr.error('Error loading item details');
                });
            }

            // Load warehouses
            function loadWarehouses() {
                $.get('{{ route('warehouses.get-warehouses') }}')
                    .done(function(data) {
                        warehouses = data;
                        populateWarehouseSelects();
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Error loading warehouses:', error);
                        toastr.error('Error loading warehouses: ' + error);
                    });
            }

            // Populate warehouse selects
            function populateWarehouseSelects() {
                const selects = ['#from_warehouse_id', '#to_warehouse_id'];

                selects.forEach(function(selector) {
                    const select = $(selector);
                    select.empty().append('<option value="">Select Warehouse</option>');

                    warehouses.forEach(function(warehouse) {
                        if (!warehouse.is_transit) {
                            select.append(
                                `<option value="${warehouse.id}">${warehouse.code} - ${warehouse.name}</option>`
                            );
                        }
                    });
                });
            }

            // Display item information
            function displayItemInfo(item) {
                if (!item) {
                    console.error('displayItemInfo: item is null or undefined');
                    return;
                }

                $('#item_code').text(item.code || '-');
                $('#item_name').text(item.name || '-');

                // Handle category - can be object with name property or string
                let categoryName = '-';
                if (item.category) {
                    if (typeof item.category === 'string') {
                        categoryName = item.category;
                    } else if (item.category.name) {
                        categoryName = item.category.name;
                    }
                }
                $('#item_category').text(categoryName);

                $('#item_unit').text(item.unit_of_measure || 'PCS');
                $('#item_info_card').show();
            }

            // Load item stock for warehouses
            function loadItemStock(itemId) {
                $.get(`{{ route('warehouses.get-item-stock', ':itemId') }}`.replace(':itemId', itemId))
                    .done(function(data) {
                        stockData = {};
                        data.forEach(function(stock) {
                            stockData[stock.warehouse_id] = stock.quantity_on_hand;
                        });
                        updateStockInfo();
                    })
                    .fail(function() {
                        toastr.error('Error loading stock information');
                        stockData = {};
                    });
            }

            // Update stock information display
            function updateStockInfo() {
                const fromWarehouseId = $('#from_warehouse_id').val();
                const toWarehouseId = $('#to_warehouse_id').val();
                const quantity = parseInt($('#quantity').val()) || 0;

                if (!fromWarehouseId || !toWarehouseId || !selectedItem) {
                    $('#stock_info_card').hide();
                    return;
                }

                const fromWarehouse = warehouses.find(w => w.id == fromWarehouseId);
                const toWarehouse = warehouses.find(w => w.id == toWarehouseId);

                if (!fromWarehouse || !toWarehouse) {
                    $('#stock_info_card').hide();
                    return;
                }

                const fromStock = stockData[fromWarehouseId] || 0;
                const toStock = stockData[toWarehouseId] || 0;
                const afterTransfer = toStock + quantity;
                const unit = selectedItem.unit_of_measure || 'PCS';

                // Update warehouse names
                $('#source_warehouse_name').text(`${fromWarehouse.code} - ${fromWarehouse.name}`);
                $('#destination_warehouse_name').text(`${toWarehouse.code} - ${toWarehouse.name}`);
                $('#destination_warehouse_name_after').text(`${toWarehouse.code} - ${toWarehouse.name}`);

                // Update stock numbers
                $('#source_stock').text(formatNumber(fromStock));
                $('#source_unit').text(unit);
                $('#destination_stock').text(formatNumber(toStock));
                $('#destination_unit').text(unit);
                $('#after_transfer').text(formatNumber(afterTransfer));
                $('#after_unit').text(unit);

                // Update progress bars (assuming max 1000 for visualization)
                const maxStock = Math.max(fromStock, toStock, afterTransfer, 1000);
                $('#source_stock_bar').css('width', (fromStock / maxStock * 100) + '%');
                $('#destination_stock_bar').css('width', (toStock / maxStock * 100) + '%');
                $('#after_transfer_bar').css('width', (afterTransfer / maxStock * 100) + '%');

                // Update quantity max
                $('#quantity').attr('max', fromStock);
                $('#quantity_help').text(`Maximum available: ${formatNumber(fromStock)} ${unit}`);

                // Show stock status alerts
                showStockStatusAlerts(fromStock, toStock, quantity, selectedItem);

                // Show stock info card
                $('#stock_info_card').show();
            }

            // Show stock status alerts
            function showStockStatusAlerts(fromStock, toStock, quantity, item) {
                const alertDiv = $('#stock_status_alert');
                const messageSpan = $('#stock_status_message');

                alertDiv.hide().removeClass('alert-info alert-warning alert-danger');

                if (fromStock < quantity) {
                    alertDiv.addClass('alert-danger');
                    messageSpan.html(
                        `<strong>Insufficient Stock!</strong> Only ${formatNumber(fromStock)} ${item.unit_of_measure || 'units'} available in source warehouse.`
                    );
                    alertDiv.show();
                } else if (fromStock === quantity) {
                    alertDiv.addClass('alert-warning');
                    messageSpan.html(
                        `<strong>Stock Depletion Warning!</strong> This transfer will completely deplete the source warehouse stock.`
                    );
                    alertDiv.show();
                } else if (fromStock - quantity < 10) {
                    alertDiv.addClass('alert-warning');
                    messageSpan.html(
                        `<strong>Low Stock Alert!</strong> Source warehouse will have only ${formatNumber(fromStock - quantity)} ${item.unit_of_measure || 'units'} remaining after transfer.`
                    );
                    alertDiv.show();
                } else if (toStock === 0) {
                    alertDiv.addClass('alert-info');
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

            // Handle warehouse selection changes
            $('#from_warehouse_id, #to_warehouse_id').on('change', function() {
                if (selectedItem) {
                    updateStockInfo();
                }
            });

            // Handle quantity changes
            $('#quantity').on('input', function() {
                updateStockInfo();
            });

            // Handle transfer type change
            $('#transfer_type').on('change', function() {
                const transferType = $(this).val();
                if (transferType === 'iti') {
                    $('#direct_ito_form').hide();
                    $('#iti_form').show();
                    loadPendingTransfers();
                } else {
                    $('#direct_ito_form').show();
                    $('#iti_form').hide();
                }
            });

            // Load pending transfers for ITI
            function loadPendingTransfers() {
                $.get('{{ route('warehouses.pending-transfers') }}')
                    .done(function(data) {
                        const select = $('#transfer_out_id');
                        select.empty().append('<option value="">Select Pending Transfer</option>');

                        data.forEach(function(transfer) {
                            const itemName = transfer.item ?
                                `${transfer.item.code} - ${transfer.item.name}` : 'Unknown Item';
                            const warehouseName = transfer.warehouse ? transfer.warehouse.name :
                                'Unknown Warehouse';
                            select.append(
                                `<option value="${transfer.id}" data-quantity="${Math.abs(transfer.quantity)}">${itemName} from ${warehouseName} (Qty: ${Math.abs(transfer.quantity)})</option>`
                            );
                        });
                    })
                    .fail(function() {
                        toastr.error('Error loading pending transfers');
                    });
            }

            // Handle ITI transfer selection
            $('#transfer_out_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const quantity = selectedOption.data('quantity');
                if (quantity) {
                    $('#original_quantity').val(quantity);
                    $('#received_quantity').attr('max', quantity);
                } else {
                    $('#original_quantity').val('');
                    $('#received_quantity').attr('max', '');
                }
            });

            // Handle form submission
            $('#transferForm').on('submit', function(e) {
                // Validate item selection
                if (!$('#item_id').val() || $('#item_id').val() === '') {
                    e.preventDefault();
                    toastr.error('Please select an item');
                    $('#item_search').focus();
                    return false;
                }
                e.preventDefault();
                const transferType = $('#transfer_type').val();
                let formData = {};
                let url = '';

                if (transferType === 'direct') {
                    formData = {
                        item_id: $('#item_id').val(),
                        from_warehouse_id: $('#from_warehouse_id').val(),
                        to_warehouse_id: $('#to_warehouse_id').val(),
                        quantity: $('#quantity').val(),
                        notes: $('#notes').val(),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    };
                    url = '{{ route('warehouses.transfer-stock') }}';
                } else if (transferType === 'ito') {
                    formData = {
                        item_id: $('#item_id').val(),
                        from_warehouse_id: $('#from_warehouse_id').val(),
                        to_warehouse_id: $('#to_warehouse_id').val(),
                        quantity: $('#quantity').val(),
                        notes: $('#notes').val(),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    };
                    url = '{{ route('warehouses.transfer-out') }}';
                } else if (transferType === 'iti') {
                    formData = {
                        transfer_out_id: $('#transfer_out_id').val(),
                        received_quantity: $('#received_quantity').val(),
                        notes: $('#iti_notes').val(),
                        _token: $('meta[name="csrf-token"]').attr('content')
                    };
                    url = '{{ route('warehouses.transfer-in') }}';
                }

                $('#submit_btn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(function() {
                                window.location.href =
                                    '{{ route('warehouses.index') }}';
                            }, 1500);
                        } else {
                            toastr.error(response.message || 'Transfer failed');
                            $('#submit_btn').prop('disabled', false).html(
                                '<i class="fas fa-exchange-alt mr-1"></i>Process Transfer');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'Transfer failed';
                        toastr.error(errorMessage);
                        $('#submit_btn').prop('disabled', false).html(
                            '<i class="fas fa-exchange-alt mr-1"></i>Process Transfer');
                    }
                });
            });

            // Initialize
            loadWarehouses();
        });
    </script>
@endpush
