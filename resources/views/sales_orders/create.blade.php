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
                                                <select name="customer_id" class="form-control form-control-sm select2bs4"
                                                    required>
                                                    <option value="">-- select customer --</option>
                                                    @foreach ($customers as $c)
                                                        <option value="{{ $c->id }}"
                                                            {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                                            {{ $c->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
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
                                                        <th style="width: 12%">Unit Price <span class="text-danger">*</span>
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

            // Handle prefill data if available
            if (window.prefill) {
                $tb.empty();
                i = 0;
                $('[name=date]').val(window.prefill.date);
                $('[name=customer_id]').val(window.prefill.customer_id);
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
                        <select name="lines[${lineIdx}][item_id]" class="form-control form-control-sm select2bs4 item-select" required>
                            <option value="">-- select ${orderType === 'item' ? 'item' : 'account'} --</option>
                            ${getItemOptions(orderType, data.item_id)}
                        </select>
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
        });
    </script>
@endpush
