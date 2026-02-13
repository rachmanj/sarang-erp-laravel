@extends('layouts.main')

@section('title', isset($prefill['delivery_order_ids']) ? 'Create Sales Invoice from DO(s)' : 'Create Sales Invoice')

@section('title_page')
    @if (isset($prefill['delivery_order_ids']))
        Create Sales Invoice from Delivery Order(s)
    @else
        Create Sales Invoice
    @endif
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Sales Invoices</a></li>
    @if (isset($deliveryOrder))
        <li class="breadcrumb-item"><a href="{{ route('delivery-orders.show', $deliveryOrder) }}">DO {{ $deliveryOrder->do_number }}</a></li>
    @endif
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
                                <i class="fas fa-file-invoice-dollar mr-1"></i>
                                @if (isset($prefill['delivery_order_ids']))
                                    New Sales Invoice (from {{ count($prefill['delivery_order_ids']) }} DO(s))
                                @else
                                    New Sales Invoice
                                @endif
                            </h3>
                            <div class="float-right">
                                @if (isset($deliveryOrder))
                                    <a href="{{ route('delivery-orders.show', $deliveryOrder) }}" class="btn btn-sm btn-outline-secondary mr-1">
                                        <i class="fas fa-truck"></i> Back to Delivery Order
                                    </a>
                                @endif
                                <a href="{{ route('sales-invoices.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Sales Invoices
                                </a>
                            </div>
                        </div>
                        <form method="post" action="{{ route('sales-invoices.store') }}">
                            @csrf
                            <div class="card-body pb-1">
                                @isset($sales_order_id)
                                    <input type="hidden" name="sales_order_id" value="{{ $sales_order_id }}" />
                                @endisset
                                @if (isset($prefill['delivery_order_ids']) && !empty($prefill['delivery_order_ids']))
                                    @foreach ($prefill['delivery_order_ids'] as $doId)
                                        <input type="hidden" name="delivery_order_ids[]" value="{{ $doId }}" />
                                    @endforeach
                                @endif
                                @if (isset($salesQuotation))
                                    <input type="hidden" name="sales_quotation_id" value="{{ $salesQuotation->id }}" />
                                @endif

                                @if (!isset($prefill['delivery_order_ids']) && isset($invoicableDeliveryOrders))
                                    <div class="card card-info card-outline mb-3 {{ ($fromDo ?? false) ? '' : 'collapsed-card' }}" id="prefill-do-card">
                                        <div class="card-header py-2">
                                            <h3 class="card-title">
                                                <i class="fas fa-truck mr-1"></i>
                                                Prefill from Delivery Order
                                            </h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @if ($invoicableDeliveryOrders->isEmpty())
                                                <p class="text-muted mb-0">No delivered delivery orders available for invoicing.</p>
                                            @else
                                                <div class="row align-items-end">
                                                    <div class="col-md-8">
                                                        <label class="form-label">Select Delivery Order(s)</label>
                                                        <select id="delivery_order_select" class="form-control form-control-sm select2bs4" style="width: 100%;" multiple>
                                                            @foreach ($invoicableDeliveryOrders as $do)
                                                                <option value="{{ $do->id }}">
                                                                    {{ $do->do_number }} - {{ optional($do->customer)->name ?? 'N/A' }} ({{ $do->planned_delivery_date ? $do->planned_delivery_date->format('d M Y') : '' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple delivery orders.</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="button" id="btn-load-do" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-download mr-1"></i> Load
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

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
                                                        value="{{ old('date', $prefill['date'] ?? now()->toDateString()) }}"
                                                        class="form-control" required>
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
                                                            {{ old('company_entity_id', $prefill['company_entity_id'] ?? $defaultEntity->id) == $entity->id ? 'selected' : '' }}>
                                                            {{ $entity->name }} ({{ $entity->code }})
                                                        </option>
                                                    @endforeach
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
                                                            {{ old('business_partner_id', $prefill['business_partner_id'] ?? null) == $c->id ? 'selected' : '' }}>
                                                            {{ $c->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Terms (days)</label>
                                            <div class="col-sm-9">
                                                <input type="number" min="0" name="terms_days"
                                                    value="{{ old('terms_days', 30) }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Description</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="description"
                                                    value="{{ old('description', $prefill['description'] ?? '') }}"
                                                    class="form-control form-control-sm" placeholder="Invoice description">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Reference No</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                                                    </div>
                                                    <input type="text" name="reference_no"
                                                        value="{{ old('reference_no', $prefill['reference_no'] ?? '') }}"
                                                        class="form-control form-control-sm" placeholder="Customer reference number">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Due Date</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <div class="col-sm-9 offset-sm-3">
                                                <div class="form-check">
                                                    <input type="checkbox" name="is_opening_balance" id="is_opening_balance"
                                                        value="1" class="form-check-input"
                                                        {{ old('is_opening_balance') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_opening_balance">
                                                        Opening Balance Invoice
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">
                                                    <i class="fas fa-info-circle"></i> Check this for invoices recorded as
                                                    opening balance.
                                                    These invoices will post directly to AR and Revenue accounts (no AR
                                                    UnInvoice flow).
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-secondary card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Invoice Lines
                                        </h3>
                                        <button type="button" class="btn btn-xs btn-primary float-right"
                                            onclick="addLine()">
                                            <i class="fas fa-plus"></i> Add Line
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0" id="lines-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 22%">Revenue Account <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 12%">Item Code</th>
                                                        <th style="width: 20%">Item Name</th>
                                                        <th style="width: 10%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 12%">Unit Price <span
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 10%">VAT</th>
                                                        <th style="width: 10%">WTax</th>
                                                        <th style="width: 6%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lines">
                                                    @if (isset($prefill) && isset($prefill['lines']) && count($prefill['lines']) > 0)
                                                        @foreach ($prefill['lines'] as $index => $line)
                                                            <tr class="line-item">
                                                                <td>
                                                                    @if (!empty($line['delivery_order_line_id']))
                                                                        <input type="hidden" name="lines[{{ $index }}][delivery_order_line_id]" value="{{ $line['delivery_order_line_id'] }}">
                                                                    @endif
                                                                    @if (!empty($line['inventory_item_id']))
                                                                        <input type="hidden" name="lines[{{ $index }}][inventory_item_id]" value="{{ $line['inventory_item_id'] }}">
                                                                    @endif
                                                                    <input type="hidden" name="lines[{{ $index }}][description]" value="{{ $line['description'] ?? $line['item_name'] ?? '' }}">
                                                                    <input type="hidden" name="lines[{{ $index }}][item_code]" value="{{ $line['item_code'] ?? '' }}">
                                                                    <input type="hidden" name="lines[{{ $index }}][item_name]" value="{{ $line['item_name'] ?? '' }}">
                                                                    @if (!empty($line['has_inventory_item']) && !empty($line['account_id']))
                                                                        <input type="hidden" name="lines[{{ $index }}][account_id]" value="{{ $line['account_id'] }}">
                                                                        <input type="text" class="form-control form-control-sm" value="{{ $line['account_display'] ?? '' }}" readonly style="background-color: #e9ecef;" title="Auto-filled from inventory category">
                                                                    @else
                                                                        <select name="lines[{{ $index }}][account_id]"
                                                                            class="form-control form-control-sm select2bs4"
                                                                            required>
                                                                            @foreach ($accounts as $a)
                                                                                <option value="{{ $a->id }}"
                                                                                    {{ ($line['account_id'] ?? null) == $a->id ? 'selected' : '' }}>
                                                                                    {{ $a->code }} - {{ $a->name }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <input type="text"
                                                                        name="lines[{{ $index }}][item_code_display]"
                                                                        class="form-control form-control-sm"
                                                                        value="{{ $line['item_code'] ?? '' }}"
                                                                        readonly
                                                                        style="background-color: #e9ecef;">
                                                                </td>
                                                                <td>
                                                                    <input type="text"
                                                                        name="lines[{{ $index }}][item_name_display]"
                                                                        class="form-control form-control-sm"
                                                                        value="{{ $line['item_name'] ?? '' }}"
                                                                        readonly
                                                                        style="background-color: #e9ecef;">
                                                                </td>
                                                                <td>
                                                                    <input type="number" step="0.01" min="0.01"
                                                                        name="lines[{{ $index }}][qty]"
                                                                        class="form-control form-control-sm text-right qty-input"
                                                                        value="{{ $line['qty'] }}" required>
                                                                </td>
                                                                <td>
                                                                    <input type="number" step="0.01" min="0"
                                                                        name="lines[{{ $index }}][unit_price]"
                                                                        class="form-control form-control-sm text-right price-input"
                                                                        value="{{ $line['unit_price'] }}" required>
                                                                </td>
                                                                <td>
                                                                    <select name="lines[{{ $index }}][tax_code_id]"
                                                                        class="form-control form-control-sm vat-select">
                                                                        <option value="">No</option>
                                                                        @foreach ($vatTaxCodes ?? [] as $t)
                                                                            <option value="{{ $t->id }}" data-rate="{{ $t->rate }}"
                                                                                {{ isset($line['tax_code_id']) && $line['tax_code_id'] == $t->id ? 'selected' : '' }}>
                                                                                {{ (int)$t->rate }}%</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select name="lines[{{ $index }}][wtax_rate]"
                                                                        class="form-control form-control-sm wtax-select">
                                                                        <option value="0" {{ ($line['wtax_rate'] ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                                                        <option value="2" {{ ($line['wtax_rate'] ?? 0) == 2 ? 'selected' : '' }}>2%</option>
                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button"
                                                                        class="btn btn-xs btn-danger rm">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr class="line-item">
                                                            <td>
                                                                <input type="hidden" name="lines[0][description]" value="">
                                                                <input type="hidden" name="lines[0][item_code]" value="">
                                                                <input type="hidden" name="lines[0][item_name]" value="">
                                                                <select name="lines[0][account_id]"
                                                                    class="form-control form-control-sm select2bs4"
                                                                    required>
                                                                    @foreach ($accounts as $a)
                                                                        <option value="{{ $a->id }}">
                                                                            {{ $a->code }} - {{ $a->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="lines[0][item_code_display]"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Item Code"
                                                                    readonly
                                                                    style="background-color: #e9ecef;">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="lines[0][item_name_display]"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Item Name"
                                                                    readonly
                                                                    style="background-color: #e9ecef;">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0.01"
                                                                    name="lines[0][qty]"
                                                                    class="form-control form-control-sm text-right qty-input"
                                                                    value="1" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0"
                                                                    name="lines[0][unit_price]"
                                                                    class="form-control form-control-sm text-right price-input"
                                                                    value="0" required>
                                                            </td>
                                                            <td>
                                                                <select name="lines[0][tax_code_id]"
                                                                    class="form-control form-control-sm vat-select">
                                                                    <option value="">No</option>
                                                                    @foreach ($vatTaxCodes ?? [] as $t)
                                                                        <option value="{{ $t->id }}" data-rate="{{ $t->rate }}">{{ (int)$t->rate }}%</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select name="lines[0][wtax_rate]"
                                                                    class="form-control form-control-sm wtax-select">
                                                                    <option value="0">No</option>
                                                                    <option value="2">2%</option>
                                                                </select>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-xs btn-danger rm">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="4" class="text-right">Original Amount:</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="4" class="text-right">Total VAT:</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="4" class="text-right">Total WTax:</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="4" class="text-right">Amount Due:</th>
                                                        <th class="text-right" id="amount-due">0.00</th>
                                                        <th colspan="3"></th>
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
                                            <i class="fas fa-save mr-1"></i> Save Invoice
                                        </button>
                                        <a href="{{ route('sales-invoices.index') }}" class="btn btn-default">
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
        let idx = {{ isset($prefill) && isset($prefill['lines']) ? count($prefill['lines']) : 1 }};

        $(document).ready(function() {
            // Initialize Select2BS4 for all select elements
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            $('#btn-load-do').on('click', function() {
                const doIds = $('#delivery_order_select').val();
                if (doIds && doIds.length > 0) {
                    const params = new URLSearchParams();
                    doIds.forEach(function(id) {
                        params.append('delivery_order_id[]', id);
                    });
                    window.location.href = '{{ route("sales-invoices.create") }}?' + params.toString();
                } else {
                    toastr.warning('Please select at least one delivery order.');
                }
            });

            // Populate due_date from business partner TOP when business partner is selected
            $('select[name="business_partner_id"]').on('change', function() {
                const businessPartnerId = $(this).val();
                const invoiceDate = $('input[name="date"]').val();
                
                if (businessPartnerId && invoiceDate) {
                    $.ajax({
                        url: '{{ route("business_partners.payment_terms", ["businessPartner" => "ID_PLACEHOLDER"]) }}'.replace('ID_PLACEHOLDER', businessPartnerId),
                        method: 'GET',
                        success: function(response) {
                            if (response.success && response.payment_terms_days !== undefined) {
                                const termsDays = response.payment_terms_days;
                                
                                // Calculate due date: invoice date + payment terms days
                                const dateObj = new Date(invoiceDate);
                                dateObj.setDate(dateObj.getDate() + termsDays);
                                
                                // Format as YYYY-MM-DD
                                const dueDate = dateObj.toISOString().split('T')[0];
                                
                                // Only set if due_date is empty (to allow manual override)
                                if (!$('input[name="due_date"]').val()) {
                                    $('input[name="due_date"]').val(dueDate);
                                }
                                
                                // Also update terms_days field
                                $('input[name="terms_days"]').val(termsDays);
                            }
                        },
                        error: function() {
                            // Silently fail - user can manually enter due date
                        }
                    });
                }
            });

            // Update due_date when invoice date changes (if business partner is selected)
            $('input[name="date"]').on('change', function() {
                const businessPartnerId = $('select[name="business_partner_id"]').val();
                const invoiceDate = $(this).val();
                
                if (businessPartnerId && invoiceDate) {
                    $.ajax({
                        url: '{{ route("business_partners.payment_terms", ["businessPartner" => "ID_PLACEHOLDER"]) }}'.replace('ID_PLACEHOLDER', businessPartnerId),
                        method: 'GET',
                        success: function(response) {
                            if (response.success && response.payment_terms_days !== undefined) {
                                const termsDays = response.payment_terms_days;
                                
                                // Calculate due date: invoice date + payment terms days
                                const dateObj = new Date(invoiceDate);
                                dateObj.setDate(dateObj.getDate() + termsDays);
                                
                                // Format as YYYY-MM-DD
                                const dueDate = dateObj.toISOString().split('T')[0];
                                
                                // Only set if due_date is empty (to allow manual override)
                                if (!$('input[name="due_date"]').val()) {
                                    $('input[name="due_date"]').val(dueDate);
                                }
                                
                                // Also update terms_days field
                                $('input[name="terms_days"]').val(termsDays);
                            }
                        },
                        error: function() {
                            // Silently fail - user can manually enter due date
                        }
                    });
                }
            });

            // Remove line
            $(document).on('click', '.rm', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });

            // Update total when unit price, quantity, VAT or WTax changes
            $(document).on('input', '.qty-input, .price-input', function() {
                updateTotalAmount();
            });
            $(document).on('change', '.vat-select, .wtax-select', function() {
                updateTotalAmount();
            });

            updateTotalAmount();
        });

        function addLine() {
            const container = document.getElementById('lines');
            const row = document.createElement('tr');
            row.className = 'line-item';
            row.innerHTML = `
                <td>
                    <input type="hidden" name="lines[${idx}][description]" value="">
                    <input type="hidden" name="lines[${idx}][item_code]" value="">
                    <input type="hidden" name="lines[${idx}][item_name]" value="">
                    <select name="lines[${idx}][account_id]" class="form-control form-control-sm select2bs4" required>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="lines[${idx}][item_code_display]" class="form-control form-control-sm" placeholder="Item Code" readonly style="background-color: #e9ecef;">
                </td>
                <td>
                    <input type="text" name="lines[${idx}][item_name_display]" class="form-control form-control-sm" placeholder="Item Name" readonly style="background-color: #e9ecef;">
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="lines[${idx}][qty]" 
                        class="form-control form-control-sm text-right qty-input" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" 
                        class="form-control form-control-sm text-right price-input" value="0" required>
                </td>
                <td>
                    <select name="lines[${idx}][tax_code_id]" class="form-control form-control-sm vat-select">
                        <option value="">No</option>
                        @foreach ($vatTaxCodes ?? [] as $t)
                            <option value="{{ $t->id }}" data-rate="{{ $t->rate }}">{{ (int)$t->rate }}%</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="lines[${idx}][wtax_rate]" class="form-control form-control-sm wtax-select">
                        <option value="0">No</option>
                        <option value="2">2%</option>
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-danger rm">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            container.appendChild(row);

            // Initialize Select2BS4 for the newly added select elements
            $(row).find('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            updateTotalAmount();
            idx++;
        }

        function updateTotalAmount() {
            let originalTotal = 0;
            let totalVat = 0;
            let totalWtax = 0;

            $('#lines tr').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val() || 0);
                const price = parseFloat($(this).find('.price-input').val() || 0);
                const vatRate = parseFloat($(this).find('.vat-select option:selected').data('rate') || 0);
                const wtaxRate = parseFloat($(this).find('.wtax-select').val() || 0);

                const lineAmount = qty * price;
                const vatAmount = lineAmount * (vatRate / 100);
                const wtaxAmount = lineAmount * (wtaxRate / 100);

                originalTotal += lineAmount;
                totalVat += vatAmount;
                totalWtax += wtaxAmount;
            });

            const amountDue = originalTotal + totalVat - totalWtax;

            const fmt = (n) => n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#total-amount').text(fmt(originalTotal));
            $('#total-vat').text(fmt(totalVat));
            $('#total-wtax').text(fmt(totalWtax));
            $('#amount-due').text(fmt(amountDue));
        }
    </script>
@endpush
