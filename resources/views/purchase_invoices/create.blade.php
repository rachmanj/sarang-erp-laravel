@extends('layouts.main')

@section('title', 'Create Purchase Invoice')

@section('title_page')
    Create Purchase Invoice
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-invoices.index') }}">Purchase Invoices</a></li>
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
                                New Purchase Invoice
                            </h3>
                            <a href="{{ route('purchase-invoices.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Purchase Invoices
                            </a>
                        </div>
                        <form method="post" action="{{ route('purchase-invoices.store') }}">
                            @csrf
                            <div class="card-body pb-1">
                                @isset($purchase_order_id)
                                    <input type="hidden" name="purchase_order_id" value="{{ $purchase_order_id }}" />
                                @endisset
                                @isset($goods_receipt_id)
                                    <input type="hidden" name="goods_receipt_id" value="{{ $goods_receipt_id }}" />
                                @endisset

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
                                            <label class="col-sm-3 col-form-label">Payment Method <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="payment_method" id="payment_method"
                                                    class="form-control form-control-sm" required>
                                                    <option value="credit"
                                                        {{ old('payment_method', 'credit') == 'credit' ? 'selected' : '' }}>
                                                        Credit</option>
                                                    <option value="cash"
                                                        {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
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
                                    <div class="col-md-4" id="cash_account_field" style="display: none;">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Cash Account</label>
                                            <div class="col-sm-9">
                                                <select name="cash_account_id" id="cash_account_id"
                                                    class="form-control form-control-sm select2bs4">
                                                    <option value="">-- Default (Kas di Tangan) --</option>
                                                    @foreach ($cashAccounts ?? [] as $cashAccount)
                                                        <option value="{{ $cashAccount->id }}"
                                                            {{ old('cash_account_id') == $cashAccount->id ? 'selected' : '' }}>
                                                            {{ $cashAccount->code }} - {{ $cashAccount->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="form-text text-muted">Leave empty to use default cash
                                                    account</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
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
                                                    These invoices will NOT affect inventory quantities.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Description</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="description"
                                                    value="{{ old('description') }}" class="form-control form-control-sm"
                                                    placeholder="Invoice description">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Discount (%)</label>
                                            <div class="col-sm-9">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="discount_percentage" id="discount_percentage"
                                                    value="{{ old('discount_percentage', 0) }}"
                                                    class="form-control form-control-sm"
                                                    placeholder="Header discount %">
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
                                                        @if ($showAccounts ?? false)
                                                            <th style="width: 12%">Account <span
                                                                    class="text-danger">*</span></th>
                                                        @endif
                                                        <th style="width: {{ $showAccounts ? '12%' : '14%' }}">Item <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 8%">Warehouse</th>
                                                        <th style="width: 10%">Description</th>
                                                        <th style="width: 6%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 8%">UOM</th>
                                                        <th style="width: 8%">Unit Price <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 5%">VAT</th>
                                                        <th style="width: 5%">WTax</th>
                                                        <th style="width: 5%">Disc %</th>
                                                        <th style="width: 6%">Disc Amt</th>
                                                        <th style="width: 9%">Amount</th>
                                                        <th style="width: 7%">Project</th>
                                                        <th style="width: 7%">Dept</th>
                                                        <th style="width: 4%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lines">
                                                    <tr class="line-item">
                                                        @if ($showAccounts ?? false)
                                                            <td>
                                                                <select name="lines[0][account_id]"
                                                                    class="form-control form-control-sm select2bs4 account-select">
                                                                    <option value="">-- select account --</option>
                                                                    @foreach ($accounts as $a)
                                                                        <option value="{{ $a->id }}">
                                                                            {{ $a->code }} - {{ $a->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <small class="text-muted d-block account-display-0"
                                                                    style="display: none;"></small>
                                                            </td>
                                                        @else
                                                            <td style="display: none;">
                                                                <input type="hidden" name="lines[0][account_id]"
                                                                    class="account-input" value="">
                                                                <small class="text-muted d-block account-display-0"
                                                                    style="display: none;"></small>
                                                            </td>
                                                        @endif
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-secondary btn-select-item"
                                                                onclick="openItemSelectionModal(0)" title="Select Item">
                                                                <i class="fas fa-search"></i> Select Item
                                                            </button>
                                                            <input type="hidden" name="lines[0][inventory_item_id]"
                                                                class="item-id-input" value="">
                                                            <div class="mt-1">
                                                                <span class="item-name-display-0 text-muted small"></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][warehouse_id]"
                                                                class="form-control form-control-sm select2bs4 warehouse-select">
                                                                <option value="">-- select --</option>
                                                                @foreach ($warehouses as $w)
                                                                    <option value="{{ $w->id }}">
                                                                        {{ $w->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="lines[0][description]"
                                                                class="form-control form-control-sm"
                                                                placeholder="Description">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" min="0.01"
                                                                name="lines[0][qty]"
                                                                class="form-control form-control-sm text-right qty-input"
                                                                value="1" required>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][order_unit_id]"
                                                                class="form-control form-control-sm unit-select select2bs4"
                                                                data-line-idx="0">
                                                                <option value="">Select Unit</option>
                                                            </select>
                                                            <div class="conversion-preview mt-1"
                                                                style="font-size: 0.75rem; color: #6c757d;"></div>
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" min="0"
                                                                name="lines[0][unit_price]"
                                                                class="form-control form-control-sm text-right price-input"
                                                                value="0" required>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][tax_code_id]"
                                                                class="form-control form-control-sm vat-select tax-code-select">
                                                                <option value="" data-rate="0">No VAT</option>
                                                                @foreach ($taxCodes ?? [] as $tc)
                                                                    @if(stripos($tc->name ?? '', 'ppn') !== false || ($tc->type ?? '') === 'ppn_input')
                                                                        <option value="{{ $tc->id }}" data-rate="{{ $tc->rate ?? 0 }}">{{ $tc->code ?? $tc->name }} ({{ $tc->rate ?? 0 }}%)</option>
                                                                    @endif
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
                                                                value="0" placeholder="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" min="0"
                                                                name="lines[0][discount_amount]"
                                                                class="form-control form-control-sm text-right line-discount-amount"
                                                                value="0" placeholder="0.00">
                                                        </td>
                                                        <td class="text-right">
                                                            <span class="line-amount">0.00</span>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][project_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
                                                                @foreach ($projects as $p)
                                                                    <option value="{{ $p->id }}">
                                                                        {{ $p->code }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][dept_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
                                                                @foreach ($departments as $d)
                                                                    <option value="{{ $d->id }}">
                                                                        {{ $d->code }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-xs btn-danger rm">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="{{ $showAccounts ?? false ? '4' : '3' }}"
                                                            class="text-right">Subtotal:</th>
                                                        <th class="text-right" id="original-amount">0.00</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th colspan="2"></th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="{{ $showAccounts ?? false ? '4' : '3' }}"
                                                            class="text-right">Line Discounts:</th>
                                                        <th colspan="4" class="text-right" id="total-line-discount">0.00</th>
                                                        <th colspan="4"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="{{ $showAccounts ?? false ? '4' : '3' }}"
                                                            class="text-right">Header Discount:</th>
                                                        <th colspan="4" class="text-right" id="total-header-discount">0.00</th>
                                                        <th colspan="4"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="{{ $showAccounts ?? false ? '4' : '3' }}"
                                                            class="text-right">Total Discount:</th>
                                                        <th colspan="4" class="text-right" id="total-discount">0.00</th>
                                                        <th colspan="4"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="{{ $showAccounts ?? false ? '4' : '3' }}"
                                                            class="text-right">Amount Due:</th>
                                                        <th colspan="4" class="text-right" id="amount-due">0.00</th>
                                                        <th colspan="4"></th>
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
                                        <a href="{{ route('purchase-invoices.index') }}" class="btn btn-default">
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
        let idx = 1;
        let updatingHeaderDiscount = false;

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
                    url: '{{ route('purchase-invoices.api.document-number') }}',
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

            // Handle prefill data from GRPO
            @if (isset($prefill))
                const prefill = @json($prefill);

                // Prefill header fields
                if (prefill.date) {
                    $('input[name="date"]').val(prefill.date);
                }
                if (prefill.business_partner_id) {
                    $('select[name="business_partner_id"]').val(prefill.business_partner_id).trigger('change');
                }
                if (prefill.company_entity_id) {
                    $('select[name="company_entity_id"]').val(prefill.company_entity_id).trigger('change');
                }
                if (prefill.description) {
                    $('input[name="description"]').val(prefill.description);
                }

                // Prefill lines
                if (prefill.lines && prefill.lines.length > 0) {
                    // Remove the default empty line
                    $('#lines tr:first').remove();
                    idx = 0;

                    prefill.lines.forEach(function(lineData, arrayIndex) {
                        if (arrayIndex > 0) {
                            addLine();
                        }

                        const row = $('#lines tr').eq(arrayIndex);
                        const lineIndex = arrayIndex; // Use array index as line index

                        // Set account
                        if (lineData.account_id) {
                            row.find('.account-select, .account-input').val(lineData.account_id);
                            if (row.find('.account-select').length) {
                                row.find('.account-select').trigger('change');
                            }
                        }

                        // Set inventory item
                        if (lineData.inventory_item_id) {
                            row.find('.item-id-input').val(lineData.inventory_item_id);

                            // Load item details and account via AJAX
                            $.ajax({
                                url: `/inventory/api/items/${lineData.inventory_item_id}/account`,
                                method: 'GET',
                                success: function(response) {
                                    if (response.success) {
                                        // Update account field
                                        const accountInput = row.find('.account-input');
                                        const accountSelect = row.find('.account-select');

                                        if (accountInput.length) {
                                            accountInput.val(response.account_id);
                                        }
                                        if (accountSelect.length) {
                                            accountSelect.val(response.account_id).trigger(
                                                'change');
                                        }

                                        // Show account info - find the account display element
                                        const accountDisplay = row.find(
                                            '[class*="account-display"]');
                                        if (accountDisplay.length) {
                                            accountDisplay.text(
                                                `${response.account_code} - ${response.account_name}`
                                            ).show();
                                        }
                                    }
                                }
                            });

                            // Load item name
                            $.get('/purchase-orders/api/item/' + lineData.inventory_item_id, function(
                                item) {
                                const itemDisplay = row.find('[class*="item-name-display"]');
                                if (itemDisplay.length) {
                                    itemDisplay.text(item.code + ' - ' + item.name).show();
                                }
                            }).fail(function() {
                                // Fallback: just show item ID
                                const itemDisplay = row.find('[class*="item-name-display"]');
                                if (itemDisplay.length) {
                                    itemDisplay.text('Item #' + lineData.inventory_item_id).show();
                                }
                            });

                            // Load units for the item
                            loadItemUnits(lineData.inventory_item_id, row);
                        }

                        // Set warehouse
                        if (lineData.warehouse_id) {
                            row.find('.warehouse-select').val(lineData.warehouse_id).trigger('change');
                        }

                        // Set description
                        if (lineData.description) {
                            row.find('input[name*="[description]"]').val(lineData.description);
                        }

                        // Set quantity and price
                        if (lineData.qty) {
                            row.find('.qty-input').val(lineData.qty).trigger('input');
                        }
                        if (lineData.unit_price) {
                            row.find('.price-input').val(lineData.unit_price).trigger('input');
                        }

                        // Set tax code (convert to VAT/WTax rates if needed)
                        if (lineData.tax_code_id) {
                            // You may need to fetch tax code details to set VAT/WTax rates
                            // For now, we'll leave it as is since the form uses VAT/WTax rates
                        }

                        // Update line amount
                        updateLineAmount(row);
                    });

                    idx = prefill.lines.length;
                    updateTotals();
                }
            @endif

            // Remove line
            $(document).on('click', '.rm', function() {
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

            // Line discount: sync percentage <-> amount
            $(document).on('input', '.line-discount-percentage', function() {
                const row = $(this).closest('tr');
                const lineAmount = parseFloat(row.find('.qty-input').val() || 0) * parseFloat(row.find('.price-input').val() || 0);
                const pct = parseFloat($(this).val() || 0);
                row.find('.line-discount-amount').val((lineAmount * pct / 100).toFixed(2));
                updateLineAmount(row);
                updateTotals();
            });
            $(document).on('input', '.line-discount-amount', function() {
                const row = $(this).closest('tr');
                const lineAmount = parseFloat(row.find('.qty-input').val() || 0) * parseFloat(row.find('.price-input').val() || 0);
                const amt = parseFloat($(this).val() || 0);
                const pct = lineAmount > 0 ? (amt / lineAmount * 100) : 0;
                row.find('.line-discount-percentage').val(pct.toFixed(2));
                updateLineAmount(row);
                updateTotals();
            });

            // Header discount: sync percentage <-> amount
            $('#discount_percentage').on('input', function() {
                if (updatingHeaderDiscount) return;
                updatingHeaderDiscount = true;
                const subtotal = getSubtotal();
                const pct = parseFloat($(this).val() || 0);
                $('#discount_amount').val((subtotal * pct / 100).toFixed(2));
                updatingHeaderDiscount = false;
                updateTotals();
            });
            $('#discount_amount').on('input', function() {
                if (updatingHeaderDiscount) return;
                updatingHeaderDiscount = true;
                const subtotal = getSubtotal();
                const amt = parseFloat($(this).val() || 0);
                const pct = subtotal > 0 ? (amt / subtotal * 100) : 0;
                $('#discount_percentage').val(pct.toFixed(2));
                updatingHeaderDiscount = false;
                updateTotals();
            });

            updateTotals();
        });

        function addLine() {
            const container = document.getElementById('lines');
            const row = document.createElement('tr');
            row.className = 'line-item';

            const showAccounts = {{ $showAccounts ?? false ? 'true' : 'false' }};
            const accountSelect = showAccounts ? `
                <td>
                    <select name="lines[${idx}][account_id]" class="form-control form-control-sm select2bs4 account-select">
                        <option value="">-- select account --</option>
                        @if ($showAccounts ?? false)
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    <small class="text-muted d-block account-display-${idx}" style="display: none;"></small>
                </td>
            ` : `
                <td style="display: none;">
                    <input type="hidden" name="lines[${idx}][account_id]" class="account-input" value="">
                    <small class="text-muted d-block account-display-${idx}" style="display: none;"></small>
                </td>
            `;

            row.innerHTML = accountSelect + `
                <td>
                    <button type="button" class="btn btn-sm btn-secondary btn-select-item" 
                        onclick="openItemSelectionModal(${idx})" title="Select Item">
                        <i class="fas fa-search"></i> Select Item
                    </button>
                    <input type="hidden" name="lines[${idx}][inventory_item_id]" class="item-id-input" value="">
                    <div class="mt-1">
                        <span class="item-name-display-${idx} text-muted small"></span>
                    </div>
                </td>
                <td>
                    <select name="lines[${idx}][warehouse_id]" class="form-control form-control-sm select2bs4 warehouse-select">
                        <option value="">-- select --</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="lines[${idx}][description]" class="form-control form-control-sm" placeholder="Description">
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="lines[${idx}][qty]" 
                        class="form-control form-control-sm text-right qty-input" value="1" required>
                </td>
                <td>
                    <select name="lines[${idx}][order_unit_id]" class="form-control form-control-sm unit-select select2bs4" data-line-idx="${idx}">
                        <option value="">Select Unit</option>
                    </select>
                    <div class="conversion-preview mt-1" style="font-size: 0.75rem; color: #6c757d;"></div>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" 
                        class="form-control form-control-sm text-right price-input" value="0" required>
                </td>
                <td>
                    <select name="lines[${idx}][tax_code_id]" class="form-control form-control-sm vat-select tax-code-select">
                        <option value="" data-rate="0">No VAT</option>
                        @foreach ($taxCodes ?? [] as $tc)
                            @if(stripos($tc->name ?? '', 'ppn') !== false || ($tc->type ?? '') === 'ppn_input')
                                <option value="{{ $tc->id }}" data-rate="{{ $tc->rate ?? 0 }}">{{ $tc->code ?? $tc->name }} ({{ $tc->rate ?? 0 }}%)</option>
                            @endif
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
                    <input type="number" step="0.01" min="0" max="100"
                        name="lines[${idx}][discount_percentage]"
                        class="form-control form-control-sm text-right line-discount-percentage"
                        value="0" placeholder="0">
                </td>
                <td>
                    <input type="number" step="0.01" min="0"
                        name="lines[${idx}][discount_amount]"
                        class="form-control form-control-sm text-right line-discount-amount"
                        value="0" placeholder="0.00">
                </td>
                <td class="text-right">
                    <span class="line-amount">0.00</span>
                </td>
                <td>
                    <select name="lines[${idx}][project_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->code }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="lines[${idx}][dept_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->code }}</option>
                        @endforeach
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

            updateLineAmount(row);
            updateTotals();
            idx++;
        }

        // Item Selection Modal Functions
        window.currentLineIndex = 0;

        function openItemSelectionModal(lineIndex) {
            window.currentLineIndex = lineIndex;
            $('#itemSelectionModal').modal('show');
            loadItems();
        }

        function loadItems(page = 1) {
            const code = $('#searchCode').val();
            const name = $('#searchName').val();
            const category = $('#searchCategory').val();
            const type = $('#searchType').val();
            const lineIdx = window.currentLineIndex;
            const warehouseId = $(`select[name="lines[${lineIdx}][warehouse_id]"]`).val() || '';

            $.ajax({
                url: '{{ route('inventory.search') }}',
                method: 'GET',
                data: {
                    code: code,
                    name: name,
                    category_id: category,
                    item_type: type,
                    warehouse_id: warehouseId,
                    per_page: 20,
                    page: page
                },
                success: function(response) {
                    const tbody = $('#itemsTable tbody');
                    tbody.empty();

                    if (response.items && response.items.length > 0) {
                        response.items.forEach(function(item, index) {
                            const row = `
                                <tr>
                                    <td>${(page - 1) * 20 + index + 1}</td>
                                    <td><strong>${item.code}</strong></td>
                                    <td>${item.name}</td>
                                    <td>${item.category ? item.category.name : 'N/A'}</td>
                                    <td><span class="badge badge-${item.item_type === 'item' ? 'primary' : 'info'}">${item.item_type}</span></td>
                                    <td>${item.unit_of_measure}</td>
                                    <td class="text-right">${parseFloat(item.purchase_price || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                                    <td class="text-right">${parseFloat(item.selling_price || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                                    <td class="text-right">${item.available_quantity != null ? parseFloat(item.available_quantity).toLocaleString('id-ID', {minimumFractionDigits: 2}) : '—'}</td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-primary select-item-btn" 
                                            data-item-id="${item.id}" 
                                            data-item-code="${item.code}"
                                            data-item-name="${item.name}"
                                            data-item-price="${item.purchase_price || 0}">
                                            <i class="fas fa-check"></i> Select
                                        </button>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                        });

                        // Update pagination
                        updatePagination(response.pagination);
                        $('#searchResultsCount').text(`Found ${response.pagination.total} items`);
                    } else {
                        tbody.append('<tr><td colspan="10" class="text-center">No items found</td></tr>');
                        $('#searchResultsCount').text('No items found');
                    }
                },
                error: function() {
                    toastr.error('Failed to load items');
                }
            });
        }

        function updatePagination(pagination) {
            const paginationEl = $('#itemsPagination');
            paginationEl.empty();

            if (pagination.last_page <= 1) return;

            // Previous button
            if (pagination.current_page > 1) {
                paginationEl.append(
                    `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a></li>`
                );
            }

            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                const active = i === pagination.current_page ? 'active' : '';
                paginationEl.append(
                    `<li class="page-item ${active}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
            }

            // Next button
            if (pagination.current_page < pagination.last_page) {
                paginationEl.append(
                    `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a></li>`
                );
            }
        }

        // Item selection handlers
        $(document).on('click', '#searchItems', function() {
            loadItems(1);
        });

        $(document).on('click', '#clearSearch', function() {
            $('#searchCode, #searchName').val('');
            $('#searchCategory, #searchType').val('');
            loadItems(1);
        });

        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                loadItems(page);
            }
        });

        window.applySelectedItemToLine = function(lineIndex, item) {
            const itemId = item.id;
            const itemCode = item.code;
            const itemName = item.name;
            const itemPrice = item.purchase_price || item.unit_price || 0;

            $(`input[name="lines[${lineIndex}][inventory_item_id]"]`).val(itemId);
            $(`.item-name-display-${lineIndex}`).text(`${itemCode} - ${itemName}`).show();
            $(`input[name="lines[${lineIndex}][unit_price]"]`).val(itemPrice).trigger('input');

            $.ajax({
                url: `/inventory/api/items/${itemId}/account`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const accountInput = $(`input[name="lines[${lineIndex}][account_id]"]`);
                        const accountSelect = $(`select[name="lines[${lineIndex}][account_id]"]`);
                        if (accountInput.length) accountInput.val(response.account_id);
                        if (accountSelect.length) accountSelect.val(response.account_id).trigger('change');
                        $(`.account-display-${lineIndex}`)
                            .text(`${response.account_code} - ${response.account_name}`)
                            .show();
                        if (response.default_warehouse_id) {
                            $(`select[name="lines[${lineIndex}][warehouse_id]"]`)
                                .val(response.default_warehouse_id)
                                .trigger('change');
                        }
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.error || 'Failed to get account for item';
                    toastr.error(errorMsg);
                }
            });

            loadItemUnits(itemId, $(`input[name="lines[${lineIndex}][inventory_item_id]"]`).closest('tr'));
            const row = $(`input[name="lines[${lineIndex}][inventory_item_id]"]`).closest('tr');
            updateLineAmount(row);
            updateTotals();
            toastr.success('Item selected successfully');
        };

        $(document).on('click', '.select-item-btn', function() {
            const lineIndex = window.currentLineIndex;
            const item = {
                id: $(this).data('item-id'),
                code: $(this).data('item-code'),
                name: $(this).data('item-name'),
                purchase_price: $(this).data('item-price')
            };
            window.applySelectedItemToLine(lineIndex, item);
            $('#itemSelectionModal').modal('hide');
        });

        // Function to load units for an item
        function loadItemUnits(itemId, $row) {
            if (!itemId) return;

            const lineIdx = $row.find('.unit-select').data('line-idx');
            const $unitSelect = $row.find('.unit-select');
            $unitSelect.empty().append('<option value="">Loading units...</option>');

            $.get('/purchase-orders/api/item-units', {
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
                        theme: 'bootstrap4',
                        placeholder: 'Select Unit',
                        allowClear: true
                    });

                    // Trigger change to show conversion preview if unit is selected
                    if ($unitSelect.val()) {
                        updateConversionPreview(lineIdx, $row);
                    }
                })
                .fail(function() {
                    $unitSelect.empty().append('<option value="">Error loading units</option>');
                });
        }

        // Function to update conversion preview
        function updateConversionPreview(lineIdx, $row) {
            const itemId = $row.find('.item-id-input').val();
            const unitId = $row.find('.unit-select').val();
            const quantity = parseFloat($row.find('.qty-input').val() || 0);

            if (!itemId || !unitId || quantity <= 0) {
                $row.find('.conversion-preview-' + lineIdx).text('').hide();
                return;
            }

            $.get('/purchase-orders/api/conversion-preview', {
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

        // Handle unit selection change
        $(document).on('change', '.unit-select', function() {
            const $row = $(this).closest('tr');
            const lineIdx = $(this).data('line-idx');
            updateConversionPreview(lineIdx, $row);
            updateLineAmount($row);
            updateTotals();
        });

        // Handle quantity change to update conversion preview
        $(document).on('input', '.qty-input', function() {
            const $row = $(this).closest('tr');
            const lineIdx = $row.find('.unit-select').data('line-idx');
            if (lineIdx !== undefined) {
                updateConversionPreview(lineIdx, $row);
            }
            updateLineAmount($row);
            updateTotals();
        });

        function updateLineAmount(row) {
            const qty = parseFloat($(row).find('.qty-input').val() || 0);
            const price = parseFloat($(row).find('.price-input').val() || 0);
            const vatRate = parseFloat($(row).find('.vat-select option:selected').data('rate') || 0);
            const wtaxRate = parseFloat($(row).find('.wtax-select').val() || 0);
            let discountAmount = parseFloat($(row).find('.line-discount-amount').val() || 0);
            const discountPct = parseFloat($(row).find('.line-discount-percentage').val() || 0);
            const originalAmount = qty * price;
            if (discountPct > 0 && discountAmount === 0) {
                discountAmount = originalAmount * discountPct / 100;
                $(row).find('.line-discount-amount').val(discountAmount.toFixed(2));
            } else if (discountAmount > 0 && discountPct === 0) {
                const pct = originalAmount > 0 ? (discountAmount / originalAmount * 100) : 0;
                $(row).find('.line-discount-percentage').val(pct.toFixed(2));
            }
            const netAmount = originalAmount - discountAmount;
            const vatAmount = netAmount * (vatRate / 100);
            const wtaxAmount = netAmount * (wtaxRate / 100);
            const lineAmount = netAmount + vatAmount - wtaxAmount;

            $(row).find('.line-amount').text(lineAmount.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }

        function getSubtotal() {
            let subtotal = 0;
            $('#lines tr').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val() || 0);
                const price = parseFloat($(this).find('.price-input').val() || 0);
                const vatRate = parseFloat($(this).find('.vat-select option:selected').data('rate') || 0);
                const wtaxRate = parseFloat($(this).find('.wtax-select').val() || 0);
                const discountAmount = parseFloat($(this).find('.line-discount-amount').val() || 0);
                const originalAmount = qty * price;
                const netAmount = originalAmount - discountAmount;
                const vatAmount = netAmount * (vatRate / 100);
                const wtaxAmount = netAmount * (wtaxRate / 100);
                subtotal += netAmount + vatAmount - wtaxAmount;
            });
            return subtotal;
        }

        function updateTotals() {
            let originalTotal = 0;
            let totalVat = 0;
            let totalWtax = 0;
            let totalLineDiscount = 0;
            let subtotal = 0;

            $('#lines tr').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val() || 0);
                const price = parseFloat($(this).find('.price-input').val() || 0);
                const vatRate = parseFloat($(this).find('.vat-select option:selected').data('rate') || 0);
                const wtaxRate = parseFloat($(this).find('.wtax-select').val() || 0);
                const discountAmount = parseFloat($(this).find('.line-discount-amount').val() || 0);
                const originalAmount = qty * price;
                const netAmount = originalAmount - discountAmount;
                const vatAmount = netAmount * (vatRate / 100);
                const wtaxAmount = netAmount * (wtaxRate / 100);
                const lineAmount = netAmount + vatAmount - wtaxAmount;

                originalTotal += originalAmount;
                totalVat += vatAmount;
                totalWtax += wtaxAmount;
                totalLineDiscount += discountAmount;
                subtotal += lineAmount;
            });

            const headerDiscountPct = parseFloat($('#discount_percentage').val() || 0);
            let headerDiscountAmount = parseFloat($('#discount_amount').val() || 0);
            if (headerDiscountPct > 0 && headerDiscountAmount === 0) {
                headerDiscountAmount = subtotal * headerDiscountPct / 100;
                if (!updatingHeaderDiscount) {
                    updatingHeaderDiscount = true;
                    $('#discount_amount').val(headerDiscountAmount.toFixed(2));
                    updatingHeaderDiscount = false;
                }
            }
            const totalDiscount = totalLineDiscount + headerDiscountAmount;
            const amountDue = subtotal - headerDiscountAmount;

            $('#original-amount').text(subtotal.toLocaleString('id-ID', {
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

            $('#total-amount').text(subtotal.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            $('#total-line-discount').text(totalLineDiscount.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            $('#total-header-discount').text(headerDiscountAmount.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            $('#total-discount').text(totalDiscount.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

            $('#amount-due').text(amountDue.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }

        // Toggle cash account field visibility
        function toggleCashAccountField() {
            const paymentMethod = $('#payment_method').val();
            const hasPO = $('input[name="purchase_order_id"]').length > 0 && $('input[name="purchase_order_id"]').val();
            const hasGRPO = $('input[name="goods_receipt_id"]').length > 0 && $('input[name="goods_receipt_id"]').val();

            // Show cash account field when: Cash payment AND no PO/GRPO (direct purchase)
            if (paymentMethod === 'cash' && !hasPO && !hasGRPO) {
                $('#cash_account_field').show();
            } else {
                $('#cash_account_field').hide();
            }
        }

        // Initialize on page load
        toggleCashAccountField();

        // Update on change
        $('#payment_method').on('change', function() {
            toggleCashAccountField();
        });
    </script>
@endpush
