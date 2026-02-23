@extends('layouts.main')

@section('title', 'Create ' . ($documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue'))

@section('title_page')
    Create {{ $documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue' }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('gr-gi.index') }}">GR/GI Management</a></li>
    <li class="breadcrumb-item active">Create {{ $documentType === 'goods_receipt' ? 'GR' : 'GI' }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus mr-1"></i>
                                Create {{ $documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue' }}
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('gr-gi.index') }}" class="btn btn-tool btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to GR/GI
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('gr-gi.store') }}" method="POST" id="gr-gi-form">
                            @csrf
                            <div class="card-body">
                                <!-- Header Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_type">Document Type</label>
                                            <input type="text" class="form-control"
                                                value="{{ $documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue' }}"
                                                readonly>
                                            <input type="hidden" name="document_type" value="{{ $documentType }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="purpose_id">Purpose <span class="text-danger">*</span></label>
                                            <select class="form-control" name="purpose_id" id="purpose_id" required>
                                                <option value="">Select Purpose</option>
                                                @foreach ($purposes as $purpose)
                                                    <option value="{{ $purpose->id }}">{{ $purpose->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('purpose_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="warehouse_id">Warehouse <span class="text-danger">*</span></label>
                                            <select class="form-control" name="warehouse_id" id="warehouse_id" required>
                                                <option value="">Select Warehouse</option>
                                                @foreach ($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}">{{ $warehouse->code }} -
                                                        {{ $warehouse->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('warehouse_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_date">Transaction Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="transaction_date"
                                                id="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}"
                                                required>
                                            @error('transaction_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_number">Reference Number</label>
                                            <input type="text" class="form-control" name="reference_number"
                                                id="reference_number" value="{{ old('reference_number') }}"
                                                placeholder="Optional reference number">
                                            @error('reference_number')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Lines Section -->
                                <hr>
                                <h5><i class="fas fa-list mr-1"></i> Document Lines</h5>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="lines-table">
                                        <thead>
                                            <tr>
                                                <th width="30%">Item <span class="text-danger">*</span></th>
                                                <th width="15%">Quantity <span class="text-danger">*</span></th>
                                                <th width="15%">Unit Price <span class="text-danger">*</span></th>
                                                <th width="15%">Total Amount</th>
                                                <th width="20%">Notes</th>
                                                <th width="5%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lines-tbody">
                                            <tr class="line-row">
                                                <td>
                                                    <div class="input-group">
                                                        <input type="text" name="lines[0][item_display]" class="form-control item-display"
                                                            placeholder="Select item" readonly>
                                                        <input type="hidden" name="lines[0][item_id]" class="item-id" value="">
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm item-search-btn"
                                                                data-line-idx="0">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control quantity-input"
                                                        name="lines[0][quantity]" step="0.001" min="0.001" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control unit-price-input"
                                                        name="lines[0][unit_price]" step="0.01" min="0"
                                                        required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control total-amount-input"
                                                        readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="lines[0][notes]"
                                                        placeholder="Optional notes">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm remove-line"
                                                        disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-success btn-sm" id="add-line">
                                            <i class="fas fa-plus"></i> Add Line
                                        </button>
                                    </div>
                                </div>

                                <!-- Total Summary -->
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 offset-md-6">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Total Amount:</th>
                                                    <td class="text-right">
                                                        <strong id="total-amount">0.00</strong>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create
                                    {{ $documentType === 'goods_receipt' ? 'GR' : 'GI' }}
                                </button>
                                <a href="{{ route('gr-gi.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('components.item-selection-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let lineIndex = 0;

            // Add line functionality
            $('#add-line').on('click', function() {
                lineIndex++;
                const newRow = `
            <tr class="line-row">
                <td>
                    <div class="input-group">
                        <input type="text" name="lines[${lineIndex}][item_display]" class="form-control item-display"
                            placeholder="Select item" readonly>
                        <input type="hidden" name="lines[${lineIndex}][item_id]" class="item-id" value="">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm item-search-btn"
                                data-line-idx="${lineIndex}">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input" name="lines[${lineIndex}][quantity]" step="0.001" min="0.001" required>
                </td>
                <td>
                    <input type="number" class="form-control unit-price-input" name="lines[${lineIndex}][unit_price]" step="0.01" min="0" required>
                </td>
                <td>
                    <input type="number" class="form-control total-amount-input" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" name="lines[${lineIndex}][notes]" placeholder="Optional notes">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-line">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
                $('#lines-tbody').append(newRow);
                updateRemoveButtons();
            });

            // Remove line functionality
            $(document).on('click', '.remove-line', function() {
                $(this).closest('tr').remove();
                updateRemoveButtons();
                calculateTotal();
            });

            // Update remove buttons state
            function updateRemoveButtons() {
                const rows = $('.line-row').length;
                $('.remove-line').prop('disabled', rows <= 1);
            }

            // Calculate total amount for a line
            $(document).on('input', '.quantity-input, .unit-price-input', function() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
                const totalAmount = quantity * unitPrice;

                row.find('.total-amount-input').val(totalAmount.toFixed(2));
                calculateTotal();
            });

            // Calculate total amount
            function calculateTotal() {
                let total = 0;
                $('.total-amount-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#total-amount').text(total.toFixed(2));
            }

            // Item search button - open modal
            $(document).on('click', '.item-search-btn', function() {
                window.currentLineIdx = $(this).data('line-idx');
                $('#itemSelectionModal').modal('show');
                loadItems();
            });

            // Form validation
            $('#gr-gi-form').on('submit', function(e) {
                const lines = $('.line-row').length;
                if (lines === 0) {
                    e.preventDefault();
                    alert('Please add at least one line item.');
                    return false;
                }

                // Validate all required fields
                let isValid = true;
                $('.line-row').each(function() {
                    const itemId = $(this).find('.item-id').val();
                    const quantity = $(this).find('.quantity-input').val();
                    const unitPrice = $(this).find('.unit-price-input').val();

                    if (!itemId || !quantity || !unitPrice) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields for all line items.');
                    return false;
                }
            });

            // Modal: load items from inventory search
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
                        $('#searchResultsCount').text('Found ' + response.pagination.total + ' items');
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
                    tbody.append('<tr><td colspan="10" class="text-center text-muted">No items found</td></tr>');
                    return;
                }

                items.forEach((item, index) => {
                    const itemType = item.item_type || 'item';
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${item.code}</strong></td>
                            <td>${item.name}</td>
                            <td>${item.category ? item.category.name : '-'}</td>
                            <td><span class="badge badge-${itemType === 'item' ? 'primary' : 'info'}">${itemType}</span></td>
                            <td>${item.unit_of_measure || '-'}</td>
                            <td class="text-right">${formatCurrency(item.purchase_price)}</td>
                            <td class="text-right">${formatCurrency(item.selling_price)}</td>
                            <td>${item.current_stock != null ? item.current_stock : '-'}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success select-item-btn"
                                        data-item-id="${item.id}"
                                        data-item-code="${item.code}"
                                        data-item-name="${item.name}"
                                        data-item-purchase-price="${item.purchase_price}">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function updatePagination(pagination) {
                const container = $('#itemsPagination');
                container.empty();

                if (pagination.last_page <= 1) return;

                if (pagination.current_page > 1) {
                    container.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a></li>`);
                }
                for (let i = 1; i <= pagination.last_page; i++) {
                    const activeClass = i === pagination.current_page ? 'active' : '';
                    container.append(`<li class="page-item ${activeClass}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
                }
                if (pagination.current_page < pagination.last_page) {
                    container.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a></li>`);
                }
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount || 0);
            }

            $('#searchItems').on('click', function() {
                loadItems(1);
            });

            $('#clearSearch').on('click', function() {
                $('#searchCode, #searchName').val('');
                $('#searchCategory, #searchType').val('');
                loadItems(1);
            });

            $(document).on('click', '#itemsPagination .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) loadItems(page);
            });

            $(document).on('click', '.select-item-btn', function() {
                const itemId = $(this).data('item-id');
                const itemCode = $(this).data('item-code');
                const itemName = $(this).data('item-name');
                const purchasePrice = $(this).data('item-purchase-price');

                const displayInput = $(`input[name="lines[${window.currentLineIdx}][item_display]"]`);
                const hiddenInput = $(`input[name="lines[${window.currentLineIdx}][item_id]"]`);
                const row = displayInput.closest('tr');

                displayInput.val(itemCode + ' - ' + itemName);
                hiddenInput.val(itemId);

                @if ($documentType === 'goods_receipt')
                    row.find('.unit-price-input').val(purchasePrice || 0);
                    row.find('.quantity-input').trigger('input');
                @else
                    $.ajax({
                        url: '{{ route('gr-gi.calculate-valuation') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            item_id: itemId,
                            warehouse_id: $('#warehouse_id').val(),
                            quantity: 1,
                            method: 'FIFO'
                        },
                        success: function(response) {
                            row.find('.unit-price-input').val(response.unit_price);
                            row.find('.quantity-input').trigger('input');
                        },
                        error: function() {
                            row.find('.unit-price-input').val(purchasePrice || 0);
                            row.find('.quantity-input').trigger('input');
                        }
                    });
                @endif
                $('#itemSelectionModal').modal('hide');
            });

            // Initialize
            updateRemoveButtons();
        });
    </script>
@endpush
