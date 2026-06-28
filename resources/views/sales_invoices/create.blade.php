@extends('layouts.main')

@section('title', isset($prefill['delivery_order_ids']) ? 'Create Sales Invoice from DO(s)' : (($directSale ?? false) ? 'Create Direct Sale' : 'Create Sales Invoice'))

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
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Invoice No</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" id="invoice_no_preview" class="form-control bg-light" readonly
                                                        placeholder="Will be assigned on save">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary" id="preview-invoice-number" title="Preview next number (does not consume)">
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
                                                <select name="business_partner_id" id="business_partner_id"
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
                                            <label class="col-sm-3 col-form-label">Customer's Project</label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_project_id" id="business_partner_project_id"
                                                    class="form-control form-control-sm select2bs4">
                                                    <option value="">-- select project (optional) --</option>
                                                </select>
                                                <small class="form-text text-muted">Select after choosing customer</small>
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
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Discount (%)</label>
                                            <div class="col-sm-8">
                                                <input type="number" step="0.01" min="0" max="100" name="discount_percentage"
                                                    id="discount_percentage" value="{{ old('discount_percentage', $prefill['discount_percentage'] ?? 0) }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Discount Amount</label>
                                            <div class="col-sm-8">
                                                <input type="number" step="0.01" min="0" name="discount_amount"
                                                    id="discount_amount" value="{{ old('discount_amount', $prefill['discount_amount'] ?? 0) }}"
                                                    class="form-control form-control-sm text-right">
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

                                @if (!isset($prefill['delivery_order_ids']))
                                    <div class="card card-warning card-outline mb-3" id="direct-sale-card">
                                        <div class="card-header py-2">
                                            <h3 class="card-title">
                                                <i class="fas fa-cash-register mr-1"></i>
                                                Direct Sale
                                            </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" name="is_direct_sale" id="is_direct_sale" value="1"
                                                    class="form-check-input"
                                                    {{ old('is_direct_sale', $directSale ?? false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_direct_sale">
                                                    Direct sale (bypass Sales Order / Delivery Order; issue stock on post)
                                                </label>
                                            </div>
                                            <div id="direct-sale-options" style="{{ old('is_direct_sale', $directSale ?? false) ? '' : 'display:none;' }}">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-2">
                                                            <label for="payment_method">Payment</label>
                                                            <select name="payment_method" id="payment_method" class="form-control form-control-sm">
                                                                <option value="credit" {{ old('payment_method', 'credit') === 'credit' ? 'selected' : '' }}>Credit (AR — pay later)</option>
                                                                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash (paid now)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4" id="cash_account_field" style="{{ old('payment_method') === 'cash' ? '' : 'display:none;' }}">
                                                        <div class="form-group mb-2">
                                                            <label for="cash_account_id">Cash / Bank Account</label>
                                                            <select name="cash_account_id" id="cash_account_id" class="form-control form-control-sm select2bs4">
                                                                <option value="">Select account</option>
                                                                @foreach ($cashAccounts ?? [] as $cashAccount)
                                                                    <option value="{{ $cashAccount->id }}" {{ (string) old('cash_account_id') === (string) $cashAccount->id ? 'selected' : '' }}>
                                                                        {{ $cashAccount->code }} - {{ $cashAccount->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    Direct sale lines require an inventory item. Stock is reduced when the invoice is posted.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2" id="opening-balance-row">
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
                                                        <th style="width: 22%; display:none;" class="direct-sale-col">Item <span class="text-danger">*</span></th>
                                                        <th style="width: 22%" class="normal-revenue-col">Revenue Account <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 12%">Item Code</th>
                                                        <th style="width: 20%">Item Name</th>
                                                        <th style="width: 10%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 12%">Unit Price <span
                                                                class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 10%">VAT</th>
                                                        <th style="width: 10%">WTax</th>
                                                        <th style="width: 7%">Disc %</th>
                                                        <th style="width: 7%">Disc Amt</th>
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
                                                                    @if (!empty($line['part_number_id']))
                                                                        <input type="hidden" name="lines[{{ $index }}][part_number_id]" value="{{ $line['part_number_id'] }}">
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
                                                                <td>
                                                                    <input type="number" step="0.01" min="0" max="100"
                                                                        name="lines[{{ $index }}][discount_percentage]"
                                                                        class="form-control form-control-sm text-right line-discount-percentage"
                                                                        value="{{ $line['discount_percentage'] ?? old('lines.'.$index.'.discount_percentage') ?? '' }}"
                                                                        placeholder="%">
                                                                </td>
                                                                <td>
                                                                    <input type="number" step="0.01" min="0"
                                                                        name="lines[{{ $index }}][discount_amount]"
                                                                        class="form-control form-control-sm text-right line-discount-amount"
                                                                        value="{{ $line['discount_amount'] ?? old('lines.'.$index.'.discount_amount', 0) }}">
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
                                                            <td class="direct-sale-item-cell" style="display:none;">
                                                                <input type="hidden" name="lines[0][inventory_item_id]" value="">
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control form-control-sm direct-sale-item-display" readonly placeholder="Select item">
                                                                    <div class="input-group-append">
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm item-search-btn" data-line-idx="0">
                                                                            <i class="fas fa-search"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" name="lines[0][account_id]" value="{{ $accounts->first()->id ?? '' }}">
                                                            </td>
                                                            <td class="normal-revenue-cell">
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
                                                            <td>
                                                                <input type="number" step="0.01" min="0" max="100"
                                                                    name="lines[0][discount_percentage]"
                                                                    class="form-control form-control-sm text-right line-discount-percentage"
                                                                    value="{{ old('lines.0.discount_percentage') }}" placeholder="%">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0"
                                                                    name="lines[0][discount_amount]"
                                                                    class="form-control form-control-sm text-right line-discount-amount"
                                                                    value="{{ old('lines.0.discount_amount', 0) }}">
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
                                                        <th colspan="4" class="text-right">Original DPP:</th>
                                                        <th class="text-right" id="original-dpp">0.00</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th class="text-right" id="total-line-discount">0.00</th>
                                                        <th colspan="2"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="4" class="text-right">Line amounts (incl. tax):</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th colspan="5"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="7" class="text-right">Header discount:</th>
                                                        <th class="text-right" id="total-header-discount" colspan="2">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="7" class="text-right">Amount due:</th>
                                                        <th class="text-right" id="amount-due" colspan="2">0.00</th>
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

    @include('components.item-selection-modal')
@endsection

@push('scripts')
    <script>
        let isDirectSaleMode = {{ old('is_direct_sale', $directSale ?? false) ? 'true' : 'false' }};
        const revenueAccountOptions = `@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>@endforeach`;
        let idx = {{ isset($prefill) && isset($prefill['lines']) ? count($prefill['lines']) : 1 }};
        let siUpdatingDiscount = false;

        function resolveLineDppDiscountJs(qty, price, discountPct, discountAmt) {
            const grossDpp = Math.round(qty * price * 100) / 100;
            const pct = parseFloat(discountPct) || 0;
            const amt = parseFloat(discountAmt) || 0;
            if (pct > 0) {
                let disc = Math.round(grossDpp * (pct / 100) * 100) / 100;
                if (disc > grossDpp) disc = grossDpp;
                const pctOut = grossDpp > 0 ? Math.round((disc / grossDpp) * 10000) / 100 : 0;
                return { disc, pctOut, grossDpp };
            }
            if (amt > 0) {
                const disc = Math.round(Math.min(amt, grossDpp) * 100) / 100;
                const pctOut = grossDpp > 0 ? Math.round((disc / grossDpp) * 10000) / 100 : 0;
                return { disc, pctOut, grossDpp };
            }
            return { disc: 0, pctOut: 0, grossDpp };
        }

        function parseNumericIdText(t) {
            return parseFloat(String(t || '0').replace(/\./g, '').replace(',', '.')) || 0;
        }

        $(document).ready(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            function toggleDirectSaleUi() {
                isDirectSaleMode = $('#is_direct_sale').is(':checked');
                $('#direct-sale-options').toggle(isDirectSaleMode);
                $('#opening-balance-row').toggle(!isDirectSaleMode);
                $('#prefill-do-card').toggle(!isDirectSaleMode);
                $('.direct-sale-col').toggle(isDirectSaleMode);
                $('.normal-revenue-col').toggle(!isDirectSaleMode);
                $('.direct-sale-item-cell').toggle(isDirectSaleMode);
                $('.normal-revenue-cell').toggle(!isDirectSaleMode);

                if (isDirectSaleMode) {
                    $('#is_opening_balance').prop('checked', false);
                }

                const paymentMethod = $('#payment_method').val();
                $('#cash_account_field').toggle(isDirectSaleMode && paymentMethod === 'cash');
            }

            $('#is_direct_sale').on('change', toggleDirectSaleUi);
            $('#payment_method').on('change', function() {
                $('#cash_account_field').toggle(isDirectSaleMode && $(this).val() === 'cash');
            });
            toggleDirectSaleUi();

            $(document).on('click', '.item-search-btn', function() {
                window.currentLineIdx = $(this).data('line-idx');
                $('#itemSelectionModal').modal('show');
                loadDirectSaleItems();
            });

            function loadDirectSaleItems(page = 1) {
                $.ajax({
                    url: '{{ route('inventory.search') }}',
                    method: 'GET',
                    data: {
                        code: $('#searchCode').val(),
                        name: $('#searchName').val(),
                        category_id: $('#searchCategory').val(),
                        item_type: $('#searchType').val() || 'item',
                        page: page,
                        per_page: 20,
                    },
                    success: function(response) {
                        const tbody = $('#itemsTable tbody');
                        tbody.empty();
                        (response.items || []).forEach(function(item, index) {
                            const price = item.selling_price || 0;
                            const stock = item.available_quantity ?? item.current_stock ?? 0;
                            tbody.append(`
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${item.code}</td>
                                    <td>${item.name}</td>
                                    <td>${item.category ? item.category.name : '-'}</td>
                                    <td>${item.item_type}</td>
                                    <td>${item.unit_of_measure || '-'}</td>
                                    <td>${price}</td>
                                    <td>${price}</td>
                                    <td>${stock}</td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-primary select-item-btn"
                                            data-item-id="${item.id}"
                                            data-item-code="${item.code}"
                                            data-item-name="${item.name}"
                                            data-item-price="${price}">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                });
            }

            $('#searchItems').on('click', function() { loadDirectSaleItems(1); });
            $('#clearSearch').on('click', function() {
                $('#searchCode, #searchName').val('');
                $('#searchCategory, #searchType').val('');
                loadDirectSaleItems(1);
            });

            $(document).on('click', '.select-item-btn', function() {
                const lineIdx = window.currentLineIdx;
                const itemId = $(this).data('item-id');
                const itemCode = $(this).data('item-code');
                const itemName = $(this).data('item-name');
                const itemPrice = $(this).data('item-price');
                const row = $(`input[name="lines[${lineIdx}][inventory_item_id]"]`).closest('tr');

                row.find(`input[name="lines[${lineIdx}][inventory_item_id]"]`).val(itemId);
                row.find(`input[name="lines[${lineIdx}][item_code]"]`).val(itemCode);
                row.find(`input[name="lines[${lineIdx}][item_name]"]`).val(itemName);
                row.find(`input[name="lines[${lineIdx}][description]"]`).val(itemName);
                row.find('.direct-sale-item-display').val(`${itemCode} - ${itemName}`);
                row.find(`input[name="lines[${lineIdx}][item_code_display]"]`).val(itemCode);
                row.find(`input[name="lines[${lineIdx}][item_name_display]"]`).val(itemName);
                row.find('.price-input').val(itemPrice);
                updateTotalAmount();
                $('#itemSelectionModal').modal('hide');
            });

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
                    url: '{{ route('sales-invoices.api.document-number') }}',
                    method: 'GET',
                    data: { company_entity_id: entityId, date: date },
                    success: function(response) {
                        if (response.document_number) {
                            $('#invoice_no_preview').val(response.document_number);
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
            $('#preview-invoice-number').on('click', updateDocumentNumber);
            updateDocumentNumber();

            function loadCustomerProjects(bpId, selectedId) {
                const $select = $('#business_partner_project_id');
                $select.empty().append('<option value="">-- select project (optional) --</option>');
                if (!bpId) return;
                $.get("{{ route('business_partners.projects.list') }}", { business_partner_id: bpId }, function(data) {
                    data.forEach(function(p) {
                        $select.append($('<option>', { value: p.id, text: p.text }));
                    });
                    $select.val(selectedId || "{{ old('business_partner_project_id', $prefill['business_partner_project_id'] ?? '') }}").trigger('change');
                });
            }
            $('select[name="business_partner_id"]').on('change', function() {
                loadCustomerProjects($(this).val());
            });
            @if (isset($prefill['business_partner_id']) && $prefill['business_partner_id'])
            loadCustomerProjects("{{ $prefill['business_partner_id'] }}", "{{ old('business_partner_project_id', $prefill['business_partner_project_id'] ?? '') }}");
            @elseif (old('business_partner_id'))
            loadCustomerProjects("{{ old('business_partner_id') }}", "{{ old('business_partner_project_id') }}");
            @endif

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

            $(document).on('input', '.qty-input, .price-input', function() {
                updateTotalAmount();
            });
            $(document).on('input', '.line-discount-percentage', function() {
                const row = $(this).closest('tr');
                const qty = parseFloat(row.find('.qty-input').val() || 0);
                const price = parseFloat(row.find('.price-input').val() || 0);
                const pct = parseFloat($(this).val() || 0);
                const { disc, pctOut } = resolveLineDppDiscountJs(qty, price, pct, 0);
                row.find('.line-discount-amount').val(disc.toFixed(2));
                row.find('.line-discount-percentage').val(pctOut.toFixed(2));
                updateTotalAmount();
            });
            $(document).on('input', '.line-discount-amount', function() {
                const row = $(this).closest('tr');
                const qty = parseFloat(row.find('.qty-input').val() || 0);
                const price = parseFloat(row.find('.price-input').val() || 0);
                const amt = parseFloat($(this).val() || 0);
                const { disc, pctOut } = resolveLineDppDiscountJs(qty, price, 0, amt);
                row.find('.line-discount-percentage').val(pctOut.toFixed(2));
                row.find('.line-discount-amount').val(disc.toFixed(2));
                updateTotalAmount();
            });
            $(document).on('change', '.vat-select, .wtax-select', function() {
                updateTotalAmount();
            });

            $('#discount_percentage').on('input', function() {
                if (siUpdatingDiscount) return;
                siUpdatingDiscount = true;
                const pct = parseFloat($(this).val() || 0);
                const lineSum = parseNumericIdText($('#total-amount').text());
                const disc = lineSum > 0 ? Math.round(lineSum * (pct / 100) * 100) / 100 : 0;
                $('#discount_amount').val(disc.toFixed(2));
                updateTotalAmount();
                siUpdatingDiscount = false;
            });

            $('#discount_amount').on('input', function() {
                if (siUpdatingDiscount) return;
                siUpdatingDiscount = true;
                const amt = parseFloat($(this).val() || 0);
                const lineSum = parseNumericIdText($('#total-amount').text());
                const pct = lineSum > 0 ? Math.round((amt / lineSum) * 10000) / 100 : 0;
                $('#discount_percentage').val(pct.toFixed(2));
                updateTotalAmount();
                siUpdatingDiscount = false;
            });

            updateTotalAmount();
        });

        function directSaleItemCellHtml(lineIdx) {
            return `
                <td class="direct-sale-item-cell" style="${isDirectSaleMode ? '' : 'display:none;'}">
                    <input type="hidden" name="lines[${lineIdx}][inventory_item_id]" value="">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm direct-sale-item-display" readonly placeholder="Select item">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm item-search-btn" data-line-idx="${lineIdx}">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="lines[${lineIdx}][account_id]" value="{{ $accounts->first()->id ?? '' }}">
                </td>
                <td class="normal-revenue-cell" style="${isDirectSaleMode ? 'display:none;' : ''}">
                    <input type="hidden" name="lines[${lineIdx}][description]" value="">
                    <input type="hidden" name="lines[${lineIdx}][item_code]" value="">
                    <input type="hidden" name="lines[${lineIdx}][item_name]" value="">
                    <select name="lines[${lineIdx}][account_id]" class="form-control form-control-sm select2bs4" required>
                        ${revenueAccountOptions}
                    </select>
                </td>
            `;
        }

        function addLine() {
            const container = document.getElementById('lines');
            const row = document.createElement('tr');
            row.className = 'line-item';
            row.innerHTML = `
                ${directSaleItemCellHtml(idx)}
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
                <td>
                    <input type="number" step="0.01" min="0" max="100" name="lines[${idx}][discount_percentage]"
                        class="form-control form-control-sm text-right line-discount-percentage" value="" placeholder="%">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="lines[${idx}][discount_amount]"
                        class="form-control form-control-sm text-right line-discount-amount" value="0">
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
            let totalLineDppDiscount = 0;
            let lineAmountSum = 0;

            $('#lines tr').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val() || 0);
                const price = parseFloat($(this).find('.price-input').val() || 0);
                const vatRate = parseFloat($(this).find('.vat-select option:selected').data('rate') || 0);
                const wtaxRate = parseFloat($(this).find('.wtax-select').val() || 0);
                const pctIn = parseFloat($(this).find('.line-discount-percentage').val() || 0);
                const amtIn = parseFloat($(this).find('.line-discount-amount').val() || 0);

                const { disc, grossDpp } = resolveLineDppDiscountJs(qty, price, pctIn, amtIn);
                totalLineDppDiscount += disc;
                const dppNet = Math.max(0, grossDpp - disc);
                const vatAmount = dppNet * (vatRate / 100);
                const wtaxAmount = dppNet * (wtaxRate / 100);
                const lineAmount = dppNet + vatAmount - wtaxAmount;

                originalTotal += grossDpp;
                totalVat += vatAmount;
                totalWtax += wtaxAmount;
                lineAmountSum += lineAmount;
            });

            let headerDisc = parseFloat($('#discount_amount').val() || 0);
            if (headerDisc > lineAmountSum) headerDisc = lineAmountSum;
            const amountDue = lineAmountSum - headerDisc;

            const fmt = (n) => n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#original-dpp').text(fmt(originalTotal));
            $('#total-vat').text(fmt(totalVat));
            $('#total-wtax').text(fmt(totalWtax));
            $('#total-line-discount').text(fmt(totalLineDppDiscount));
            $('#total-amount').text(fmt(lineAmountSum));
            $('#total-header-discount').text(fmt(headerDisc));
            $('#amount-due').text(fmt(amountDue));
        }
    </script>
@endpush
