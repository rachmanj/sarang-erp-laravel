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
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Description</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="description"
                                                    value="{{ old('description') }}" class="form-control form-control-sm"
                                                    placeholder="Invoice description">
                                            </div>
                                        </div>
                                    </div>
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
                                                            <select name="lines[0][vat_rate]"
                                                                class="form-control form-control-sm vat-select">
                                                                <option value="0">No</option>
                                                                <option value="11">11%</option>
                                                                <option value="12">12%</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="lines[0][wtax_rate]"
                                                                class="form-control form-control-sm wtax-select">
                                                                <option value="0">No</option>
                                                                <option value="2">2%</option>
                                                            </select>
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
                                                            class="text-right">Original Amount:</th>
                                                        <th class="text-right" id="original-amount">0.00</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th colspan="3"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="{{ $showAccounts ?? false ? '4' : '3' }}"
                                                            class="text-right">Amount Due:</th>
                                                        <th colspan="4" class="text-right" id="amount-due">0.00</th>
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

        $(document).ready(function() {
            // Initialize Select2BS4 for all select elements
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
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
                    <select name="lines[${idx}][vat_rate]" class="form-control form-control-sm vat-select">
                        <option value="0">No</option>
                        <option value="11">11%</option>
                        <option value="12">12%</option>
                    </select>
                </td>
                <td>
                    <select name="lines[${idx}][wtax_rate]" class="form-control form-control-sm wtax-select">
                        <option value="0">No</option>
                        <option value="2">2%</option>
                    </select>
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

            $.ajax({
                url: '{{ route('inventory.search') }}',
                method: 'GET',
                data: {
                    code: code,
                    name: name,
                    category_id: category,
                    item_type: type,
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
                        tbody.append('<tr><td colspan="9" class="text-center">No items found</td></tr>');
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

        $(document).on('click', '.select-item-btn', function() {
            const itemId = $(this).data('item-id');
            const itemCode = $(this).data('item-code');
            const itemName = $(this).data('item-name');
            const itemPrice = $(this).data('item-price');
            const lineIndex = window.currentLineIndex;

            // Update item fields
            $(`input[name="lines[${lineIndex}][inventory_item_id]"]`).val(itemId);
            $(`.item-name-display-${lineIndex}`).text(`${itemCode} - ${itemName}`).show();

            // Update price
            $(`input[name="lines[${lineIndex}][unit_price]"]`).val(itemPrice).trigger('input');

            // Auto-populate account via AJAX
            $.ajax({
                url: `/inventory/api/items/${itemId}/account`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Update account field (hidden or visible)
                        const accountInput = $(`input[name="lines[${lineIndex}][account_id]"]`);
                        const accountSelect = $(`select[name="lines[${lineIndex}][account_id]"]`);

                        if (accountInput.length) {
                            accountInput.val(response.account_id);
                        }
                        if (accountSelect.length) {
                            accountSelect.val(response.account_id).trigger('change');
                        }

                        // Show account info
                        $(`.account-display-${lineIndex}`)
                            .text(`${response.account_code} - ${response.account_name}`)
                            .show();

                        // Auto-select default warehouse if available
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

            // Load units for selected item
            loadItemUnits(itemId, $(`input[name="lines[${lineIndex}][inventory_item_id]"]`).closest('tr'));

            // Update line amount
            const row = $(`input[name="lines[${lineIndex}][inventory_item_id]"]`).closest('tr');
            updateLineAmount(row);
            updateTotals();

            // Close modal
            $('#itemSelectionModal').modal('hide');
            toastr.success('Item selected successfully');
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

            $('#lines tr').each(function() {
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
