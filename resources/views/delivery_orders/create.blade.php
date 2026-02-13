@extends('layouts.main')

@section('title', 'Create Delivery Order')

@section('title_page')
    Create Delivery Order
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('delivery-orders.index') }}">Delivery Orders</a></li>
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
                                New Delivery Order
                            </h3>
                            <a href="{{ route('delivery-orders.index') }}" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Delivery Orders
                            </a>
                        </div>
                        <form method="post" action="{{ route('delivery-orders.store') }}">
                            @csrf
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Sales Order <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                @if(count($salesOrders) == 0)
                                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                                        <strong>No Sales Orders Available:</strong> There are no approved and confirmed item-type Sales Orders available. You can still create a Delivery Order manually by selecting a customer below.
                                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                @endif
                                                <div class="input-group input-group-sm">
                                                    <select name="sales_order_id"
                                                        class="form-control form-control-sm select2bs4" id="sales_order_id"
                                                        {{ count($salesOrders) > 0 ? 'required' : '' }}>
                                                        <option value="">-- select sales order --</option>
                                                        @foreach ($salesOrders as $so)
                                                            @php
                                                                $customer = $so->customer ?? $so->businessPartner ?? null;
                                                                $deliveryAddr = $so->delivery_address ?? ($customer?->default_shipping_address ?? '');
                                                                $deliveryContact = $so->delivery_contact_person ?? ($customer?->primary_contact_name ?? '');
                                                                $deliveryPhone = $so->delivery_phone ?? ($customer?->primary_contact_phone ?? '');
                                                            @endphp
                                                            <option value="{{ $so->id }}"
                                                                {{ $salesOrder && $salesOrder->id == $so->id ? 'selected' : '' }}
                                                                data-customer-id="{{ $so->business_partner_id }}"
                                                                data-customer-name="{{ $customer ? $customer->name : 'N/A' }}"
                                                                data-customer-address="{{ $deliveryAddr }}"
                                                                data-customer-contact="{{ $deliveryContact }}"
                                                                data-customer-phone="{{ $deliveryPhone }}"
                                                                data-expected-delivery="{{ $so->expected_delivery_date ?? '' }}">
                                                                {{ $so->order_no }} - {{ $customer ? $customer->name : 'N/A' }}
                                                                ({{ $so->date }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append">
                                                        <button type="button" id="copy-lines-btn"
                                                            class="btn btn-sm btn-success"
                                                            {{ $salesOrder ? '' : 'disabled' }}>
                                                            <i class="fas fa-copy"></i> Copy Lines
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Customer <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="business_partner_id" id="customer-select"
                                                    class="form-control form-control-sm select2bs4" required>
                                                    <option value="">-- select customer --</option>
                                                    @foreach ($customers as $c)
                                                        <option value="{{ $c->id }}"
                                                            {{ old('business_partner_id', $salesOrder ? $salesOrder->business_partner_id : '') == $c->id ? 'selected' : '' }}
                                                            data-address="{{ e($c->default_shipping_address ?? '') }}"
                                                            data-contact="{{ e($c->primary_contact_name ?? '') }}"
                                                            data-phone="{{ e($c->primary_contact_phone ?? '') }}">
                                                            {{ $c->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="warehouse_id" id="warehouse-select"
                                                    class="form-control form-control-sm select2bs4" required>
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
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Planned Delivery <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input type="date" name="planned_delivery_date"
                                                        value="{{ old('planned_delivery_date', $salesOrder ? $salesOrder->expected_delivery_date : '') }}"
                                                        class="form-control" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Delivery Address <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <textarea name="delivery_address" class="form-control form-control-sm" rows="3" required>{{ old('delivery_address', $salesOrder ? ($salesOrder->delivery_address ?? $salesOrder->customer?->default_shipping_address) : '') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Contact Person</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="delivery_contact_person"
                                                    value="{{ old('delivery_contact_person', $salesOrder ? ($salesOrder->delivery_contact_person ?? $salesOrder->customer?->primary_contact_name) : '') }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Phone</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="delivery_phone"
                                                    value="{{ old('delivery_phone', $salesOrder ? ($salesOrder->delivery_phone ?? $salesOrder->customer?->primary_contact_phone) : '') }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Delivery Method <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <select name="delivery_method" class="form-control form-control-sm"
                                                    required>
                                                    <option value="own_fleet"
                                                        {{ old('delivery_method', 'own_fleet') == 'own_fleet' ? 'selected' : '' }}>
                                                        Own Fleet</option>
                                                    <option value="courier"
                                                        {{ old('delivery_method') == 'courier' ? 'selected' : '' }}>Courier
                                                    </option>
                                                    <option value="pickup"
                                                        {{ old('delivery_method') == 'pickup' ? 'selected' : '' }}>Customer
                                                        Pickup</option>
                                                    <option value="customer_pickup"
                                                        {{ old('delivery_method') == 'customer_pickup' ? 'selected' : '' }}>
                                                        Customer Pickup</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Logistics Cost</label>
                                            <div class="col-sm-9">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp</span>
                                                    </div>
                                                    <input type="number" step="0.01" min="0"
                                                        name="logistics_cost" value="{{ old('logistics_cost', 0) }}"
                                                        class="form-control form-control-sm text-right">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Delivery Instructions</label>
                                            <div class="col-sm-10">
                                                <textarea name="delivery_instructions" class="form-control form-control-sm" rows="2">{{ old('delivery_instructions') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Notes</label>
                                            <div class="col-sm-10">
                                                <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($salesOrder)
                                    <div class="card card-secondary card-outline mt-3 mb-2">
                                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                            <h3 class="card-title mb-0">
                                                <i class="fas fa-list-ul mr-1"></i>
                                                Sales Order Items
                                            </h3>
                                            <button type="button" id="copy-lines-table-btn" class="btn btn-sm btn-success">
                                                <i class="fas fa-copy"></i> Copy Lines
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped mb-0" id="do-lines-table">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center" style="width: 40px;">No</th>
                                                            <th>Item Code</th>
                                                            <th>Item Name</th>
                                                            <th class="text-right">Ordered Qty</th>
                                                            <th class="text-right">Remain Qty</th>
                                                            <th class="text-right" style="width: 120px;">Delivery Qty</th>
                                                            <th style="width: 50px;">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="do-lines-tbody">
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="5" class="text-right">Total Delivery Qty:</th>
                                                            <th class="text-right" id="do-lines-total">0.00</th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-1"></i> Create Delivery Order
                                        </button>
                                        <a href="{{ route('delivery-orders.index') }}" class="btn btn-default">
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
        var remainingLinesData = @json($remainingLines ?? []);
        $(document).ready(function() {
            // Ensure sales order field is enabled
            $('#sales_order_id').prop('disabled', false);
            
            // Store selected customer ID for filtering
            var selectedCustomerIdForFilter = null;
            
            // Initialize Select2BS4 for customer dropdown
            $('#customer-select').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });
            
            // Initialize Select2BS4 for sales order with custom templateResult for filtering
            $('#sales_order_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true,
                templateResult: function(data) {
                    // Handle placeholder option
                    if (!data.id) {
                        return data.text;
                    }
                    
                    // If a customer is selected, filter sales orders
                    if (selectedCustomerIdForFilter && data.element) {
                        var $option = $(data.element);
                        var optionCustomerId = $option.data('customer-id');
                        // Hide options that don't match the selected customer
                        if (optionCustomerId && optionCustomerId != selectedCustomerIdForFilter) {
                            return null; // This hides the option in Select2 dropdown
                        }
                    }
                    
                    return data.text;
                }
            });
            
            // Initialize other Select2 fields
            $('.select2bs4').not('#sales_order_id').not('#customer-select').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });
            
            // Ensure Select2 doesn't disable the field
            $('#sales_order_id').on('select2:open', function() {
                $(this).prop('disabled', false);
            });
            
            // Initialize filter on page load if customer is already selected
            if ($('#customer-select').val()) {
                selectedCustomerIdForFilter = $('#customer-select').val();
            }

            // Auto-fill customer details when sales order is selected
            $('#sales_order_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    // Set customer dropdown to match the sales order's customer
                    var customerId = selectedOption.data('customer-id');
                    if (customerId) {
                        $('#customer-select').val(customerId).trigger('change');
                    }

                    // Fill in delivery details
                    $('textarea[name="delivery_address"]').val(selectedOption.data('customer-address'));
                    $('input[name="delivery_contact_person"]').val(selectedOption.data('customer-contact'));
                    $('input[name="delivery_phone"]').val(selectedOption.data('customer-phone'));
                    $('input[name="planned_delivery_date"]').val(selectedOption.data('expected-delivery'));

                    // Enable copy lines button
                    $('#copy-lines-btn').prop('disabled', false);
                } else {
                    // Disable copy lines button if no sales order selected
                    $('#copy-lines-btn').prop('disabled', true);
                }
            });

            // Filter Sales Orders and auto-fill delivery details when customer is selected
            $('#customer-select').on('change', function() {
                var selectedCustomerId = $(this).val();
                var selectedOption = $(this).find('option:selected');
                var $salesOrderSelect = $('#sales_order_id');
                var currentSelectedValue = $salesOrderSelect.val();
                
                // Update the filter variable for Select2 templateResult
                selectedCustomerIdForFilter = selectedCustomerId;
                
                // Filter Sales Order dropdown based on selected customer
                if (selectedCustomerId) {
                    // If currently selected SO doesn't belong to this customer, clear selection
                    if (currentSelectedValue) {
                        var selectedSOOption = $salesOrderSelect.find('option[value="' + currentSelectedValue + '"]');
                        if (selectedSOOption.length && selectedSOOption.data('customer-id') != selectedCustomerId) {
                            $salesOrderSelect.val('').trigger('change');
                            $('#copy-lines-btn').prop('disabled', true);
                        }
                    }
                    
                    // Force Select2 to refresh by closing and reopening (if open)
                    // The templateResult will filter options when dropdown opens
                    if ($salesOrderSelect.data('select2')) {
                        $salesOrderSelect.select2('close');
                    }
                    
                    // Only update delivery details if no sales order is selected or fields are empty
                    if (!$('#sales_order_id').val() || !$('textarea[name="delivery_address"]').val()) {
                        $('textarea[name="delivery_address"]').val(selectedOption.data('address'));
                        $('input[name="delivery_contact_person"]').val(selectedOption.data('contact'));
                        $('input[name="delivery_phone"]').val(selectedOption.data('phone'));
                    }
                } else {
                    // If no customer selected, show all sales orders
                    selectedCustomerIdForFilter = null;
                    if ($salesOrderSelect.data('select2')) {
                        $salesOrderSelect.select2('close');
                    }
                }
            });

            // Handle Copy Lines button (next to SO dropdown) - redirect
            $('#copy-lines-btn').on('click', function() {
                var salesOrderId = $('#sales_order_id').val();
                if (!salesOrderId) {
                    toastr.error('Please select a Sales Order first');
                    return;
                }
                window.location.href = "{{ route('delivery-orders.create') }}?sales_order_id=" + salesOrderId;
            });

            function renderDoLinesTable() {
                var tbody = $('#do-lines-tbody');
                tbody.empty();
                var total = 0;
                remainingLinesData.forEach(function(line, idx) {
                    var qty = parseFloat(line.remaining_qty) || 0;
                    if (qty > parseFloat(line.max_qty || 0)) {
                        qty = parseFloat(line.max_qty) || 0;
                    }
                    total += qty;
                    var row = '<tr data-sol-id="' + line.sales_order_line_id + '">' +
                        '<td class="text-center">' + (idx + 1) + '</td>' +
                        '<td>' + (line.item_code || 'N/A') + '</td>' +
                        '<td>' + (line.item_name || 'N/A') + '</td>' +
                        '<td class="text-right">' + parseFloat(line.ordered_qty || 0).toFixed(2) + '</td>' +
                        '<td class="text-right">' + parseFloat(line.remaining_qty || 0).toFixed(2) + '</td>' +
                        '<td class="text-right">' +
                        '<input type="number" step="0.01" min="0" max="' + line.max_qty + '" ' +
                        'name="lines[' + idx + '][qty]" value="' + qty + '" ' +
                        'class="form-control form-control-sm text-right do-line-qty" ' +
                        'data-max="' + line.max_qty + '" style="width: 90px;">' +
                        '<input type="hidden" name="lines[' + idx + '][sales_order_line_id]" value="' + line.sales_order_line_id + '">' +
                        '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-danger do-line-delete" title="Delete row"><i class="fas fa-trash"></i></button></td>' +
                        '</tr>';
                    tbody.append(row);
                });
                reindexLineInputs();
                $('#do-lines-total').text(total.toFixed(2));
            }

            function reindexLineInputs() {
                $('#do-lines-tbody tr').each(function(idx) {
                    $(this).find('input[name*="[qty]"]').attr('name', 'lines[' + idx + '][qty]');
                    $(this).find('input[name*="[sales_order_line_id]"]').attr('name', 'lines[' + idx + '][sales_order_line_id]');
                });
            }

            function updateTotalQty() {
                var total = 0;
                $('.do-line-qty').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#do-lines-total').text(total.toFixed(2));
            }

            $('#copy-lines-table-btn').on('click', function() {
                renderDoLinesTable();
                if (remainingLinesData.length > 0) {
                    toastr.success('Remaining items loaded. You can edit qty or delete rows.');
                } else {
                    toastr.info('No remaining items to deliver for this Sales Order.');
                }
            });

            $(document).on('click', '.do-line-delete', function() {
                $(this).closest('tr').remove();
                reindexLineInputs();
                updateTotalQty();
            });

            $(document).on('input', '.do-line-qty', function() {
                var $input = $(this);
                var max = parseFloat($input.data('max')) || 999999;
                var val = parseFloat($input.val()) || 0;
                if (val > max) {
                    $input.val(max);
                } else if (val < 0) {
                    $input.val(0);
                }
                updateTotalQty();
            });

            if (remainingLinesData.length > 0) {
                renderDoLinesTable();
            }
        });
    </script>
@endpush
