@extends('layouts.main')

@section('title', 'Create Purchase Payment')

@section('title_page')
    Create Purchase Payment
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-payments.index') }}">Purchase Payments</a></li>
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
                                <i class="fas fa-credit-card mr-1"></i>
                                New Purchase Payment
                            </h3>
                            <a href="{{ route('purchase-payments.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Purchase Payments
                            </a>
                        </div>
                        <form method="post" action="{{ route('purchase-payments.store') }}" id="payment-form">
                            @csrf
                            <div class="card-body pb-1">
                                <!-- Basic Information Section -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Date <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" name="date" id="payment_date"
                                                        value="{{ old('date', now()->toDateString()) }}"
                                                        class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Payment No</label>
                                            <div class="col-sm-8">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                    </div>
                                                    <input type="text" id="payment_no_preview" class="form-control bg-light" readonly
                                                        placeholder="Will be assigned on save">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary" id="preview-payment-number" title="Preview next number (does not consume)">
                                                            <i class="fas fa-eye"></i> Preview
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Number is generated when you save. Preview shows next number without consuming it.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Company <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-8">
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
                                    <div class="col-md-3">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Vendor <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-8">
                                                <select name="business_partner_id" id="business_partner_id"
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
                                    <div class="col-md-3">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Description</label>
                                            <div class="col-sm-8">
                                                <input type="text" name="description" value="{{ old('description') }}"
                                                    class="form-control form-control-sm" placeholder="Payment description">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Invoice Selection Section -->
                                <div class="card card-success card-outline mt-3 mb-2" id="invoice-selection-card"
                                    style="display: none;">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-file-invoice mr-1"></i>
                                            Select Invoices to Pay
                                        </h3>
                                        <div class="float-right">
                                            <button type="button" class="btn btn-xs btn-info"
                                                onclick="selectAllInvoices()">
                                                <i class="fas fa-check-square"></i> Select All
                                            </button>
                                            <button type="button" class="btn btn-xs btn-secondary"
                                                onclick="deselectAllInvoices()">
                                                <i class="fas fa-square"></i> Deselect All
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0" id="invoices-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 5%">
                                                            <input type="checkbox" id="select-all-checkbox"
                                                                onchange="toggleAllInvoices()">
                                                        </th>
                                                        <th style="width: 12%">Invoice #</th>
                                                        <th style="width: 10%">Date</th>
                                                        <th style="width: 10%">Due Date</th>
                                                        <th style="width: 12%" class="text-right">Total Amount</th>
                                                        <th style="width: 12%" class="text-right">Allocated</th>
                                                        <th style="width: 12%" class="text-right">Remaining</th>
                                                        <th style="width: 15%" class="text-right">Allocation Amount <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 10%">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="invoices-tbody">
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted">
                                                            <i class="fas fa-info-circle"></i> Select a vendor to load
                                                            available invoices
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="7" class="text-right">Total Allocation:</th>
                                                        <th class="text-right" id="total-allocation">0.00</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Lines Section -->
                                <div class="card card-secondary card-outline mt-3 mb-2" id="payment-lines-card"
                                    style="display: none;">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Payment Lines
                                        </h3>
                                        <small class="text-muted float-right mt-2">
                                            <span id="validation-message" class="text-danger"></span>
                                        </small>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0" id="lines-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 60%">Bank/Cash Account <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 25%" class="text-right">Amount <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 10%">Notes</th>
                                                        <th style="width: 5%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="lines">
                                                    <!-- Will be auto-populated -->
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th class="text-right">Total Payment:</th>
                                                        <th class="text-right" id="total-payment">0.00</th>
                                                        <th colspan="2"></th>
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
                                        <button class="btn btn-primary" type="submit" id="submit-btn" disabled>
                                            <i class="fas fa-save mr-1"></i> Save Payment
                                        </button>
                                        <a href="{{ route('purchase-payments.index') }}" class="btn btn-default">
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
        let availableInvoices = [];
        let selectedInvoices = new Set();
        let allocationIndex = 0;

        $(document).ready(function() {
            // Initialize Select2BS4
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            function updateDocumentNumber() {
                const entityId = $('#company_entity_id').val();
                const date = $('#payment_date').val() || new Date().toISOString().slice(0, 10);
                if (!entityId) return;
                $.ajax({
                    url: '{{ route('purchase-payments.api.document-number') }}',
                    method: 'GET',
                    data: { company_entity_id: entityId, date: date },
                    success: function(response) {
                        if (response.document_number) {
                            $('#payment_no_preview').val(response.document_number);
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
            $('#payment_date').on('change', updateDocumentNumber);
            $('#preview-payment-number').on('click', updateDocumentNumber);
            updateDocumentNumber();

            // Load invoices when vendor is selected
            $('#business_partner_id').on('change', function() {
                const vendorId = $(this).val();
                if (vendorId) {
                    loadAvailableInvoices(vendorId);
                } else {
                    hideInvoiceSelection();
                }
            });

            // Real-time validation
            $(document).on('input', '.allocation-amount-input', function() {
                validateAllocation($(this));
                updateTotals();
                validateForm();
            });

            $(document).on('input', '.payment-amount-input', function() {
                updateTotals();
                validateForm();
            });

            // Remove payment line
            $(document).on('click', '.remove-payment-line', function() {
                $(this).closest('tr').remove();
                updateTotals();
                validateForm();
            });
        });

        async function loadAvailableInvoices(vendorId) {
            try {
                const response = await fetch(
                    `{{ route('purchase-payments.availableInvoices') }}?business_partner_id=${vendorId}`
                );
                const data = await response.json();
                availableInvoices = data.invoices || [];
                renderInvoiceTable();
                showInvoiceSelection();
            } catch (error) {
                console.error('Error loading invoices:', error);
                toastr.error('Failed to load invoices. Please try again.');
            }
        }

        function renderInvoiceTable() {
            const tbody = $('#invoices-tbody');
            tbody.empty();
            allocationIndex = 0;
            selectedInvoices.clear();

            if (availableInvoices.length === 0) {
                tbody.html(
                    '<tr><td colspan="9" class="text-center text-muted"><i class="fas fa-info-circle"></i> No outstanding invoices found for this vendor</td></tr>'
                );
                return;
            }

            availableInvoices.forEach((invoice) => {
                const isOverdue = invoice.is_overdue;
                const rowClass = isOverdue ? 'table-warning' : '';
                const row = `
                    <tr class="${rowClass}" data-invoice-id="${invoice.id}">
                        <td>
                            <input type="checkbox" class="invoice-checkbox" 
                                data-invoice-id="${invoice.id}" 
                                onchange="toggleInvoiceSelection(${invoice.id})">
                        </td>
                        <td>${invoice.invoice_no}</td>
                        <td>${formatDate(invoice.date)}</td>
                        <td>${invoice.due_date ? formatDate(invoice.due_date) : '-'}</td>
                        <td class="text-right">${formatCurrency(invoice.total_amount)}</td>
                        <td class="text-right">${formatCurrency(invoice.allocated_amount)}</td>
                        <td class="text-right"><strong>${formatCurrency(invoice.remaining_balance)}</strong></td>
                        <td>
                            <input type="number" 
                                step="0.01" 
                                min="0" 
                                max="${invoice.remaining_balance}"
                                class="form-control form-control-sm text-right allocation-amount-input" 
                                data-invoice-id="${invoice.id}"
                                data-max="${invoice.remaining_balance}"
                                name=""
                                value="0"
                                disabled
                                style="display: none;">
                            <input type="hidden" class="allocation-invoice-id" data-invoice-id="${invoice.id}" name="" value="${invoice.id}">
                        </td>
                        <td>
                            ${isOverdue ? `<span class="badge badge-warning">Overdue ${invoice.days_overdue} days</span>` : '<span class="badge badge-success">Current</span>'}
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function toggleInvoiceSelection(invoiceId) {
            const checkbox = $(`.invoice-checkbox[data-invoice-id="${invoiceId}"]`);
            const row = checkbox.closest('tr');
            const allocationInput = row.find('.allocation-amount-input');
            const hiddenInput = row.find('.allocation-invoice-id[data-invoice-id="' + invoiceId + '"]');

            if (checkbox.is(':checked')) {
                selectedInvoices.add(invoiceId);
                allocationInput.show().prop('disabled', false);
                const invoice = availableInvoices.find(inv => inv.id === invoiceId);
                allocationInput.val(invoice.remaining_balance);

                // Assign sequential index
                const currentIndex = allocationIndex++;
                allocationInput.attr('name', `allocations[${currentIndex}][amount]`);
                hiddenInput.attr('name', `allocations[${currentIndex}][invoice_id]`);
            } else {
                selectedInvoices.delete(invoiceId);
                allocationInput.hide().prop('disabled', true).val(0);
                allocationInput.attr('name', '');
                hiddenInput.attr('name', '');

                // Rebuild indices to ensure sequential numbering
                rebuildAllocationIndices();
            }

            updateTotals();
            updatePaymentLine();
            validateForm();
        }

        function rebuildAllocationIndices() {
            allocationIndex = 0;
            $('.invoice-checkbox:checked').each(function() {
                const invoiceId = $(this).data('invoice-id');
                const row = $(this).closest('tr');
                const allocationInput = row.find('.allocation-amount-input');
                const hiddenInput = row.find('.allocation-invoice-id[data-invoice-id="' + invoiceId + '"]');

                allocationInput.attr('name', `allocations[${allocationIndex}][amount]`);
                hiddenInput.attr('name', `allocations[${allocationIndex}][invoice_id]`);
                allocationIndex++;
            });
        }

        function selectAllInvoices() {
            $('.invoice-checkbox').each(function() {
                if (!$(this).is(':checked')) {
                    $(this).prop('checked', true).trigger('change');
                }
            });
        }

        function deselectAllInvoices() {
            $('.invoice-checkbox').each(function() {
                if ($(this).is(':checked')) {
                    $(this).prop('checked', false).trigger('change');
                }
            });
        }

        function toggleAllInvoices() {
            const selectAll = $('#select-all-checkbox').is(':checked');
            if (selectAll) {
                selectAllInvoices();
            } else {
                deselectAllInvoices();
            }
        }

        function validateAllocation(input) {
            const max = parseFloat(input.data('max'));
            const value = parseFloat(input.val() || 0);

            if (value > max) {
                input.val(max);
                toastr.warning(`Allocation amount cannot exceed remaining balance of ${formatCurrency(max)}`);
            }

            if (value < 0) {
                input.val(0);
            }
        }

        function updateTotals() {
            let totalAllocation = 0;

            $('.allocation-amount-input:visible').each(function() {
                const amount = parseFloat($(this).val() || 0);
                totalAllocation += amount;
            });

            $('#total-allocation').text(formatCurrency(totalAllocation));

            // Update payment line total
            updatePaymentLineAmount(totalAllocation);
        }

        function updatePaymentLine() {
            const totalAllocation = parseFloat($('#total-allocation').text().replace(/[^\d.-]/g, '') || 0);

            if (totalAllocation > 0 && selectedInvoices.size > 0) {
                showPaymentLines();
                updatePaymentLineAmount(totalAllocation);
            } else {
                hidePaymentLines();
            }
        }

        function updatePaymentLineAmount(amount) {
            const linesTbody = $('#lines');
            const paymentAmountInput = linesTbody.find('.payment-amount-input');

            if (paymentAmountInput.length === 0) {
                // Create payment line if it doesn't exist
                createPaymentLine(amount);
            } else {
                // Update existing payment line
                paymentAmountInput.val(amount.toFixed(2));
            }

            updatePaymentTotal();
        }

        function createPaymentLine(amount) {
            const linesTbody = $('#lines');
            const accounts = @json($accounts);
            let accountOptions = '';
            accounts.forEach(account => {
                accountOptions += `<option value="${account.id}">${account.code} - ${account.name}</option>`;
            });

            const row = `
                <tr class="payment-line-item">
                    <td>
                        <select name="lines[0][account_id]" class="form-control form-control-sm select2bs4" required>
                            ${accountOptions}
                        </select>
                    </td>
                    <td>
                        <input type="number" 
                            step="0.01" 
                            min="0.01" 
                            name="lines[0][amount]" 
                            class="form-control form-control-sm text-right payment-amount-input" 
                            value="${amount.toFixed(2)}" 
                            required>
                    </td>
                    <td>
                        <input type="text" 
                            name="lines[0][description]" 
                            class="form-control form-control-sm" 
                            placeholder="Notes">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-danger remove-payment-line">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
            linesTbody.html(row);

            // Initialize Select2BS4 for the new select
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });
        }

        function updatePaymentTotal() {
            let total = 0;
            $('.payment-amount-input').each(function() {
                total += parseFloat($(this).val() || 0);
            });
            $('#total-payment').text(formatCurrency(total));
        }

        function validateForm() {
            const totalAllocation = parseFloat($('#total-allocation').text().replace(/[^\d.-]/g, '') || 0);
            const totalPayment = parseFloat($('#total-payment').text().replace(/[^\d.-]/g, '') || 0);
            const diff = Math.abs(totalAllocation - totalPayment);
            const validationMsg = $('#validation-message');
            const submitBtn = $('#submit-btn');

            if (selectedInvoices.size === 0) {
                validationMsg.text('Please select at least one invoice to pay');
                submitBtn.prop('disabled', true);
                return false;
            }

            if (totalAllocation === 0) {
                validationMsg.text('Please enter allocation amounts for selected invoices');
                submitBtn.prop('disabled', true);
                return false;
            }

            if (diff > 0.01) {
                validationMsg.text(
                    `Payment total (${formatCurrency(totalPayment)}) must match allocation total (${formatCurrency(totalAllocation)})`
                    );
                submitBtn.prop('disabled', true);
                return false;
            }

            validationMsg.text('');
            submitBtn.prop('disabled', false);
            return true;
        }

        function showInvoiceSelection() {
            $('#invoice-selection-card').slideDown();
        }

        function hideInvoiceSelection() {
            $('#invoice-selection-card').slideUp();
            $('#invoices-tbody').html(
                '<tr><td colspan="9" class="text-center text-muted"><i class="fas fa-info-circle"></i> Select a vendor to load available invoices</td></tr>'
            );
            selectedInvoices.clear();
        }

        function showPaymentLines() {
            $('#payment-lines-card').slideDown();
        }

        function hidePaymentLines() {
            $('#payment-lines-card').slideUp();
            $('#lines').empty();
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        // Form submission validation
        $('#payment-form').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                toastr.error('Please fix the validation errors before submitting');
                return false;
            }

            // Clean up unused allocation inputs before submission
            $('.allocation-amount-input[disabled]').closest('tr').find('.allocation-invoice-id').remove();
            $('.allocation-amount-input[disabled]').remove();

            // Ensure sequential indices
            rebuildAllocationIndices();
        });

        // Initialize if vendor is pre-selected
        @if (old('business_partner_id'))
            $('#business_partner_id').trigger('change');
        @endif
    </script>
@endpush
