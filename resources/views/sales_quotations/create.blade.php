@extends('layouts.main')

@section('title', 'Create Sales Quotation')

@section('title_page')
    Create Sales Quotation
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-quotations.index') }}">Sales Quotations</a></li>
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
                                <i class="fas fa-file-invoice mr-1"></i>
                                New Sales Quotation
                            </h3>
                            <a href="{{ route('sales-quotations.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Quotations
                            </a>
                        </div>
                        <form method="post" action="{{ route('sales-quotations.store') }}" id="quotation-form">
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
                                            <label class="col-sm-3 col-form-label">Quotation No</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" name="quotation_no" value="{{ $quotationNo }}"
                                                        class="form-control bg-light" readonly>
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
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Valid Until <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-check"></i></span>
                                                    </div>
                                                    <input type="date" name="valid_until_date"
                                                        value="{{ old('valid_until_date', now()->addDays(30)->toDateString()) }}"
                                                        class="form-control" required>
                                                </div>
                                                <small class="form-text text-muted">Quotation expiration date</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Quotation Type <span
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
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Reference No</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                                                    class="form-control form-control-sm" placeholder="Customer reference">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
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
                                                            {{ old('business_partner_id') == $c->id ? 'selected' : '' }}>
                                                            {{ $c->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse</label>
                                            <div class="col-sm-9">
                                                <select name="warehouse_id"
                                                    class="form-control form-control-sm select2bs4">
                                                    <option value="">-- select warehouse (optional) --</option>
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
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Description</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="description"
                                                    value="{{ old('description') }}" class="form-control form-control-sm"
                                                    placeholder="Quotation description">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
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

                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Discount (%)</label>
                                            <div class="col-sm-9">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="discount_percentage" id="discount_percentage"
                                                    value="{{ old('discount_percentage', 0) }}"
                                                    class="form-control form-control-sm"
                                                    placeholder="Header discount percentage">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Discount Amount</label>
                                            <div class="col-sm-9">
                                                <input type="number" step="0.01" min="0"
                                                    name="discount_amount" id="discount_amount"
                                                    value="{{ old('discount_amount', 0) }}"
                                                    class="form-control form-control-sm text-right" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-secondary card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Quotation Lines
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
                                                        <th style="width: 15%">Item/Account <span
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 15%">Description</th>
                                                        <th style="width: 8%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 10%">Unit Price <span
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 6%">VAT</th>
                                                        <th style="width: 6%">WTax</th>
                                                        <th style="width: 8%">Disc %</th>
                                                        <th style="width: 8%">Disc Amt</th>
                                                        <th style="width: 10%">Amount</th>
                                                        <th style="width: 10%">Net Amount</th>
                                                        <th style="width: 8%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Original Amount:</th>
                                                        <th class="text-right" id="original-amount">0.00</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th colspan="2"></th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th class="text-right" id="total-net-amount">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Line Discounts:</th>
                                                        <th colspan="4" class="text-right" id="total-line-discount">
                                                            0.00</th>
                                                        <th colspan="2"></th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Header Discount:</th>
                                                        <th colspan="4" class="text-right" id="total-header-discount">
                                                            0.00</th>
                                                        <th colspan="2"></th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Total Discount:</th>
                                                        <th colspan="4" class="text-right" id="total-discount">0.00
                                                        </th>
                                                        <th colspan="2"></th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Net Amount:</th>
                                                        <th colspan="4" class="text-right" id="net-amount">0.00</th>
                                                        <th colspan="2"></th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Terms & Conditions</label>
                                            <textarea name="terms_conditions" class="form-control form-control-sm" rows="3"
                                                placeholder="Enter terms and conditions...">{{ old('terms_conditions') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-1"></i> Save Quotation
                                        </button>
                                        <a href="{{ route('sales-quotations.index') }}" class="btn btn-default">
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
            let updatingDiscount = false;

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

            // Handle line-level discount percentage change
            $(document).on('input', '.line-discount-percentage', function() {
                const row = $(this).closest('tr');
                const discountPercentage = parseFloat($(this).val() || 0);
                const lineAmount = parseFloat(row.find('.line-amount').text().replace(/\./g, '').replace(
                    ',', '.') || 0);
                const discountAmount = (lineAmount * discountPercentage) / 100;
                row.find('.line-discount-amount').val(discountAmount.toFixed(2));
                updateLineAmount(row);
                updateTotals();
            });

            // Handle line-level discount amount change
            $(document).on('input', '.line-discount-amount', function() {
                const row = $(this).closest('tr');
                const discountAmount = parseFloat($(this).val() || 0);
                const lineAmount = parseFloat(row.find('.line-amount').text().replace(/\./g, '').replace(
                    ',', '.') || 0);
                const discountPercentage = lineAmount > 0 ? (discountAmount / lineAmount) * 100 : 0;
                row.find('.line-discount-percentage').val(discountPercentage.toFixed(2));
                updateLineAmount(row);
                updateTotals();
            });

            // Handle discount percentage change
            $('#discount_percentage').on('input', function() {
                if (updatingDiscount) return;
                updatingDiscount = true;
                const discountPercentage = parseFloat($(this).val() || 0);
                const totalAmount = parseFloat($('#total-amount').text().replace(/\./g, '').replace(',',
                    '.') || 0);
                const discountAmount = (totalAmount * discountPercentage) / 100;
                $('#discount_amount').val(discountAmount.toFixed(2));
                updateTotals();
                updatingDiscount = false;
            });

            // Handle discount amount change
            $('#discount_amount').on('input', function() {
                if (updatingDiscount) return;
                updatingDiscount = true;
                const discountAmount = parseFloat($(this).val() || 0);
                const totalAmount = parseFloat($('#total-amount').text().replace(/\./g, '').replace(',',
                    '.') || 0);
                const discountPercentage = totalAmount > 0 ? (discountAmount / totalAmount) * 100 : 0;
                $('#discount_percentage').val(discountPercentage.toFixed(2));
                updateTotals();
                updatingDiscount = false;
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

            // Currency handling
            $('#currency_id').on('change', function() {
                updateExchangeRate();
            });

            // Date change handler
            $('input[name="date"]').on('change', function() {
                updateExchangeRate();
                updateDocumentNumber();
            });

            // Valid until date validation
            $('input[name="valid_until_date"]').on('change', function() {
                const date = $('input[name="date"]').val();
                const validUntil = $(this).val();
                if (date && validUntil && validUntil < date) {
                    alert('Valid until date must be on or after quotation date');
                    $(this).val(date);
                }
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
                    url: '{{ route('sales-quotations.api.exchange-rate') }}',
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
                        }
                    },
                    error: function(xhr) {
                        console.error('Exchange rate request failed:', xhr);
                    }
                });
            }

            // Company entity change handler - regenerate document number
            $('#company_entity_id').on('change', function() {
                updateDocumentNumber();
            });

            function updateDocumentNumber() {
                const entityId = $('#company_entity_id').val();
                const date = $('input[name="date"]').val();

                if (!entityId || !date) {
                    return;
                }

                $.ajax({
                    url: '{{ route('sales-quotations.api.document-number') }}',
                    method: 'GET',
                    data: {
                        company_entity_id: entityId,
                        date: date
                    },
                    success: function(response) {
                        if (response.document_number) {
                            $('input[name="quotation_no"]').val(response.document_number);
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

                window.currentLineIdx = lineIdx;
                window.currentOrderType = orderType;

                $('#itemSelectionModal').modal('show');
                loadItems();
            });

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
                    <td>
                        <input type="number" step="0.01" min="0" max="100" 
                            name="lines[${lineIdx}][discount_percentage]" 
                            class="form-control form-control-sm text-right line-discount-percentage" 
                            value="${data.discount_percentage || 0}" placeholder="0">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" 
                            name="lines[${lineIdx}][discount_amount]" 
                            class="form-control form-control-sm text-right line-discount-amount" 
                            value="${data.discount_amount || 0}" placeholder="0.00">
                    </td>
                    <td class="text-right">
                        <span class="line-amount">0.00</span>
                    </td>
                    <td class="text-right">
                        <span class="line-net-amount">0.00</span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-danger rm">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;

                $tb.append(tr);

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

                // Calculate line-level discount
                let lineDiscountAmount = parseFloat($(row).find('.line-discount-amount').val() || 0);
                const lineDiscountPercentage = parseFloat($(row).find('.line-discount-percentage').val() || 0);

                if (lineDiscountPercentage > 0 && lineDiscountAmount == 0) {
                    lineDiscountAmount = (lineAmount * lineDiscountPercentage) / 100;
                    $(row).find('.line-discount-amount').val(lineDiscountAmount.toFixed(2));
                } else if (lineDiscountAmount > 0 && lineDiscountPercentage == 0) {
                    const calculatedPercentage = lineAmount > 0 ? (lineDiscountAmount / lineAmount) * 100 : 0;
                    $(row).find('.line-discount-percentage').val(calculatedPercentage.toFixed(2));
                }

                const lineNetAmount = lineAmount - lineDiscountAmount;

                $(row).find('.line-amount').text(lineAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $(row).find('.line-net-amount').text(lineNetAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }

            function updateTotals() {
                let originalTotal = 0;
                let totalVat = 0;
                let totalWtax = 0;
                let totalAmount = 0;
                let totalLineDiscountAmount = 0;

                $('#lines tbody tr').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val() || 0);
                    const price = parseFloat($(this).find('.price-input').val() || 0);
                    const vatRate = parseFloat($(this).find('.vat-select').val() || 0);
                    const wtaxRate = parseFloat($(this).find('.wtax-select').val() || 0);

                    const originalAmount = qty * price;
                    const vatAmount = originalAmount * (vatRate / 100);
                    const wtaxAmount = originalAmount * (wtaxRate / 100);
                    const lineAmount = originalAmount + vatAmount - wtaxAmount;

                    // Get line-level discount
                    const lineDiscountAmount = parseFloat($(this).find('.line-discount-amount').val() || 0);

                    originalTotal += originalAmount;
                    totalVat += vatAmount;
                    totalWtax += wtaxAmount;
                    totalAmount += lineAmount; // Sum original amounts (before discounts)
                    totalLineDiscountAmount += lineDiscountAmount;
                });

                // Calculate header-level discount
                const headerDiscountPercentage = parseFloat($('#discount_percentage').val() || 0);
                let headerDiscountAmount = parseFloat($('#discount_amount').val() || 0);

                if (headerDiscountPercentage > 0 && headerDiscountAmount == 0) {
                    headerDiscountAmount = (totalAmount * headerDiscountPercentage) / 100;
                    updatingDiscount = true;
                    $('#discount_amount').val(headerDiscountAmount.toFixed(2));
                    updatingDiscount = false;
                } else if (headerDiscountAmount > 0 && headerDiscountPercentage == 0) {
                    const calculatedPercentage = totalAmount > 0 ? (headerDiscountAmount / totalAmount) * 100 : 0;
                    updatingDiscount = true;
                    $('#discount_percentage').val(calculatedPercentage.toFixed(2));
                    updatingDiscount = false;
                }

                // Total discount = line discounts + header discount
                const totalDiscountAmount = totalLineDiscountAmount + headerDiscountAmount;

                // Calculate net amount
                const netAmount = totalAmount - totalDiscountAmount;
                const totalNetAmount = totalAmount - totalLineDiscountAmount; // Amount after line discounts only

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

                $('#total-net-amount').text(totalNetAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#total-line-discount').text(totalLineDiscountAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#total-header-discount').text(headerDiscountAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#total-discount').text(totalDiscountAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                $('#net-amount').text(netAmount.toLocaleString('id-ID', {
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
                    tbody.append('<tr><td colspan="9" class="text-center text-muted">No items found</td></tr>');
                    return;
                }

                items.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${item.code}</strong></td>
                            <td>${item.name}</td>
                            <td>${item.category ? item.category.name : '-'}</td>
                            <td><span class="badge badge-${item.item_type === 'item' ? 'primary' : 'info'}">${item.item_type}</span></td>
                            <td>${item.unit_of_measure}</td>
                            <td class="text-right">${formatCurrency(item.purchase_price)}</td>
                            <td class="text-right">${formatCurrency(item.selling_price)}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success select-item-btn" 
                                        data-item-id="${item.id}" 
                                        data-item-code="${item.code}" 
                                        data-item-name="${item.name}"
                                        data-item-price="${item.selling_price}">
                                    <i class="fas fa-check"></i> Select
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function updatePagination(pagination) {
                const paginationContainer = $('#itemsPagination');
                paginationContainer.empty();

                if (pagination.last_page <= 1) return;

                if (pagination.current_page > 1) {
                    paginationContainer.append(`
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
                        </li>
                    `);
                }

                for (let i = 1; i <= pagination.last_page; i++) {
                    const activeClass = i === pagination.current_page ? 'active' : '';
                    paginationContainer.append(`
                        <li class="page-item ${activeClass}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }

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

            $('#searchItems').on('click', function() {
                loadItems(1);
            });

            $('#clearSearch').on('click', function() {
                $('#searchCode, #searchName').val('');
                $('#searchCategory, #searchType').val('');
                loadItems(1);
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

                const selectElement = $(`select[name="lines[${window.currentLineIdx}][item_id]"]`);
                selectElement.empty();
                selectElement.append(
                    `<option value="${itemId}" selected>${itemCode} - ${itemName}</option>`);

                const priceInput = selectElement.closest('tr').find('.price-input');
                priceInput.val(itemPrice);

                updateLineAmount(selectElement.closest('tr'));
                updateTotals();

                $('#itemSelectionModal').modal('hide');
            });
        });
    </script>
@endpush
