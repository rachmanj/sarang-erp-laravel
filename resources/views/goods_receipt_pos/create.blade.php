@extends('layouts.main')

@section('title', 'Create Goods Receipt PO')

@section('title_page')
    Create Goods Receipt PO
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('goods-receipt-pos.index') }}">Goods Receipt PO</a></li>
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
                                <i class="fas fa-truck mr-1"></i>
                                New Goods Receipt PO
                            </h3>
                            <a href="{{ route('goods-receipt-pos.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Goods Receipt PO
                            </a>
                        </div>
                        <form method="post" action="{{ route('goods-receipt-pos.store') }}" id="grpo-form">
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
                                            <label class="col-sm-3 col-form-label">Vendor <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id" id="vendor-select"
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
                                            <label class="col-sm-3 col-form-label">Purchase Order</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <select name="purchase_order_id" id="po-select"
                                                        class="form-control form-control-sm select2bs4" disabled>
                                                        <option value="">-- select vendor first --</option>
                                                    </select>
                                                    <div class="input-group-append">
                                                        <button type="button" id="copy-lines-btn" class="btn btn-sm btn-success" disabled>
                                                            <i class="fas fa-copy"></i> Copy Lines
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-secondary card-outline mt-3 mb-2">
                                    <div class="card-header py-2">
                                        <h3 class="card-title">
                                            <i class="fas fa-list-ul mr-1"></i>
                                            Receipt Lines
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
                                                        <th style="width: 24%">Account <span class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 28%">Description</th>
                                                        <th style="width: 12%">Qty <span class="text-danger">*</span></th>
                                                        <th style="width: 16%">Unit Price <span class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 12%">Tax</th>
                                                        <th style="width: 8%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Total:</th>
                                                        <th class="text-right" id="total-amount">0.00</th>
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
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-1"></i> Save GRPO
                                        </button>
                                        <a href="{{ route('goods-receipt-pos.index') }}" class="btn btn-default">
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
                updateTotalAmount();
            });

            // Update total when unit price or quantity changes
            $(document).on('input', '.qty-input, .price-input', function() {
                updateTotalAmount();
            });

            // Vendor selection handler - load POs for selected vendor
            $('#vendor-select').on('change', function() {
                const vendorId = $(this).val();
                const $poSelect = $('#po-select');
                const $copyBtn = $('#copy-lines-btn');
                
                if (vendorId) {
                    // Enable PO select and load vendor's POs
                    $poSelect.prop('disabled', false);
                    $poSelect.empty().append('<option value="">-- loading POs --</option>');
                    
                    $.get('{{ route("goods-receipt-pos.vendor-pos") }}', { business_partner_id: vendorId })
                        .done(function(data) {
                            $poSelect.empty().append('<option value="">-- select PO --</option>');
                            
                            if (data.purchase_orders.length > 0) {
                                $.each(data.purchase_orders, function(index, po) {
                                    $poSelect.append(`<option value="${po.id}">${po.order_no} (${po.date}) - ${po.remaining_lines_count} lines</option>`);
                                });
                            } else {
                                $poSelect.append('<option value="">-- no open POs found --</option>');
                            }
                        })
                        .fail(function() {
                            $poSelect.empty().append('<option value="">-- error loading POs --</option>');
                        });
                } else {
                    // Disable PO select and copy button
                    $poSelect.prop('disabled', true).empty().append('<option value="">-- select vendor first --</option>');
                    $copyBtn.prop('disabled', true);
                }
            });

            // PO selection handler - enable copy button
            $('#po-select').on('change', function() {
                const poId = $(this).val();
                const $copyBtn = $('#copy-lines-btn');
                
                if (poId) {
                    $copyBtn.prop('disabled', false);
                } else {
                    $copyBtn.prop('disabled', true);
                }
            });

            // Copy remaining lines button handler
            $('#copy-lines-btn').on('click', function() {
                const poId = $('#po-select').val();
                
                if (!poId) {
                    alert('Please select a Purchase Order first');
                    return;
                }
                
                // Confirm before copying
                if (confirm('This will copy all remaining lines from the selected PO. Existing lines will be replaced. Continue?')) {
                    $.get('{{ route("goods-receipt-pos.remaining-lines") }}', { purchase_order_id: poId })
                        .done(function(data) {
                            if (data.lines.length > 0) {
                                // Clear existing lines
                                $tb.empty();
                                i = 0;
                                
                                // Add copied lines
                                $.each(data.lines, function(index, line) {
                                    addLineRow({
                                        account_id: line.account_id,
                                        description: line.description,
                                        qty: line.qty,
                                        unit_price: line.unit_price,
                                        tax_code_id: line.tax_code_id
                                    });
                                });
                                
                                updateTotalAmount();
                                alert(`Copied ${data.lines.length} lines from PO`);
                            } else {
                                alert('No remaining lines found in the selected PO');
                            }
                        })
                        .fail(function() {
                            alert('Error loading PO lines');
                        });
                }
            });

            // Handle prefill data if available
            if (window.prefill) {
                $tb.empty();
                i = 0;
                $('[name=date]').val(window.prefill.date);
                $('[name=business_partner_id]').val(window.prefill.business_partner_id);
                $('#vendor-select').trigger('change'); // Trigger vendor change to load POs
                
                // Wait for POs to load, then set the selected PO
                setTimeout(function() {
                    $('[name=purchase_order_id]').val(window.prefill.purchase_order_id);
                    $('#po-select').trigger('change');
                }, 1000);

                if (window.prefill.lines && window.prefill.lines.length > 0) {
                    window.prefill.lines.forEach(function(l) {
                        addLineRow(l);
                    });
                } else {
                    addLineRow();
                }

                updateTotalAmount();
            }

            function addLineRow(data = {}) {
                const lineIdx = i++;
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td>
                        <select name="lines[${lineIdx}][account_id]" class="form-control form-control-sm select2bs4" required>
                            <option value="">-- select account --</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" ${data.account_id == {{ $a->id }} ? 'selected' : ''}>
                                    {{ $a->code }} - {{ $a->name }}
                                </option>
                            @endforeach
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
                        <select name="lines[${lineIdx}][tax_code_id]" class="form-control form-control-sm select2bs4">
                            <option value="">-- none --</option>
                            @foreach ($taxCodes as $t)
                                <option value="{{ $t->id }}" ${data.tax_code_id == {{ $t->id }} ? 'selected' : ''}>
                                    {{ $t->code }}
                                </option>
                            @endforeach
                        </select>
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

                updateTotalAmount();
            }

            function updateTotalAmount() {
                let total = 0;

                // Calculate total from all line items
                $('#lines tbody tr').each(function() {
                    const qty = parseFloat($(this).find('.qty-input').val() || 0);
                    const price = parseFloat($(this).find('.price-input').val() || 0);
                    total += qty * price;
                });

                // Update total display with Indonesian number formatting
                $('#total-amount').text(total.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }
        });
    </script>
@endpush