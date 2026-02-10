@extends('layouts.main')

@section('title', 'Create Sales Order')

@section('title_page')
    Create Sales Order
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-orders.index') }}">Sales Orders</a></li>
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
                                <i class="fas fa-shopping-bag mr-1"></i>
                                New Sales Order
                            </h3>
                            <a href="{{ route('sales-orders.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Sales Orders
                            </a>
                        </div>
                        <form method="post" action="{{ route('sales-orders.store') }}" id="so-form">
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
                                            <label class="col-sm-3 col-form-label">SO Number</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" name="order_no" value="{{ $soNumber }}"
                                                        class="form-control bg-light" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Reference No</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                                                    </div>
                                                    <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                                                        class="form-control" placeholder="Customer reference number">
                                                </div>
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
                                            <label class="col-sm-3 col-form-label">Order Type <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="order_type" class="form-control form-control-sm select2bs4"
                                                    id="order_type" required>
                                                    <option value="">-- select type --</option>
                                                    <option value="item"
                                                        {{ old('order_type', 'item') == 'item' ? 'selected' : '' }}>
                                                        Item (Physical Inventory)
                                                    </option>
                                                    <option value="service"
                                                        {{ old('order_type') == 'service' ? 'selected' : '' }}>
                                                        Service (Non-Inventory)
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Customer <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    <option value="">-- select customer --</option>
                                                    @foreach ($customers as $c)
                                                        <option value="{{ $c->id }}"
                                                            {{ old('business_partner_id') == $c->id ? 'selected' : '' }}
                                                            data-address="{{ e($c->default_shipping_address ?? '') }}"
                                                            data-contact="{{ e($c->primary_contact_name ?? '') }}"
                                                            data-phone="{{ e($c->primary_contact_phone ?? '') }}">
                                                            {{ $c->name }}
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
                                                <select name="warehouse_id" class="form-control form-control-sm select2bs4"
                                                    required>
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
                                            <label class="col-sm-3 col-form-label">Currency <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="currency_id" id="currency_id"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    <option value="">-- select currency --</option>
                                                    @foreach ($currencies as $currency)
                                                        <option value="{{ $currency->id }}"
                                                            {{ old('currency_id', 1) == $currency->id ? 'selected' : '' }}>
                                                            {{ $currency->code }} - {{ $currency->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Exchange Rate</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="exchange_rate" id="exchange_rate"
                                                        class="form-control form-control-sm" step="0.000001"
                                                        value="{{ old('exchange_rate', '1.000000') }}">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                            id="refresh-rate-btn" title="Refresh Exchange Rate">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Auto-updated based on selected currency
                                                    and date</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-info card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-truck mr-1"></i>
                                            Delivery Address
                                        </h3>
                                        <small class="text-muted">Default from customer. Can be overridden. Used for Delivery Orders.</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-3 col-form-label">Address</label>
                                                    <div class="col-sm-9">
                                                        <textarea name="delivery_address" id="delivery_address" class="form-control form-control-sm" rows="3" placeholder="Select customer to auto-fill">{{ old('delivery_address') }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-4 col-form-label">Contact Person</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" name="delivery_contact_person" id="delivery_contact_person" value="{{ old('delivery_contact_person') }}" class="form-control form-control-sm" placeholder="Contact name">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-2">
                                                    <label class="col-sm-4 col-form-label">Phone</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" name="delivery_phone" id="delivery_phone" value="{{ old('delivery_phone') }}" class="form-control form-control-sm" placeholder="Phone number">
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
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 20%">Description</th>
                                                        <th style="width: 10%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 12%">Unit Price <span
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 8%">VAT</th>
                                                        <th style="width: 8%">WTax</th>
                                                        <th style="width: 12%">Amount</th>
                                                        <th style="width: 10%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
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
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-1"></i> Save Order
                                        </button>
                                        <a href="{{ route('sales-orders.index') }}" class="btn btn-default">
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

    <!-- Include Item Selection Modal -->
    @include('components.item-selection-modal')
@endsection

@push('scripts')
    <script>
        window.prefill = @json($prefill ?? null);
        window.inventoryItems = @json($inventoryItems);
        window.accounts = @json($accounts);

        $(document).ready(function() {
            // Initialize Select2BS4 for all select elements
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            let i = 0;
            const $tb = $('#lines tbody');

            // Add first line
            $('#add-line').on('click', function() {
                addLineRow();
            }).trigger('click');

            // Remove line
            $tb.on('click', '.rm', function() {
                $(this).closest('tr').remove();
                updateTotals();
            });

            // Update totals when inputs change
            $(document).on('input', '.qty-input, .price-input', function() {
                updateLineAmount($(this).closest('tr'));
                updateTotals();
            });

            // Update totals when VAT or WTax changes
            $(document).on('change', '.vat-select, .wtax-select', function() {
                updateLineAmount($(this).closest('tr'));
                updateTotals();
            });

            // Handle order type change
            $('#order_type').on('change', function() {
                updateAllLineDropdowns();
            });

            // Customer change - populate delivery address from customer
            $('select[name="business_partner_id"]').on('change', function() {
                const opt = $(this).find('option:selected');
                if (opt.val()) {
                    $('#delivery_address').val(opt.data('address') || '');
                    $('#delivery_contact_person').val(opt.data('contact') || '');
                    $('#delivery_phone').val(opt.data('phone') || '');
                }
            });

            // Currency handling
            $('#currency_id').on('change', function() {
                updateExchangeRate();
            });

            // Date change handler
            $('input[name="date"]').on('change', function() {
                updateExchangeRate();
            });

            // Refresh rate button handler
            $('#refresh-rate-btn').on('click', function() {
                updateExchangeRate();
            });

            function updateExchangeRate() {
                const currencyId = $('#currency_id').val();
                const date = $('input[name="date"]').val();

                if (!currencyId || !date) {
                    return;
                }

                $.ajax({
                    url: '{{ route('sales-orders.api.exchange-rate') }}',
                    method: 'GET',
                    data: {
                        currency_id: currencyId,
                        date: date
                    },
                    success: function(response) {
                        if (response.rate) {
                            $('#exchange_rate').val(response.rate);
                        } else if (response.error) {
                            console.error('Exchange rate error:', response.error);
                            alert('Error getting exchange rate: ' + response.error);
                        }
                    },
                    error: function(xhr) {
                        console.error('Exchange rate request failed:', xhr);
                        alert('Failed to get exchange rate');
                    }
                });
            }

            // Company entity change handler - regenerate document number
            $('#company_entity_id').on('change', function() {
                updateDocumentNumber();
            });

            // Date change handler - regenerate document number (already handled for exchange rate, but also update doc number)
            $('input[name="date"]').on('change', function() {
                updateDocumentNumber();
            });

            function updateDocumentNumber() {
                const entityId = $('#company_entity_id').val();
                const date = $('input[name="date"]').val();

                if (!entityId || !date) {
                    return;
                }

                $.ajax({
                    url: '{{ route('sales-orders.api.document-number') }}',
                    method: 'GET',
                    data: {
                        company_entity_id: entityId,
                        date: date
                    },
                    success: function(response) {
                        if (response.document_number) {
                            $('input[name="order_no"]').val(response.document_number);
                        } else if (response.error) {
                            console.error('Document number error:', response.error);
                        }
                    },
                    error: function(xhr) {
                        console.error('Document number request failed:', xhr);
                    }
                });
            }

            // Handle item search button clicks
            $(document).on('click', '.item-search-btn', function() {
                const lineIdx = $(this).data('line-idx');
                const orderType = $(this).data('order-type');

                // Store current line index for item selection
                window.currentLineIdx = lineIdx;
                window.currentOrderType = orderType;

                // Show modal
                $('#itemSelectionModal').modal('show');

                // Load initial items
                loadItems();
            });

            // Handle prefill data if available
            if (window.prefill) {
                $tb.empty();
                i = 0;
                $('[name=date]').val(window.prefill.date);
                $('[name=business_partner_id]').val(window.prefill.business_partner_id);
                $('[name=order_type]').val(window.prefill.order_type || 'item');

                if (window.prefill.lines && window.prefill.lines.length > 0) {
                    window.prefill.lines.forEach(function(l) {
                        addLineRow(l);
                    });
                } else {
                    addLineRow();
                }

                // Initialize Select2 for prefilled data
                $('.select2bs4').select2({
                    theme: 'bootstrap4',
                    placeholder: 'Select an option',
                    allowClear: true
                });

                updateTotals();
            }

            function addLineRow(data = {}) {
                const lineIdx = i++;
                const tr = document.createElement('tr');
                const orderType = $('#order_type').val() || 'item';

                tr.innerHTML = `
                    <td>
                        <div class="input-group">
                            <select name="lines[${lineIdx}][item_id]" class="form-control form-control-sm item-select" required>
                                <option value="">-- select ${orderType === 'item' ? 'item' : 'account'} --</option>
                                ${getItemOptions(orderType, data.item_id)}
                            </select>
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
                        <input type="number" step="0.01" min="0" name="lines[${lineIdx}][unit_price]" 
                            class="form-control form-control-sm text-right price-input" value="${data.unit_price || 0}" required>
                    </td>
                    <td>
                        <select name="lines[${lineIdx}][vat_rate]" class="form-control form-control-sm vat-select">
                            <option value="0" ${data.vat_rate == 0 ? 'selected' : ''}>No</option>
                            <option value="11" ${data.vat_rate == 11 ? 'selected' : ''}>11%</option>
                            <option value="12" ${data.vat_rate == 12 ? 'selected' : ''}>12%</option>
                        </select>
                    </td>
                    <td>
                        <select name="lines[${lineIdx}][wtax_rate]" class="form-control form-control-sm wtax-select">
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

                // Initialize Select2BS4 for the newly added select elements
                $(tr).find('.select2bs4').select2({
                    theme: 'bootstrap4',
                    placeholder: 'Select an option',
                    allowClear: true
                });

                updateLineAmount(tr);
                updateTotals();
            }

            function getItemOptions(orderType, selectedId) {
                if (orderType === 'item') {
                    return window.inventoryItems.map(item =>
                        `<option value="${item.id}" ${selectedId == item.id ? 'selected' : ''}>${item.code} - ${item.name}</option>`
                    ).join('');
                } else {
                    return window.accounts.map(account =>
                        `<option value="${account.id}" ${selectedId == account.id ? 'selected' : ''}>${account.code} - ${account.name}</option>`
                    ).join('');
                }
            }

            function updateAllLineDropdowns() {
                const orderType = $('#order_type').val() || 'item';
                $('#lines tbody tr').each(function() {
                    const $select = $(this).find('.item-select');
                    const currentValue = $select.val();

                    $select.empty();
                    $select.append(
                        `<option value="">-- select ${orderType === 'item' ? 'item' : 'account'} --</option>`
                    );
                    $select.append(getItemOptions(orderType));

                    if (currentValue) {
                        $select.val(currentValue);
                    }

                    $select.trigger('change');
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
                const warehouseId = $('select[name="warehouse_id"]').val();
                const searchData = {
                    code: $('#searchCode').val(),
                    name: $('#searchName').val(),
                    category_id: $('#searchCategory').val(),
                    item_type: $('#searchType').val(),
                    per_page: 20,
                    page: page
                };
                
                // Add warehouse_id if selected
                if (warehouseId) {
                    searchData.warehouse_id = warehouseId;
                }

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
                    tbody.append('<tr><td colspan="10" class="text-center text-muted">No items found</td></tr>');
                    return;
                }

                // Apply stock filter if enabled
                const showOnlyInStock = $('#filterInStock').is(':checked');
                const showOnlyLowStock = $('#filterLowStock').is(':checked');
                
                let filteredItems = items;
                if (showOnlyInStock) {
                    filteredItems = filteredItems.filter(item => 
                        item.warehouse_stock && item.warehouse_stock.available_quantity > 0
                    );
                }
                if (showOnlyLowStock) {
                    filteredItems = filteredItems.filter(item => 
                        item.warehouse_stock && 
                        item.warehouse_stock.available_quantity > 0 && 
                        item.warehouse_stock.available_quantity <= (item.warehouse_stock.reorder_point || 0)
                    );
                }

                if (filteredItems.length === 0) {
                    tbody.append('<tr><td colspan="10" class="text-center text-muted">No items match the selected filters</td></tr>');
                    return;
                }

                filteredItems.forEach((item, index) => {
                    const stockInfo = getStockDisplay(item);
                    const row = `
                        <tr ${stockInfo.isOutOfStock ? 'class="table-danger"' : (stockInfo.isLowStock ? 'class="table-warning"' : '')}>
                            <td>${index + 1}</td>
                            <td><strong>${item.code}</strong></td>
                            <td>${item.name}</td>
                            <td>${item.category ? item.category.name : '-'}</td>
                            <td><span class="badge badge-${item.item_type === 'item' ? 'primary' : 'info'}">${item.item_type}</span></td>
                            <td>${item.unit_of_measure}</td>
                            <td class="text-right">${formatCurrency(item.purchase_price)}</td>
                            <td class="text-right">${formatCurrency(item.selling_price)}</td>
                            <td class="text-right">${stockInfo.html}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success select-item-btn" 
                                        data-item-id="${item.id}" 
                                        data-item-code="${item.code}" 
                                        data-item-name="${item.name}"
                                        data-item-price="${item.selling_price}"
                                        data-available-qty="${stockInfo.availableQty}"
                                        ${stockInfo.isOutOfStock && item.item_type === 'item' ? 'title="Out of stock"' : ''}>
                                    <i class="fas fa-check"></i> Select
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function getStockDisplay(item) {
                const warehouseId = $('select[name="warehouse_id"]').val();
                
                // For service items, don't show stock
                if (item.item_type === 'service') {
                    return {
                        html: '<span class="text-muted">-</span>',
                        availableQty: null,
                        isOutOfStock: false,
                        isLowStock: false
                    };
                }
                
                // If no warehouse selected or no warehouse stock data
                if (!warehouseId || !item.warehouse_stock) {
                    return {
                        html: '<span class="text-muted" title="Select warehouse to see availability">-</span>',
                        availableQty: null,
                        isOutOfStock: false,
                        isLowStock: false
                    };
                }
                
                const stock = item.warehouse_stock;
                const availableQty = stock.available_quantity || 0;
                const reorderPoint = stock.reorder_point || 0;
                const isOutOfStock = availableQty === 0;
                const isLowStock = availableQty > 0 && availableQty <= reorderPoint;
                
                let badgeClass = 'badge-success';
                let badgeText = 'In Stock';
                let icon = '<i class="fas fa-check-circle"></i>';
                
                if (isOutOfStock) {
                    badgeClass = 'badge-danger';
                    badgeText = 'Out of Stock';
                    icon = '<i class="fas fa-times-circle"></i>';
                } else if (isLowStock) {
                    badgeClass = 'badge-warning';
                    badgeText = 'Low Stock';
                    icon = '<i class="fas fa-exclamation-triangle"></i>';
                }
                
                const tooltip = `On Hand: ${(stock.quantity_on_hand || 0).toLocaleString('id-ID')} ${item.unit_of_measure || ''}\nReserved: ${(stock.reserved_quantity || 0).toLocaleString('id-ID')} ${item.unit_of_measure || ''}\nAvailable: ${availableQty.toLocaleString('id-ID')} ${item.unit_of_measure || ''}`;
                
                return {
                    html: `<span class="badge ${badgeClass}" title="${tooltip}">${icon} ${availableQty.toLocaleString('id-ID')} ${item.unit_of_measure || ''}</span>`,
                    availableQty: availableQty,
                    isOutOfStock: isOutOfStock,
                    isLowStock: isLowStock
                };
            }

            function updatePagination(pagination) {
                const paginationContainer = $('#itemsPagination');
                paginationContainer.empty();

                if (pagination.last_page <= 1) return;

                // Previous button
                if (pagination.current_page > 1) {
                    paginationContainer.append(`
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
                        </li>
                    `);
                }

                // Page numbers
                for (let i = 1; i <= pagination.last_page; i++) {
                    const activeClass = i === pagination.current_page ? 'active' : '';
                    paginationContainer.append(`
                        <li class="page-item ${activeClass}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }

                // Next button
                if (pagination.current_page < pagination.last_page) {
                    paginationContainer.append(`
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
                        </li>
                    `);
                }
            }

            function updateSearchResultsCount(total) {
                $('#searchResultsCount').text(`Found ${total} items`);
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount);
            }

            // Event handlers for modal
            $('#searchItems').on('click', function() {
                loadItems(1);
            });

            $('#clearSearch').on('click', function() {
                $('#searchCode, #searchName').val('');
                $('#searchCategory, #searchType').val('');
                $('#filterInStock, #filterLowStock').prop('checked', false);
                loadItems(1);
            });
            
            // Stock filter handlers
            $('#filterInStock, #filterLowStock').on('change', function() {
                const warehouseId = $('select[name="warehouse_id"]').val();
                if (warehouseId) {
                    loadItems(1);
                }
            });
            
            // Show/hide stock filters based on item type
            $('#searchType').on('change', function() {
                const itemType = $(this).val();
                if (itemType === 'item') {
                    $('#stockFilters').show();
                } else {
                    $('#stockFilters').hide();
                    $('#filterInStock, #filterLowStock').prop('checked', false);
                }
            });
            
            // Check initial item type
            if ($('#searchType').val() === 'item') {
                $('#stockFilters').show();
            }
            
            // Warehouse change handler - refresh items if modal is open
            $('select[name="warehouse_id"]').on('change', function() {
                if ($('#itemSelectionModal').hasClass('show')) {
                    loadItems(1);
                }
            });

            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                loadItems(page);
            });

            $(document).on('click', '.select-item-btn', function() {
                const itemId = $(this).data('item-id');
                const itemCode = $(this).data('item-code');
                const itemName = $(this).data('item-name');
                const itemPrice = $(this).data('item-price');
                const availableQty = $(this).data('available-qty');
                const orderType = window.currentOrderType || $('#order_type').val();

                // Check stock availability for item type orders
                if (orderType === 'item' && availableQty !== null && availableQty !== undefined) {
                    if (availableQty === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Out of Stock',
                            text: `Item "${itemCode} - ${itemName}" is currently out of stock in the selected warehouse.`,
                            confirmButtonText: 'Continue Anyway',
                            showCancelButton: true,
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                selectItem(itemId, itemCode, itemName, itemPrice, availableQty);
                            }
                        });
                        return;
                    }
                }

                selectItem(itemId, itemCode, itemName, itemPrice, availableQty);
            });
            
            function selectItem(itemId, itemCode, itemName, itemPrice, availableQty) {
                // Update the select dropdown
                const selectElement = $(`select[name="lines[${window.currentLineIdx}][item_id]"]`);
                selectElement.empty();
                selectElement.append(
                    `<option value="${itemId}" selected>${itemCode} - ${itemName}</option>`);

                // Update the price field
                const priceInput = selectElement.closest('tr').find('.price-input');
                priceInput.val(itemPrice);
                
                // Store available quantity as data attribute for validation
                const row = selectElement.closest('tr');
                row.data('available-qty', availableQty);
                
                // Add stock info badge if available
                if (availableQty !== null && availableQty !== undefined) {
                    const qtyInput = row.find('.qty-input');
                    const existingBadge = row.find('.stock-info-badge');
                    if (existingBadge.length) {
                        existingBadge.remove();
                    }
                    
                    let badgeClass = 'badge-success';
                    if (availableQty === 0) {
                        badgeClass = 'badge-danger';
                    } else if (availableQty <= 10) {
                        badgeClass = 'badge-warning';
                    }
                    
                    const badge = `<span class="badge ${badgeClass} stock-info-badge ml-2" title="Available: ${availableQty.toLocaleString('id-ID')}">
                        <i class="fas fa-box"></i> Available: ${availableQty.toLocaleString('id-ID')}
                    </span>`;
                    qtyInput.after(badge);
                }

                // Update line amount
                updateLineAmount(row);
                updateTotals();

                // Close modal
                $('#itemSelectionModal').modal('hide');
            }
            
            // Validate quantity against available stock when quantity changes
            $(document).on('input', '.qty-input', function() {
                const row = $(this).closest('tr');
                const availableQty = row.data('available-qty');
                const enteredQty = parseFloat($(this).val()) || 0;
                const orderType = $('#order_type').val();
                
                if (orderType === 'item' && availableQty !== null && availableQty !== undefined) {
                    const stockBadge = row.find('.stock-info-badge');
                    
                    if (enteredQty > availableQty) {
                        // Show warning
                        if (!row.find('.stock-warning').length) {
                            const warning = `<small class="text-danger stock-warning d-block mt-1">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Available stock: ${availableQty.toLocaleString('id-ID')}
                            </small>`;
                            $(this).after(warning);
                        }
                        stockBadge.removeClass('badge-success badge-warning').addClass('badge-danger');
                    } else {
                        // Remove warning
                        row.find('.stock-warning').remove();
                        if (availableQty > 0) {
                            stockBadge.removeClass('badge-danger').addClass(availableQty <= 10 ? 'badge-warning' : 'badge-success');
                        }
                    }
                }
            });
        });
    </script>
@endpush
