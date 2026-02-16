@extends('layouts.main')

@section('title', 'Create Goods Receipt PO')

@section('title_page')
    Create Goods Receipt PO
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('goods-receipt-pos.index') }}">Goods Receipt PO</a></li>
    <li class="breadcrumb-item active">Create</li>
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
                                <i class="fas fa-truck mr-1"></i>
                                New Goods Receipt PO
                            </h3>
                            <a href="{{ route('goods-receipt-pos.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Goods Receipt PO
                            </a>
                        </div>
                        <form method="post" action="{{ route('goods-receipt-pos.store') }}" id="grpo-form">
                            @csrf
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
                                                        value="{{ old('date', now()->toDateString()) }}"
                                                        class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">GRPO Number</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" id="grn_no_preview" class="form-control bg-light" readonly
                                                        placeholder="Will be assigned on save">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary" id="preview-grpo-number" title="Preview next number (does not consume)">
                                                            <i class="fas fa-eye"></i> Preview
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Number is generated when you save. Preview shows next number without consuming it.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Company <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="company_entity_id" id="company_entity_id"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    @foreach ($entities as $entity)
                                                        <option value="{{ $entity->id }}"
                                                            {{ old('company_entity_id', $defaultEntity->id) == $entity->id ? 'selected' : '' }}>
                                                            {{ $entity->name }} ({{ $entity->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Vendor <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id" id="vendor-select"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    <option value="">-- select vendor --</option>
                                                    @foreach ($vendors as $v)
                                                        <option value="{{ $v->id }}"
                                                            {{ old('business_partner_id') == $v->id ? 'selected' : '' }}>
                                                            {{ $v->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="warehouse_id" id="warehouse-select"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    <option value="">-- select warehouse --</option>
                                                    @foreach ($warehouses as $w)
                                                        <option value="{{ $w->id }}"
                                                            {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>
                                                            {{ $w->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Purchase Order</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <select name="purchase_order_id" id="po-select"
                                                        class="form-control form-control-sm select2bs4" disabled>
                                                        <option value="">-- select vendor first --</option>
                                                    </select>
                                                    <div class="input-group-append">
                                                        <button type="button" id="copy-lines-btn"
                                                            class="btn btn-sm btn-success" disabled>
                                                            <i class="fas fa-copy"></i> Copy Lines
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-secondary card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Receipt Lines
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
                                                        <th style="width: 25%">Item/Account <span
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 30%">Description</th>
                                                        <th style="width: 15%">Remaining Qty</th>
                                                        <th style="width: 15%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 15%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="4" class="text-right">Total Lines:</th>
                                                        <th class="text-right" id="total-lines">0</th>
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
                                        <button class="btn btn-primary" type="submit" id="save-grpo-btn">
                                            <i class="fas fa-save mr-1"></i> Save GRPO
                                        </button>
                                        <button type="button" class="btn btn-info ml-2" id="preview-journal-btn">
                                            <i class="fas fa-eye mr-1"></i> Preview Journal
                                        </button>
                                        <a href="{{ route('goods-receipt-pos.index') }}" class="btn btn-default"
                                            id="cancel-btn">
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
    <div class="modal fade" id="itemSelectModal" tabindex="-1" role="dialog" aria-labelledby="itemSelectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itemSelectModalLabel">
                        <i class="fas fa-search mr-1"></i> Select Item
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Search Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <input type="text" id="searchCode" class="form-control form-control-sm"
                                placeholder="Item Code">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="searchName" class="form-control form-control-sm"
                                placeholder="Item Name">
                        </div>
                        <div class="col-md-3">
                            <select id="searchCategory" class="form-control form-control-sm">
                                <option value="">All Categories</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ is_object($category) ? $category->id : $category->id }}">
                                        @if (is_object($category) && method_exists($category, 'getHierarchicalName'))
                                            {{ $category->getHierarchicalName() }}
                                        @else
                                            {{ is_object($category) ? ($category->name ?? '-') : ($category->name ?? '-') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary btn-sm" onclick="loadItems()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Unit Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="searchResultsCount"></div>
                        <nav id="paginationContainer"></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.prefill = @json($prefill ?? null);

        $(document).ready(function() {
            // Initialize Select2BS4 for all select elements
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            function updateDocumentNumber() {
                const entityId = $('#company_entity_id').val();
                const date = $('input[name="date"]').val() || new Date().toISOString().slice(0, 10);
                if (!entityId) return;
                $.ajax({
                    url: '{{ route('goods-receipt-pos.api.document-number') }}',
                    method: 'GET',
                    data: { company_entity_id: entityId, date: date },
                    success: function(response) {
                        if (response.document_number) {
                            $('#grn_no_preview').val(response.document_number);
                        } else if (response.error) {
                            console.error('Document number error:', response.error);
                        }
                    },
                    error: function(xhr) {
                        console.error('Document number request failed:', xhr);
                    }
                });
            }

            $('#company_entity_id').on('change', updateDocumentNumber);
            $('input[name="date"]').on('change', updateDocumentNumber);
            $('#preview-grpo-number').on('click', updateDocumentNumber);
            updateDocumentNumber();

            let i = 0;
            const $tb = $('#lines tbody');

            // Add first line
            $('#add-line').on('click', function() {
                addLineRow();
                toastr.success('New line added successfully');
            }).trigger('click');

            // Remove line with SweetAlert2 confirmation
            $tb.on('click', '.rm', function() {
                const $row = $(this).closest('tr');
                const lineNumber = $tb.find('tr').index($row) + 1;

                Swal.fire({
                    title: 'Delete Line?',
                    text: `Are you sure you want to delete line ${lineNumber}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $row.remove();
                        updateTotalLines();
                        toastr.success(`Line ${lineNumber} deleted successfully`);
                    }
                });
            });

            // Update total lines when quantity changes
            $(document).on('input', '.qty-input', function() {
                updateTotalLines();
            });

            // Vendor selection handler - load POs for selected vendor
            $('#vendor-select').on('change', function() {
                const vendorId = $(this).val();
                const $poSelect = $('#po-select');
                const $copyBtn = $('#copy-lines-btn');

                if (vendorId) {
                    // Enable PO select and load vendor's POs
                    $poSelect.prop('disabled', false);
                    $poSelect.empty().append('<option value="">-- loading POs --</option>');

                    $.get('{{ route('goods-receipt-pos.vendor-pos') }}', {
                            business_partner_id: vendorId
                        })
                        .done(function(data) {
                            $poSelect.empty().append('<option value="">-- select PO --</option>');

                            if (data.purchase_orders.length > 0) {
                                $.each(data.purchase_orders, function(index, po) {
                                    $poSelect.append(
                                        `<option value="${po.id}">${po.order_no} (${po.date}) - ${po.remaining_lines_count} lines</option>`
                                    );
                                });
                            } else {
                                $poSelect.append('<option value="">-- no open POs found --</option>');
                            }
                        })
                        .fail(function() {
                            $poSelect.empty().append(
                                '<option value="">-- error loading POs --</option>');
                        });
                } else {
                    // Disable PO select and copy button
                    $poSelect.prop('disabled', true).empty().append(
                        '<option value="">-- select vendor first --</option>');
                    $copyBtn.prop('disabled', true);
                }
            });

            // PO selection handler - enable copy button
            $('#po-select').on('change', function() {
                const poId = $(this).val();
                const $copyBtn = $('#copy-lines-btn');

                if (poId) {
                    $copyBtn.prop('disabled', false);
                } else {
                    $copyBtn.prop('disabled', true);
                }
            });

            // Copy remaining lines button handler with SweetAlert2 confirmation
            $('#copy-lines-btn').on('click', function() {
                const poId = $('#po-select').val();

                if (!poId) {
                    toastr.error('Please select a Purchase Order first');
                    return;
                }

                // Confirm before copying with SweetAlert2
                Swal.fire({
                    title: 'Copy Lines from PO?',
                    text: 'This will copy all remaining lines from the selected PO. Existing lines will be replaced.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, copy lines!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.get('{{ route('goods-receipt-pos.remaining-lines') }}', {
                                purchase_order_id: poId
                            })
                            .done(function(data) {
                                if (data.lines.length > 0) {
                                    // Clear existing lines
                                    $tb.empty();
                                    i = 0;

                                    // Add copied lines
                                    $.each(data.lines, function(index, line) {
                                        addLineRow({
                                            item_id: line.item_id,
                                            item_display: line.item_display,
                                            description: line.description,
                                            qty: line.qty,
                                            remaining_qty: line
                                                .qty, // Use the pending_qty as remaining_qty
                                            unit_price: line.unit_price
                                        });
                                    });

                                    updateTotalLines();
                                    toastr.success(
                                        `Successfully copied ${data.lines.length} lines from PO`
                                    );
                                } else {
                                    toastr.warning(
                                        'No remaining lines found in the selected PO');
                                }
                            })
                            .fail(function() {
                                toastr.error('Error loading PO lines. Please try again.');
                            });
                    }
                });
            });

            // Handle prefill data if available
            if (window.prefill) {
                $tb.empty();
                i = 0;
                $('[name=date]').val(window.prefill.date);
                $('[name=business_partner_id]').val(window.prefill.business_partner_id);
                $('#vendor-select').trigger('change'); // Trigger vendor change to load POs

                // Wait for POs to load, then set the selected PO
                setTimeout(function() {
                    $('[name=purchase_order_id]').val(window.prefill.purchase_order_id);
                    $('#po-select').trigger('change');
                }, 1000);

                if (window.prefill.lines && window.prefill.lines.length > 0) {
                    window.prefill.lines.forEach(function(l) {
                        addLineRow(l);
                    });
                } else {
                    addLineRow();
                }

                updateTotalAmount();
            }

            function addLineRow(data = {}) {
                const lineIdx = i++;
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td>
                        <div class="input-group">
                            <input type="text" name="lines[${lineIdx}][item_display]" class="form-control form-control-sm item-display" 
                                value="${data.item_display || ''}" placeholder="-- select item --" readonly>
                            <input type="hidden" name="lines[${lineIdx}][item_id]" class="item-id" value="${data.item_id || ''}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-sm item-search-btn" 
                                        data-line-idx="${lineIdx}">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="lines[${lineIdx}][description]" class="form-control form-control-sm" 
                            value="${data.description || ''}" placeholder="Description">
                    </td>
                    <td class="text-right">
                        <span class="remaining-qty">${data.remaining_qty || '0.00'}</span>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0.01" name="lines[${lineIdx}][qty]" 
                            class="form-control form-control-sm text-right qty-input" value="${data.qty || 1}" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-danger rm">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;

                $tb.append(tr);
                updateTotalLines();
            }

            function updateTotalLines() {
                const totalLines = $('#lines tbody tr').length;
                $('#total-lines').text(totalLines);
            }

            // Cancel button with SweetAlert2 confirmation
            $('#cancel-btn').on('click', function(e) {
                e.preventDefault();

                // Check if form has any data
                const hasData = $('[name="business_partner_id"]').val() ||
                    $('#lines tbody tr').length > 0 ||
                    $('[name="date"]').val() !== '{{ now()->toDateString() }}';

                if (hasData) {
                    Swal.fire({
                        title: 'Cancel GRPO Creation?',
                        text: 'You have unsaved changes. Are you sure you want to cancel?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, cancel!',
                        cancelButtonText: 'Continue editing',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route('goods-receipt-pos.index') }}';
                        }
                    });
                } else {
                    window.location.href = '{{ route('goods-receipt-pos.index') }}';
                }
            });

            // Save button with SweetAlert2 confirmation
            $('#save-grpo-btn').on('click', function(e) {
                e.preventDefault();

                // Basic validation
                if (!$('[name="business_partner_id"]').val()) {
                    toastr.error('Please select a vendor');
                    return;
                }

                if ($('#lines tbody tr').length === 0) {
                    toastr.error('Please add at least one line item');
                    return;
                }

                // Check if all required fields are filled
                let hasErrors = false;
                $('#lines tbody tr').each(function() {
                    const $row = $(this);
                    if (!$row.find('.item-id').val()) {
                        toastr.error('Please select an item for all lines');
                        hasErrors = true;
                        return false;
                    }
                    if (!$row.find('.qty-input').val() || parseFloat($row.find('.qty-input')
                            .val()) <= 0) {
                        toastr.error('Please enter valid quantities for all lines');
                        hasErrors = true;
                        return false;
                    }
                });

                if (hasErrors) return;

                // Show confirmation dialog
                Swal.fire({
                    title: 'Save GRPO?',
                    text: 'Are you sure you want to save this Goods Receipt PO?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, save it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form
                        $('#grpo-form').submit();
                    }
                });
            });

            // Item search modal functionality (similar to PO)
            $(document).on('click', '.item-search-btn', function() {
                window.currentLineIdx = $(this).data('line-idx');
                $('#itemSelectModal').modal('show');

                // Check if a PO is selected to filter items
                const poId = $('#po-select').val();
                if (poId) {
                    loadItemsFromPO(poId);
                } else {
                    loadItems();
                }
            });

            // Item selection handler
            $(document).on('click', '.select-item-btn', function() {
                const itemId = $(this).data('item-id');
                const itemCode = $(this).data('item-code');
                const itemName = $(this).data('item-name');
                const itemPrice = $(this).data('item-price');
                const remainingQty = $(this).data('remaining-qty') || 0;

                // Update the display and hidden input fields
                const itemDisplayInput = $(`input[name="lines[${window.currentLineIdx}][item_display]"]`);
                const itemIdInput = $(`input[name="lines[${window.currentLineIdx}][item_id]"]`);

                itemDisplayInput.val(`${itemCode} - ${itemName}`);
                itemIdInput.val(itemId);

                // Update remaining quantity display
                const remainingQtySpan = $(`input[name="lines[${window.currentLineIdx}][item_display]"]`)
                    .closest('tr').find('.remaining-qty');
                remainingQtySpan.text(parseFloat(remainingQty).toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));

                // Update total lines count
                updateTotalLines();

                $('#itemSelectModal').modal('hide');
                toastr.success(`Item "${itemCode} - ${itemName}" selected successfully`);
            });

            // Modal functionality
            function loadItems(page = 1) {
                const searchData = {
                    code: $('#searchCode').val(),
                    name: $('#searchName').val(),
                    category_id: $('#searchCategory').val(),
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
                        toastr.error('Error loading items. Please try again.');
                    }
                });
            }

            function loadItemsFromPO(poId) {
                $.ajax({
                    url: '{{ route('goods-receipt-pos.remaining-lines') }}',
                    method: 'GET',
                    data: {
                        purchase_order_id: poId
                    },
                    success: function(response) {
                        // Convert PO lines to item format for display
                        const items = response.lines.map(line => ({
                            id: line.item_id,
                            code: line.item_code,
                            name: line.item_name,
                            category: 'From PO',
                            type: 'item',
                            unit_price: line.unit_price,
                            stock: line.qty, // Use remaining qty as stock
                            remaining_qty: line.qty
                        }));

                        displayItemsFromPO(items);
                        updateSearchResultsCount(items.length);
                    },
                    error: function(xhr) {
                        console.error('Error loading PO items:', xhr.responseText);
                        toastr.error('Error loading items from PO. Please try again.');
                    }
                });
            }

            function displayItems(items) {
                const tbody = $('#itemsTable tbody');
                tbody.empty();

                if (items.length === 0) {
                    tbody.append('<tr><td colspan="8" class="text-center text-muted">No items found</td></tr>');
                    return;
                }

                items.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${item.code}</strong></td>
                            <td>${item.name}</td>
                            <td>${item.category ? item.category.name : '-'}</td>
                            <td>${item.item_type}</td>
                            <td class="text-right">${parseFloat(item.unit_price || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                            <td class="text-right">${parseFloat(item.stock_qty || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-success select-item-btn" 
                                        data-item-id="${item.id}" 
                                        data-item-code="${item.code}" 
                                        data-item-name="${item.name}" 
                                        data-item-price="${item.unit_price || 0}">
                                    <i class="fas fa-check"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function displayItemsFromPO(items) {
                const tbody = $('#itemsTable tbody');
                tbody.empty();

                if (items.length === 0) {
                    tbody.append(
                        '<tr><td colspan="8" class="text-center text-muted">No items found in selected PO</td></tr>'
                    );
                    return;
                }

                items.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${item.code}</strong></td>
                            <td>${item.name}</td>
                            <td>${item.category}</td>
                            <td>${item.type}</td>
                            <td class="text-right">${parseFloat(item.unit_price || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                            <td class="text-right">${parseFloat(item.remaining_qty || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-success select-item-btn" 
                                        data-item-id="${item.id}" 
                                        data-item-code="${item.code}" 
                                        data-item-name="${item.name}" 
                                        data-item-price="${item.unit_price || 0}"
                                        data-remaining-qty="${item.remaining_qty || 0}">
                                    <i class="fas fa-check"></i>
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

                let paginationHtml = '<ul class="pagination pagination-sm">';

                // Previous button
                if (pagination.current_page > 1) {
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${pagination.current_page - 1})">Previous</a></li>`;
                }

                // Page numbers
                for (let i = 1; i <= pagination.last_page; i++) {
                    if (i === pagination.current_page) {
                        paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                    } else {
                        paginationHtml +=
                            `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${i})">${i}</a></li>`;
                    }
                }

                // Next button
                if (pagination.current_page < pagination.last_page) {
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${pagination.current_page + 1})">Next</a></li>`;
                }

                paginationHtml += '</ul>';
                container.html(paginationHtml);
            }

            function updateSearchResultsCount(total) {
                $('#searchResultsCount').text(`Showing ${total} items`);
            }

            // Preview Journal button functionality
            $('#preview-journal-btn').click(function() {
                // Validate form first
                if (!validateForm()) {
                    toastr.error('Please fill in all required fields before previewing journal entries.');
                    return;
                }

                // Get form data
                const formData = new FormData(document.getElementById('grpo-form'));

                // Show loading
                Swal.fire({
                    title: 'Generating Journal Preview...',
                    text: 'Please wait while we prepare the journal entries.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request to preview journal
                $.ajax({
                    url: '/api/journal-preview/grpo',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.close();
                        showJournalPreviewModal(response);
                    },
                    error: function(xhr) {
                        Swal.close();
                        let errorMessage = 'Failed to generate journal preview.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    }
                });
            });

            function showJournalPreviewModal(data) {
                let modalHtml = `
                    <div class="modal fade" id="journalPreviewModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-eye mr-2"></i>Preview Journal Entries
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Journal Number:</strong> ${data.journal_number || 'Auto-generated'}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Date:</strong> ${data.date || new Date().toLocaleDateString()}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <strong>Description:</strong> ${data.description || 'GRPO Receipt -'}
                                        </div>
                                    </div>
                                    <h6>Journal Lines:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Account</th>
                                                    <th>Description</th>
                                                    <th class="text-right">Debit</th>
                                                    <th class="text-right">Credit</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;

                data.lines.forEach(function(line) {
                    modalHtml += `
                        <tr>
                            <td>${line.account_code} - ${line.account_name}</td>
                            <td>${line.description}</td>
                            <td class="text-right">${line.debit ? 'Rp ' + parseFloat(line.debit).toLocaleString('id-ID') : ''}</td>
                            <td class="text-right">${line.credit ? 'Rp ' + parseFloat(line.credit).toLocaleString('id-ID') : ''}</td>
                        </tr>`;
                });

                modalHtml += `
                                            </tbody>
                                            <tfoot>
                                                <tr class="font-weight-bold">
                                                    <td colspan="2">Total</td>
                                                    <td class="text-right">Rp ${parseFloat(data.total_debit || 0).toLocaleString('id-ID')}</td>
                                                    <td class="text-right">Rp ${parseFloat(data.total_credit || 0).toLocaleString('id-ID')}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="alert ${data.is_balanced ? 'alert-success' : 'alert-danger'}">
                                        <i class="fas ${data.is_balanced ? 'fa-check' : 'fa-times'} mr-2"></i>
                                        Journal is ${data.is_balanced ? 'balanced' : 'not balanced'}
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>`;

                // Remove existing modal if any
                $('#journalPreviewModal').remove();

                // Add modal to body
                $('body').append(modalHtml);

                // Show modal
                $('#journalPreviewModal').modal('show');
            }

            function validateForm() {
                // Check if vendor is selected
                if (!$('#business_partner_id').val()) {
                    return false;
                }

                // Check if warehouse is selected
                if (!$('#warehouse_id').val()) {
                    return false;
                }

                // Check if at least one line exists
                if ($('#grpo-lines tbody tr').length === 0) {
                    return false;
                }

                // Check if all lines have items and quantities
                let isValid = true;
                $('#grpo-lines tbody tr').each(function() {
                    const itemInput = $(this).find('input[name*="[item_id]"]');
                    const qtyInput = $(this).find('input[name*="[qty]"]');

                    if (!itemInput.val() || !qtyInput.val() || parseFloat(qtyInput.val()) <= 0) {
                        isValid = false;
                        return false;
                    }
                });

                return isValid;
            }
        });
    </script>
@endpush
