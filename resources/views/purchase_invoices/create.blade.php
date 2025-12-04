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
                                                <input type="text" name="description" value="{{ old('description') }}"
                                                    class="form-control form-control-sm" placeholder="Invoice description">
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
                                                        <th style="width: 16%">Account <span class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 16%">Description</th>
                                                        <th style="width: 8%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 10%">Unit Price <span class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 6%">VAT</th>
                                                        <th style="width: 6%">WTax</th>
                                                        <th style="width: 10%">Amount</th>
                                                        <th style="width: 8%">Project</th>
                                                        <th style="width: 8%">Fund</th>
                                                        <th style="width: 8%">Dept</th>
                                                        <th style="width: 4%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lines">
                                                    <tr class="line-item">
                                                        <td>
                                                            <select name="lines[0][account_id]"
                                                                class="form-control form-control-sm select2bs4" required>
                                                                @foreach ($accounts as $a)
                                                                    <option value="{{ $a->id }}">
                                                                        {{ $a->code }} - {{ $a->name }}</option>
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
                                                            <select name="lines[0][fund_id]"
                                                                class="form-control form-control-sm select2bs4">
                                                                <option value="">-- none --</option>
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
                                                        <th colspan="3" class="text-right">Original Amount:</th>
                                                        <th class="text-right" id="original-amount">0.00</th>
                                                        <th class="text-right" id="total-vat">0.00</th>
                                                        <th class="text-right" id="total-wtax">0.00</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
                                                        <th colspan="4"></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Amount Due:</th>
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
            row.innerHTML = `
                <td>
                    <select name="lines[${idx}][account_id]" class="form-control form-control-sm select2bs4" required>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
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
                    <select name="lines[${idx}][fund_id]" class="form-control form-control-sm select2bs4">
                        <option value="">-- none --</option>
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
    </script>
@endpush
