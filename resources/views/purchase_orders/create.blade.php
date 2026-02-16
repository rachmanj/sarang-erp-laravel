@extends('layouts.main')

@section('title', 'Create Purchase Order')

@section('title_page')
    Create Purchase Order
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
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
                                <i class="fas fa-shopping-cart mr-1"></i>
                                New Purchase Order
                            </h3>
                            <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Purchase Orders
                            </a>
                        </div>
                        <form method="post" action="{{ route('purchase-orders.store') }}" id="po-form">
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
                                            <label class="col-sm-3 col-form-label">PO Number</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" id="order_no_preview" class="form-control bg-light" readonly
                                                        placeholder="Will be assigned on save">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary" id="preview-po-number" title="Preview next number (does not consume)">
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
                                            <label class="col-sm-3 col-form-label">Vendor <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id"
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

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Reference No</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="reference_no"
                                                    value="{{ old('reference_no') }}"
                                                    class="form-control form-control-sm" placeholder="Reference number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Expected Delivery</label>
                                            <div class="col-sm-9">
                                                <input type="date" name="expected_delivery_date"
                                                    value="{{ old('expected_delivery_date') }}"
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
                                                    placeholder="Purchase order description">{{ old('description') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Freight Cost</label>
                                            <div class="col-sm-8">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp</span>
                                                    </div>
                                                    <input type="number" step="0.01" min="0"
                                                        name="freight_cost" value="{{ old('freight_cost', 0) }}"
                                                        class="form-control form-control-sm text-right">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Handling Cost</label>
                                            <div class="col-sm-8">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp</span>
                                                    </div>
                                                    <input type="number" step="0.01" min="0"
                                                        name="handling_cost" value="{{ old('handling_cost', 0) }}"
                                                        class="form-control form-control-sm text-right">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Insurance Cost</label>
                                            <div class="col-sm-8">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp</span>
                                                    </div>
                                                    <input type="number" step="0.01" min="0"
                                                        name="insurance_cost" value="{{ old('insurance_cost', 0) }}"
                                                        class="form-control form-control-sm text-right">
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
                                                        <th style="width: 15%">Description</th>
                                                        <th style="width: 8%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 10%">Unit</th>
                                                        <th style="width: 10%">Unit Price <span
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
                                                        <th colspan="7" class="text-right">Original Amount:</th>
                                                        <th class="text-right" id="original-amount">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="7" class="text-right">VAT:</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="7" class="text-right">WTax:</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="7" class="text-right">Amount Due:</th>
                                                        <th class="text-right" id="amount-due">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Payment Terms</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="payment_terms"
                                                    value="{{ old('payment_terms') }}"
                                                    class="form-control form-control-sm" placeholder="e.g., Net 30, COD">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Delivery Method</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="delivery_method"
                                                    value="{{ old('delivery_method') }}"
                                                    class="form-control form-control-sm"
                                                    placeholder="e.g., Pickup, Delivery">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Notes</label>
                                            <div class="col-sm-10">
                                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Additional notes">{{ old('notes') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Terms & Conditions</label>
                                            <div class="col-sm-10">
                                                <textarea name="terms_conditions" class="form-control form-control-sm" rows="3"
                                                    placeholder="Terms and conditions">{{ old('terms_conditions') }}</textarea>
                                            </div>
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
                                        data-item-price="${item.purchase_price}">
                                    <i class="fas fa-check"></i>
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

                // Update the input field and hidden field
                const displayInput = $(`input[name="lines[${window.currentLineIdx}][item_display]"]`);
                const hiddenInput = $(`input[name="lines[${window.currentLineIdx}][item_id]"]`);

                displayInput.val(`${itemCode} - ${itemName}`);
                hiddenInput.val(itemId);

                // Update the price field
                const priceInput = displayInput.closest('tr').find('.price-input');
                priceInput.val(itemPrice);

                // Update line amount
                updateLineAmount(displayInput.closest('tr'));
                updateTotals();

                // Load units for selected item
                loadItemUnits(itemId, displayInput.closest('tr'));

                // Close modal
                $('#itemSelectionModal').modal('hide');
            });

            // Unit selection change handler
            $tb.on('change', '.unit-select', function() {
                const $row = $(this).closest('tr');
                const unitId = $(this).val();
                const itemId = $row.find('.item-id').val();
                const quantity = parseFloat($row.find('.qty-input').val()) || 1;

                if (unitId && itemId) {
                    showConversionPreview(itemId, unitId, quantity, $row);
                } else {
                    $row.find('.conversion-preview').text('');
                }
            });

            // Quantity change handler for conversion preview
            $tb.on('input', '.qty-input', function() {
                const $row = $(this).closest('tr');
                const unitId = $row.find('.unit-select').val();
                const itemId = $row.find('.item-id').val();
                const quantity = parseFloat($(this).val()) || 1;

                if (unitId && itemId) {
                    showConversionPreview(itemId, unitId, quantity, $row);
                }
            });
        });

        // Function to load units for an item
        function loadItemUnits(itemId, $row) {
            if (!itemId) return;

            const $unitSelect = $row.find('.unit-select');
            $unitSelect.empty().append('<option value="">Loading units...</option>');

            $.get('{{ route('purchase-orders.api.item-units') }}', {
                    item_id: itemId
                })
                .done(function(units) {
                    $unitSelect.empty().append('<option value="">Select Unit</option>');

                    units.forEach(function(unit) {
                        const selected = unit.is_base_unit ? 'selected' : '';
                        $unitSelect.append(
                            `<option value="${unit.id}" ${selected}>${unit.display_name}</option>`);
                    });

                    // Initialize Select2 for the new select
                    $unitSelect.select2({
                        placeholder: 'Select Unit',
                        allowClear: true,
                        width: '100%'
                    });
                })
                .fail(function() {
                    $unitSelect.empty().append('<option value="">Error loading units</option>');
                });
        }

        // Function to show conversion preview
        function showConversionPreview(itemId, unitId, quantity, $row) {
            $.get('{{ route('purchase-orders.api.conversion-preview') }}', {
                    item_id: itemId,
                    from_unit_id: unitId,
                    quantity: quantity
                })
                .done(function(response) {
                    const $preview = $row.find('.conversion-preview');
                    if (response.valid && response.preview) {
                        $preview.text(response.preview).show();
                    } else {
                        $preview.text('').hide();
                    }
                })
                .fail(function() {
                    $row.find('.conversion-preview').text('').hide();
                });
        }

        // Currency handling
        $(document).ready(function() {
            // Currency selection change handler
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
                    url: '{{ route('purchase-orders.api.exchange-rate') }}',
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

            // Initialize exchange rate on page load
            updateExchangeRate();

            // Company entity change handler - preview document number
            $('#company_entity_id').on('change', function() {
                updateDocumentNumber();
            });

            $('input[name="date"]').on('change', function() {
                updateDocumentNumber();
            });

            $('#preview-po-number').on('click', function() {
                updateDocumentNumber();
            });

            function updateDocumentNumber() {
                const entityId = $('#company_entity_id').val();
                const date = $('input[name="date"]').val();

                if (!entityId || !date) {
                    return;
                }

                $.ajax({
                    url: '{{ route('purchase-orders.api.document-number') }}',
                    method: 'GET',
                    data: {
                        company_entity_id: entityId,
                        date: date
                    },
                    success: function(response) {
                        if (response.document_number) {
                            $('#order_no_preview').val(response.document_number);
                        } else if (response.error) {
                            console.error('Document number error:', response.error);
                        }
                    },
                    error: function(xhr) {
                        console.error('Document number request failed:', xhr);
                    }
                });
            }

            updateDocumentNumber();
        });
    </script>
@endpush
