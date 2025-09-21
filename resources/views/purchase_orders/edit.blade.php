@extends('layouts.main')

@section('title', 'Edit Purchase Order')

@section('title_page')
    Edit Purchase Order - {{ $order->order_no }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
    <li class="breadcrumb-item active">Edit - {{ $order->order_no }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shopping-cart mr-1"></i>
                                Edit Purchase Order
                            </h3>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Purchase Orders
                            </a>
                        </div>

                        <form action="{{ route('purchase-orders.update', $order->id) }}" method="POST" id="po-form">
                            @csrf
                            @method('PUT')

                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Date <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" name="date"
                                                        value="{{ old('date', $order->date ? $order->date->format('Y-m-d') : '') }}"
                                                        class="form-control @error('date') is-invalid @enderror" required>
                                                    @error('date')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">PO Number</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" name="order_no"
                                                        value="{{ old('order_no', $order->order_no) }}"
                                                        class="form-control bg-light" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Order Type <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="order_type"
                                                    class="form-control form-control-sm select2bs4 @error('order_type') is-invalid @enderror"
                                                    id="order_type" required>
                                                    <option value="">-- select type --</option>
                                                    <option value="item"
                                                        {{ old('order_type', $order->order_type) == 'item' ? 'selected' : '' }}>
                                                        Item (Physical Inventory)
                                                    </option>
                                                    <option value="service"
                                                        {{ old('order_type', $order->order_type) == 'service' ? 'selected' : '' }}>
                                                        Service (Non-Inventory)
                                                    </option>
                                                </select>
                                                @error('order_type')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Vendor <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id"
                                                    class="form-control form-control-sm select2bs4 @error('business_partner_id') is-invalid @enderror"
                                                    required>
                                                    <option value="">-- select vendor --</option>
                                                    @foreach ($vendors as $vendor)
                                                        <option value="{{ $vendor->id }}"
                                                            {{ old('business_partner_id', $order->business_partner_id) == $vendor->id ? 'selected' : '' }}>
                                                            {{ $vendor->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('business_partner_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="warehouse_id"
                                                    class="form-control form-control-sm select2bs4 @error('warehouse_id') is-invalid @enderror"
                                                    required>
                                                    <option value="">-- select warehouse --</option>
                                                    @foreach ($warehouses as $warehouse)
                                                        <option value="{{ $warehouse->id }}"
                                                            {{ old('warehouse_id', $order->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                                            {{ $warehouse->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('warehouse_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Reference No</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="reference_no"
                                                    value="{{ old('reference_no', $order->reference_no) }}"
                                                    class="form-control form-control-sm" placeholder="Reference number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Expected Delivery</label>
                                            <div class="col-sm-9">
                                                <input type="date" name="expected_delivery_date"
                                                    value="{{ old('expected_delivery_date', $order->expected_delivery_date ? $order->expected_delivery_date->format('Y-m-d') : '') }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Description</label>
                                            <div class="col-sm-10">
                                                <textarea name="description" class="form-control form-control-sm" rows="2"
                                                    placeholder="Purchase order description">{{ old('description', $order->description) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-secondary card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Order Lines
                                        </h3>
                                        <button type="button" class="btn btn-xs btn-primary float-right" id="add-line">
                                            <i class="fas fa-plus"></i> Add Line
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0" id="lines">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 20%">Item/Account <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 15%">Description</th>
                                                        <th style="width: 8%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 10%">Unit</th>
                                                        <th style="width: 10%">Unit Price <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 8%">VAT</th>
                                                        <th style="width: 8%">WTax</th>
                                                        <th style="width: 12%">Amount</th>
                                                        <th style="width: 10%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($order->lines as $index => $line)
                                                        <tr class="line-row">
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="text"
                                                                        name="lines[{{ $index }}][item_display]"
                                                                        class="form-control form-control-sm item-display"
                                                                        value="{{ old('lines.' . $index . '.item_display', $line->inventoryItem ? $line->inventoryItem->code : $line->item_code) }}"
                                                                        placeholder="-- select item --" readonly>
                                                                    <input type="hidden"
                                                                        name="lines[{{ $index }}][item_id]"
                                                                        class="item-id"
                                                                        value="{{ old('lines.' . $index . '.item_id', $line->inventory_item_id) }}">
                                                                    <input type="hidden"
                                                                        name="lines[{{ $index }}][id]"
                                                                        value="{{ $line->id }}">
                                                                    <div class="input-group-append">
                                                                        <button type="button"
                                                                            class="btn btn-outline-secondary btn-sm item-search-btn"
                                                                            data-line-idx="{{ $index }}"
                                                                            data-order-type="{{ $order->order_type }}">
                                                                            <i class="fas fa-search"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="lines[{{ $index }}][description]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ old('lines.' . $index . '.description', $line->inventoryItem ? $line->inventoryItem->name : $line->item_name) }}"
                                                                    placeholder="Description">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0.01"
                                                                    name="lines[{{ $index }}][qty]"
                                                                    class="form-control form-control-sm text-right qty-input"
                                                                    value="{{ old('lines.' . $index . '.qty', $line->quantity) }}"
                                                                    required>
                                                            </td>
                                                            <td>
                                                                <select name="lines[{{ $index }}][order_unit_id]"
                                                                    class="form-control form-control-sm unit-select select2bs4"
                                                                    data-line-idx="{{ $index }}">
                                                                    <option value="">Select Unit</option>
                                                                </select>
                                                                <div class="conversion-preview mt-1"
                                                                    style="font-size: 0.75rem; color: #6c757d;"></div>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0"
                                                                    name="lines[{{ $index }}][unit_price]"
                                                                    class="form-control form-control-sm text-right price-input"
                                                                    value="{{ old('lines.' . $index . '.unit_price', $line->unit_price) }}"
                                                                    required>
                                                            </td>
                                                            <td>
                                                                <select name="lines[{{ $index }}][vat_rate]"
                                                                    class="form-control form-control-sm vat-select select2bs4">
                                                                    <option value="0"
                                                                        {{ old('lines.' . $index . '.vat_rate', $line->vat_percent) == 0 ? 'selected' : '' }}>
                                                                        No</option>
                                                                    <option value="11"
                                                                        {{ old('lines.' . $index . '.vat_rate', $line->vat_percent) == 11 ? 'selected' : '' }}>
                                                                        11%</option>
                                                                    <option value="12"
                                                                        {{ old('lines.' . $index . '.vat_rate', $line->vat_percent) == 12 ? 'selected' : '' }}>
                                                                        12%</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select name="lines[{{ $index }}][wtax_rate]"
                                                                    class="form-control form-control-sm wtax-select select2bs4">
                                                                    <option value="0"
                                                                        {{ old('lines.' . $index . '.wtax_rate', $line->wtax_percent) == 0 ? 'selected' : '' }}>
                                                                        No</option>
                                                                    <option value="2"
                                                                        {{ old('lines.' . $index . '.wtax_rate', $line->wtax_percent) == 2 ? 'selected' : '' }}>
                                                                        2%</option>
                                                                </select>
                                                            </td>
                                                            <td class="text-right">
                                                                <span
                                                                    class="line-amount">{{ number_format($line->quantity * $line->unit_price + ($line->quantity * $line->unit_price * $line->vat_percent) / 100 - ($line->quantity * $line->unit_price * $line->wtax_percent) / 100, 2) }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-xs btn-danger rm">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Original Amount:</th>
                                                        <th class="text-right" id="original-amount">0.00</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Amount Due:</th>
                                                        <th colspan="4" class="text-right" id="amount-due">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button class="btn btn-primary" type="submit"
                                            data-confirm="Are you sure you want to update this purchase order?">
                                            <i class="fas fa-save mr-1"></i> Update Order
                                        </button>
                                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-default">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <div class="text-muted">
                                            <small>* Required fields</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Item Selection Modal -->
    <div class="modal fade" id="itemSelectionModal" tabindex="-1" role="dialog"
        aria-labelledby="itemSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemSelectionModalLabel">Select Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" id="searchCode"
                                placeholder="Search by code">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" id="searchName"
                                placeholder="Search by name">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="searchCategory">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control form-control-sm" id="searchType">
                                <option value="">All Types</option>
                                <option value="item">Item</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Unit</th>
                                    <th>Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Items will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div id="searchResultsCount" class="text-muted"></div>
                        <nav id="paginationContainer">
                            <!-- Pagination will be loaded here -->
                        </nav>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let i = {{ count($order->lines) }};
            let currentLineIndex = null;
            const $tb = $('#lines tbody');

            // Initialize Select2BS4
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Ensure VAT/WTax dropdowns show correct selected values
            $('.vat-select, .wtax-select').each(function() {
                const currentValue = $(this).val();
                if (currentValue) {
                    $(this).val(currentValue).trigger('change');
                }
            });

            // Initialize existing lines
            initializeExistingLines();

            // Add line button
            $('#add-line').on('click', function() {
                addLineRow();
            });

            // Remove line button
            $(document).on('click', '.rm', function() {
                Swal.fire({
                    title: 'Confirm Action',
                    text: 'Are you sure you want to delete this line?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(this).closest('tr').remove();
                        updateTotals();
                        toastr.success('Line deleted successfully!');
                    }
                });
            });

            // Item search button
            $(document).on('click', '.item-search-btn', function() {
                currentLineIndex = $(this).data('line-idx');
                $('#itemSelectionModal').modal('show');
                loadItems();
            });

            // Search inputs
            $('#searchCode, #searchName, #searchCategory, #searchType').on('input change', function() {
                loadItems();
            });

            // Select item
            $(document).on('click', '.select-item', function() {
                const itemData = $(this).data();
                const row = $(`input[name="lines[${currentLineIndex}][item_id]"]`).closest('tr');

                row.find('.item-id').val(itemData.itemId);
                row.find('.item-display').val(itemData.itemCode);
                row.find('input[name*="[description]"]').val(itemData.itemName);
                row.find('.price-input').val(itemData.itemPrice);

                loadUnitsForItem(itemData.itemId, currentLineIndex);
                updateLineAmount(row);
                updateTotals();

                $('#itemSelectionModal').modal('hide');
                toastr.success('Item selected successfully');
            });

            // Update totals when inputs change
            $(document).on('input', '.qty-input, .price-input', function() {
                updateLineAmount($(this).closest('tr'));
                updateTotals();
            });

            // Update totals when VAT or WTax changes
            $(document).on('change', '.vat-select, .wtax-select', function() {
                console.log('VAT/WTax changed:', $(this).val());
                updateLineAmount($(this).closest('tr'));
                updateTotals();
            });

            // Handle order type change
            $('#order_type').on('change', function() {
                updateAllLineDropdowns();
            });

            // Unit selection change
            $(document).on('change', '.unit-select', function() {
                const rowIndex = $(this).closest('tr').index();
                updateConversionPreview(rowIndex);
            });

            // Form submission with confirmation
            $('#po-form').on('submit', function(e) {
                const $submitBtn = $(this).find('button[type="submit"]');
                const confirmText = $submitBtn.data('confirm') ||
                    'Are you sure you want to update this purchase order?';

                e.preventDefault();

                Swal.fire({
                    title: 'Confirm Update',
                    text: confirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        $submitBtn.prop('disabled', true).html(
                            '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...');

                        // Submit the form
                        this.submit();
                    }
                });
            });

            function initializeExistingLines() {
                // Calculate amounts for existing lines
                $('.line-row').each(function() {
                    updateLineAmount($(this));
                });
                updateTotals();

                // Ensure VAT/WTax dropdowns have proper event handlers
                $('.vat-select, .wtax-select').off('change').on('change', function() {
                    updateLineAmount($(this).closest('tr'));
                    updateTotals();
                });
            }

            function addLineRow(data = {}) {
                const lineIdx = i++;
                const tr = document.createElement('tr');
                const orderType = $('#order_type').val() || 'item';

                tr.innerHTML = `
                    <td>
                        <div class="input-group">
                            <input type="text" name="lines[${lineIdx}][item_display]" class="form-control form-control-sm item-display" 
                                value="${data.item_display || ''}" placeholder="-- select ${orderType === 'item' ? 'item' : 'account'} --" readonly>
                            <input type="hidden" name="lines[${lineIdx}][item_id]" class="item-id" value="${data.item_id || ''}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-sm item-search-btn" 
                                        data-line-idx="${lineIdx}" data-order-type="${orderType}">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="lines[${lineIdx}][description]" class="form-control form-control-sm" 
                            value="${data.description || ''}" placeholder="Description">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" name="lines[${lineIdx}][qty]" 
                            class="form-control form-control-sm text-right qty-input" value="${data.qty || 1}" required>
                    </td>
                    <td>
                        <select name="lines[${lineIdx}][order_unit_id]" class="form-control form-control-sm unit-select select2bs4" data-line-idx="${lineIdx}">
                            <option value="">Select Unit</option>
                        </select>
                        <div class="conversion-preview mt-1" style="font-size: 0.75rem; color: #6c757d;"></div>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" name="lines[${lineIdx}][unit_price]" 
                            class="form-control form-control-sm text-right price-input" value="${data.unit_price || 0}" required>
                    </td>
                    <td>
                        <select name="lines[${lineIdx}][vat_rate]" class="form-control form-control-sm vat-select select2bs4">
                            <option value="0" ${data.vat_rate == 0 ? 'selected' : ''}>No</option>
                            <option value="11" ${data.vat_rate == 11 ? 'selected' : ''}>11%</option>
                            <option value="12" ${data.vat_rate == 12 ? 'selected' : ''}>12%</option>
                        </select>
                    </td>
                    <td>
                        <select name="lines[${lineIdx}][wtax_rate]" class="form-control form-control-sm wtax-select select2bs4">
                            <option value="0" ${data.wtax_rate == 0 ? 'selected' : ''}>No</option>
                            <option value="2" ${data.wtax_rate == 2 ? 'selected' : ''}>2%</option>
                        </select>
                    </td>
                    <td class="text-right">
                        <span class="line-amount">0.00</span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-danger rm">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;

                $tb.append(tr);

                // Initialize Select2BS4 for the newly added select elements (VAT and WTax only)
                $(tr).find('.select2bs4').select2({
                    theme: 'bootstrap4',
                    placeholder: 'Select an option',
                    allowClear: true
                });

                updateLineAmount(tr);
                updateTotals();
            }

            function updateAllLineDropdowns() {
                const orderType = $('#order_type').val() || 'item';
                $('#lines tbody tr').each(function() {
                    const $displayInput = $(this).find('.item-display');
                    const $searchBtn = $(this).find('.item-search-btn');

                    // Update placeholder text
                    $displayInput.attr('placeholder',
                        `-- select ${orderType === 'item' ? 'item' : 'account'} --`);

                    // Update search button data attribute
                    $searchBtn.attr('data-order-type', orderType);
                });
            }

            function updateLineAmount(row) {
                const qty = parseFloat($(row).find('.qty-input').val() || 0);
                const price = parseFloat($(row).find('.price-input').val() || 0);
                const vatRate = parseFloat($(row).find('.vat-select').val() || 0);
                const wtaxRate = parseFloat($(row).find('.wtax-select').val() || 0);

                const originalAmount = qty * price;
                const vatAmount = originalAmount * (vatRate / 100);
                const wtaxAmount = originalAmount * (wtaxRate / 100);
                const lineAmount = originalAmount + vatAmount - wtaxAmount;

                console.log('Line calculation:', {
                    qty: qty,
                    price: price,
                    vatRate: vatRate,
                    wtaxRate: wtaxRate,
                    originalAmount: originalAmount,
                    vatAmount: vatAmount,
                    wtaxAmount: wtaxAmount,
                    lineAmount: lineAmount
                });

                $(row).find('.line-amount').text(lineAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            function updateTotals() {
                let originalTotal = 0;
                let totalVat = 0;
                let totalWtax = 0;
                let totalAmount = 0;

                $('#lines tbody tr').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val() || 0);
                    const price = parseFloat($(this).find('.price-input').val() || 0);
                    const vatRate = parseFloat($(this).find('.vat-select').val() || 0);
                    const wtaxRate = parseFloat($(this).find('.wtax-select').val() || 0);

                    const originalAmount = qty * price;
                    const vatAmount = originalAmount * (vatRate / 100);
                    const wtaxAmount = originalAmount * (wtaxRate / 100);
                    const lineAmount = originalAmount + vatAmount - wtaxAmount;

                    originalTotal += originalAmount;
                    totalVat += vatAmount;
                    totalWtax += wtaxAmount;
                    totalAmount += lineAmount;
                });

                const amountDue = originalTotal + totalVat - totalWtax;

                // Update display with Indonesian number formatting
                $('#original-amount').text(originalTotal.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#total-vat').text(totalVat.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#total-wtax').text(totalWtax.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#total-amount').text(totalAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#amount-due').text(amountDue.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            // Modal functionality
            function loadItems(page = 1) {
                const searchData = {
                    code: $('#searchCode').val(),
                    name: $('#searchName').val(),
                    category_id: $('#searchCategory').val(),
                    item_type: $('#searchType').val(),
                    per_page: 20,
                    page: page
                };

                $.ajax({
                    url: '{{ route('inventory.search') }}',
                    method: 'GET',
                    data: searchData,
                    success: function(response) {
                        displayItems(response.items);
                        updatePagination(response.pagination);
                        updateSearchResultsCount(response.pagination.total);
                    },
                    error: function(xhr) {
                        console.error('Error loading items:', xhr.responseText);
                        alert('Error loading items. Please try again.');
                    }
                });
            }

            function displayItems(items) {
                const tbody = $('#itemsTable tbody');
                tbody.empty();

                if (items.length === 0) {
                    tbody.append('<tr><td colspan="7" class="text-center text-muted">No items found</td></tr>');
                    return;
                }

                items.forEach(function(item) {
                    const row = `
                        <tr>
                            <td>${item.code}</td>
                            <td>${item.name}</td>
                            <td>${item.category ? item.category.name : '-'}</td>
                            <td>${item.item_type}</td>
                            <td>${item.unit}</td>
                            <td>${item.price.toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm select-item" 
                                        data-item-id="${item.id}" 
                                        data-item-code="${item.code}" 
                                        data-item-name="${item.name}" 
                                        data-item-price="${item.price}">
                                    Select
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function updatePagination(pagination) {
                const container = $('#paginationContainer');
                container.empty();

                if (pagination.last_page <= 1) return;

                let paginationHtml = '<ul class="pagination pagination-sm mb-0">';

                // Previous button
                if (pagination.current_page > 1) {
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a></li>`;
                }

                // Page numbers
                for (let i = 1; i <= pagination.last_page; i++) {
                    const activeClass = i === pagination.current_page ? 'active' : '';
                    paginationHtml +=
                        `<li class="page-item ${activeClass}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }

                // Next button
                if (pagination.current_page < pagination.last_page) {
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a></li>`;
                }

                paginationHtml += '</ul>';
                container.html(paginationHtml);
            }

            function updateSearchResultsCount(total) {
                $('#searchResultsCount').text(`${total} items found`);
            }

            // Pagination click handler
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                loadItems(page);
            });

            function loadUnitsForItem(itemId, lineIndex) {
                if (!itemId) return;

                $.ajax({
                    url: `/api/inventory/${itemId}/units`,
                    method: 'GET',
                    success: function(response) {
                        const select = $(`select[name="lines[${lineIndex}][order_unit_id]"]`);
                        select.empty().append('<option value="">Select Unit</option>');

                        response.forEach(function(unit) {
                            select.append(`<option value="${unit.id}">${unit.name}</option>`);
                        });

                        select.select2({
                            theme: 'bootstrap4',
                            placeholder: 'Select Unit',
                            allowClear: true
                        });
                    },
                    error: function(xhr) {
                        console.error('Error loading units:', xhr.responseText);
                    }
                });
            }

            function updateConversionPreview(lineIndex) {
                // This function can be expanded to show unit conversion previews
                const row = $(`select[name="lines[${lineIndex}][order_unit_id]"]`).closest('tr');
                const preview = row.find('.conversion-preview');
                preview.text(''); // Clear preview for now
            }

            // Load units for existing lines
            @foreach ($order->lines as $index => $line)
                @if ($line->inventory_item_id)
                    loadUnitsForItem({{ $line->inventory_item_id }}, {{ $index }});
                    // Set the selected unit
                    setTimeout(function() {
                        $('select[name="lines[{{ $index }}][order_unit_id]"]').val(
                            {{ $line->order_unit_id ?? 'null' }});
                        updateConversionPreview({{ $index }});
                    }, 500);
                @endif
            @endforeach

            // Display success/error messages from session
            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        });
    </script>
@endpush
