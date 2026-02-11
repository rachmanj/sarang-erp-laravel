@extends('layouts.main')

@section('title', 'Edit Sales Invoice')

@section('title_page')
    Edit Sales Invoice #{{ $invoice->invoice_no }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Sales Invoices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.show', $invoice->id) }}">#{{ $invoice->invoice_no }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
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
                                Edit Sales Invoice #{{ $invoice->invoice_no }}
                            </h3>
                            <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                                class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Invoice
                            </a>
                        </div>
                        <form method="post" action="{{ route('sales-invoices.update', $invoice->id) }}">
                            @csrf
                            @method('PUT')
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
                                                        value="{{ old('date', $invoice->date->toDateString()) }}"
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
                                                            {{ old('company_entity_id', $invoice->company_entity_id) == $entity->id ? 'selected' : '' }}>
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
                                                            {{ old('business_partner_id', $invoice->business_partner_id) == $c->id ? 'selected' : '' }}>
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
                                                    value="{{ old('terms_days', $invoice->terms_days ?? 30) }}"
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
                                                    value="{{ old('description', $invoice->description ?? '') }}"
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
                                                        value="{{ old('reference_no', $invoice->reference_no ?? '') }}"
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
                                                    <input type="date" name="due_date"
                                                        value="{{ old('due_date', $invoice->due_date?->toDateString()) }}"
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
                                                        {{ old('is_opening_balance', $invoice->is_opening_balance) ? 'checked' : '' }}>
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
                                                    @forelse ($invoice->lines as $index => $line)
                                                        <tr class="line-item">
                                                            <td>
                                                                @if (!empty($line->inventory_item_id))
                                                                    <input type="hidden" name="lines[{{ $index }}][inventory_item_id]" value="{{ $line->inventory_item_id }}">
                                                                @endif
                                                                <input type="hidden" name="lines[{{ $index }}][description]" value="{{ $line->description ?? '' }}">
                                                                <input type="hidden" name="lines[{{ $index }}][item_code]" value="{{ $line->item_code ?? '' }}">
                                                                <input type="hidden" name="lines[{{ $index }}][item_name]" value="{{ $line->item_name ?? '' }}">
                                                                <select name="lines[{{ $index }}][account_id]"
                                                                    class="form-control form-control-sm select2bs4"
                                                                    required>
                                                                    @foreach ($accounts as $a)
                                                                        <option value="{{ $a->id }}"
                                                                            {{ old('lines.'.$index.'.account_id', $line->account_id) == $a->id ? 'selected' : '' }}>
                                                                            {{ $a->code }} - {{ $a->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="lines[{{ $index }}][item_code_display]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $line->item_code ?? '' }}"
                                                                    readonly
                                                                    style="background-color: #e9ecef;">
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="lines[{{ $index }}][item_name_display]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $line->item_name ?? '' }}"
                                                                    readonly
                                                                    style="background-color: #e9ecef;">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0.01"
                                                                    name="lines[{{ $index }}][qty]"
                                                                    class="form-control form-control-sm text-right qty-input"
                                                                    value="{{ old('lines.'.$index.'.qty', $line->qty) }}" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0"
                                                                    name="lines[{{ $index }}][unit_price]"
                                                                    class="form-control form-control-sm text-right price-input"
                                                                    value="{{ old('lines.'.$index.'.unit_price', $line->unit_price) }}" required>
                                                            </td>
                                                            <td>
                                                                <select name="lines[{{ $index }}][tax_code_id]"
                                                                    class="form-control form-control-sm vat-select">
                                                                    <option value="">No</option>
                                                                    @foreach ($vatTaxCodes ?? [] as $t)
                                                                        <option value="{{ $t->id }}" data-rate="{{ $t->rate }}"
                                                                            {{ old('lines.'.$index.'.tax_code_id', $line->tax_code_id) == $t->id ? 'selected' : '' }}>
                                                                            {{ (int)$t->rate }}%</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select name="lines[{{ $index }}][wtax_rate]"
                                                                    class="form-control form-control-sm wtax-select">
                                                                    <option value="0" {{ (float) old('lines.'.$index.'.wtax_rate', $line->wtax_rate ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                                                    <option value="2" {{ (float) old('lines.'.$index.'.wtax_rate', $line->wtax_rate ?? 0) == 2 ? 'selected' : '' }}>2%</option>
                                                                </select>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button"
                                                                    class="btn btn-xs btn-danger rm">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr class="line-item">
                                                            <td>
                                                                <input type="hidden" name="lines[0][description]" value="">
                                                                <input type="hidden" name="lines[0][item_code]" value="">
                                                                <input type="hidden" name="lines[0][item_name]" value="">
                                                                <select name="lines[0][account_id]" class="form-control form-control-sm select2bs4" required>
                                                                    @foreach ($accounts as $a)
                                                                        <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="lines[0][item_code_display]" class="form-control form-control-sm" placeholder="Item Code" readonly style="background-color: #e9ecef;">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="lines[0][item_name_display]" class="form-control form-control-sm" placeholder="Item Name" readonly style="background-color: #e9ecef;">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0.01" name="lines[0][qty]" class="form-control form-control-sm text-right qty-input" value="1" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" name="lines[0][unit_price]" class="form-control form-control-sm text-right price-input" value="0" required>
                                                            </td>
                                                            <td>
                                                                <select name="lines[0][tax_code_id]" class="form-control form-control-sm vat-select">
                                                                    <option value="">No</option>
                                                                    @foreach ($vatTaxCodes ?? [] as $t)
                                                                        <option value="{{ $t->id }}" data-rate="{{ $t->rate }}">{{ (int)$t->rate }}%</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select name="lines[0][wtax_rate]" class="form-control form-control-sm wtax-select">
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
                                                    @endforelse
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
                                            <i class="fas fa-save mr-1"></i> Update Invoice
                                        </button>
                                        <a href="{{ route('sales-invoices.show', $invoice->id) }}" class="btn btn-default">
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
        let idx = {{ max(1, $invoice->lines->count()) }};

        $(document).ready(function() {
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

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
                                const dateObj = new Date(invoiceDate);
                                dateObj.setDate(dateObj.getDate() + termsDays);
                                const dueDate = dateObj.toISOString().split('T')[0];
                                if (!$('input[name="due_date"]').val()) {
                                    $('input[name="due_date"]').val(dueDate);
                                }
                                $('input[name="terms_days"]').val(termsDays);
                            }
                        }
                    });
                }
            });

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
                                const dateObj = new Date(invoiceDate);
                                dateObj.setDate(dateObj.getDate() + termsDays);
                                const dueDate = dateObj.toISOString().split('T')[0];
                                if (!$('input[name="due_date"]').val()) {
                                    $('input[name="due_date"]').val(dueDate);
                                }
                                $('input[name="terms_days"]').val(termsDays);
                            }
                        }
                    });
                }
            });

            $(document).on('click', '.rm', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });

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
                    <input type="number" step="0.01" min="0.01" name="lines[${idx}][qty]" class="form-control form-control-sm text-right qty-input" value="1" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="lines[${idx}][unit_price]" class="form-control form-control-sm text-right price-input" value="0" required>
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
