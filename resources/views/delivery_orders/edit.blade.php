@extends('layouts.main')

@section('title', 'Edit Delivery Order')

@section('title_page')
    Edit Delivery Order
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('delivery-orders.index') }}">Delivery Orders</a></li>
    <li class="breadcrumb-item"><a
            href="{{ route('delivery-orders.show', $deliveryOrder) }}">{{ $deliveryOrder->do_number }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit mr-1"></i>
                                Edit Delivery Order: {{ $deliveryOrder->do_number }}
                            </h3>
                            <a href="{{ route('delivery-orders.show', $deliveryOrder) }}"
                                class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                        <form method="post" action="{{ route('delivery-orders.update', $deliveryOrder) }}">
                            @csrf
                            @method('PATCH')
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Sales Order</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control form-control-sm"
                                                    value="{{ $deliveryOrder->salesOrder->order_no }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Customer</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control form-control-sm"
                                                    value="{{ $deliveryOrder->customer->name }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse</label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control form-control-sm"
                                                    value="{{ $deliveryOrder->warehouse->name ?? 'N/A' }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Planned Delivery <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="date" name="planned_delivery_date"
                                                    value="{{ old('planned_delivery_date', $deliveryOrder->planned_delivery_date->format('Y-m-d')) }}"
                                                    class="form-control" required>
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
                                                <textarea name="delivery_address" class="form-control form-control-sm" rows="3" required>{{ old('delivery_address', $deliveryOrder->delivery_address) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Contact Person</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="delivery_contact_person"
                                                    value="{{ old('delivery_contact_person', $deliveryOrder->delivery_contact_person) }}"
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
                                                    value="{{ old('delivery_phone', $deliveryOrder->delivery_phone) }}"
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
                                                        {{ old('delivery_method', $deliveryOrder->delivery_method) == 'own_fleet' ? 'selected' : '' }}>
                                                        Own Fleet</option>
                                                    <option value="courier"
                                                        {{ old('delivery_method', $deliveryOrder->delivery_method) == 'courier' ? 'selected' : '' }}>
                                                        Courier</option>
                                                    <option value="pickup"
                                                        {{ old('delivery_method', $deliveryOrder->delivery_method) == 'pickup' ? 'selected' : '' }}>
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
                                                <input type="number" step="0.01" min="0" name="logistics_cost"
                                                    value="{{ old('logistics_cost', $deliveryOrder->logistics_cost) }}"
                                                    class="form-control form-control-sm text-right">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Delivery Instructions</label>
                                            <div class="col-sm-10">
                                                <textarea name="delivery_instructions" class="form-control form-control-sm" rows="2">{{ old('delivery_instructions', $deliveryOrder->delivery_instructions) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Notes</label>
                                            <div class="col-sm-10">
                                                <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes', $deliveryOrder->notes) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delivery Items Table -->
                                @if ($deliveryOrder->lines && $deliveryOrder->lines->count() > 0)
                                    <div class="card card-secondary card-outline mt-3 mb-2">
                                        <div class="card-header py-2">
                                            <h3 class="card-title">
                                                <i class="fas fa-list-ul mr-1"></i>
                                                Delivery Items
                                            </h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped mb-0" id="delivery-items-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Item Code</th>
                                                            <th>Item Name</th>
                                                            <th class="text-right">Ordered Qty</th>
                                                            <th class="text-right">Unit Price</th>
                                                            <th class="text-right">Amount</th>
                                                            <th>VAT</th>
                                                            <th>WTax %</th>
                                                            <th>Description</th>
                                                            <th>Notes</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($deliveryOrder->lines as $index => $line)
                                                            <tr>
                                                                <td>
                                                                    {{ $line->item_code ?? ($line->inventoryItem->code ?? 'N/A') }}
                                                                    <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                                                    <input type="hidden" name="lines[{{ $index }}][sales_order_line_id]" value="{{ $line->sales_order_line_id }}">
                                                                    <input type="hidden" name="lines[{{ $index }}][inventory_item_id]" value="{{ $line->inventory_item_id }}">
                                                                </td>
                                                                <td>{{ $line->item_name ?? ($line->inventoryItem->name ?? ($line->description ?? 'N/A')) }}</td>
                                                                <td class="text-right">
                                                                    <input type="number" 
                                                                           name="lines[{{ $index }}][ordered_qty]" 
                                                                           class="form-control form-control-sm text-right qty-input" 
                                                                           value="{{ old('lines.'.$index.'.ordered_qty', $line->ordered_qty) }}" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           required
                                                                           data-line-index="{{ $index }}">
                                                                </td>
                                                                <td class="text-right">
                                                                    <input type="number" 
                                                                           name="lines[{{ $index }}][unit_price]" 
                                                                           class="form-control form-control-sm text-right price-input" 
                                                                           value="{{ old('lines.'.$index.'.unit_price', $line->unit_price) }}" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           required
                                                                           data-line-index="{{ $index }}">
                                                                </td>
                                                                <td class="text-right">
                                                                    <span class="amount-display" data-line-index="{{ $index }}">
                                                                        {{ number_format($line->amount, 2) }}
                                                                    </span>
                                                                    <input type="hidden" name="lines[{{ $index }}][amount]" class="amount-input" value="{{ $line->amount }}" data-line-index="{{ $index }}">
                                                                </td>
                                                                <td class="text-muted">
                                                                    {{ $line->taxCode?->code ?? '—' }}
                                                                </td>
                                                                <td class="text-muted text-right">
                                                                    {{ optional($line->salesOrderLine)->wtax_rate ? number_format($line->salesOrderLine->wtax_rate, 2) . '%' : '—' }}
                                                                </td>
                                                                <td>
                                                                    <input type="text" 
                                                                           name="lines[{{ $index }}][description]" 
                                                                           class="form-control form-control-sm" 
                                                                           value="{{ old('lines.'.$index.'.description', $line->description) }}">
                                                                </td>
                                                                <td>
                                                                    <input type="text" 
                                                                           name="lines[{{ $index }}][notes]" 
                                                                           class="form-control form-control-sm" 
                                                                           value="{{ old('lines.'.$index.'.notes', $line->notes) }}">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="4" class="text-right">Total:</th>
                                                            <th class="text-right" id="total-amount">{{ number_format($deliveryOrder->total_amount, 2) }}</th>
                                                            <th colspan="4"></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save mr-1"></i> Update Delivery Order
                                </button>
                                <a href="{{ route('delivery-orders.show', $deliveryOrder) }}" class="btn btn-default">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
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
        $(document).ready(function() {
            // Calculate amount and total when qty or price changes
            function calculateAmount(lineIndex) {
                const row = $(`tr:has(input[data-line-index="${lineIndex}"])`);
                const qty = parseFloat(row.find('.qty-input').val()) || 0;
                const price = parseFloat(row.find('.price-input').val()) || 0;
                const amount = qty * price;
                
                row.find(`.amount-display[data-line-index="${lineIndex}"]`).text(amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                row.find(`.amount-input[data-line-index="${lineIndex}"]`).val(amount.toFixed(2));
                
                calculateTotal();
            }
            
            function calculateTotal() {
                let total = 0;
                $('.amount-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#total-amount').text(total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
            }
            
            // Bind change events
            $(document).on('input', '.qty-input, .price-input', function() {
                const lineIndex = $(this).data('line-index');
                calculateAmount(lineIndex);
            });
            
            // Initial calculation
            $('.qty-input').each(function() {
                const lineIndex = $(this).data('line-index');
                calculateAmount(lineIndex);
            });
        });
    </script>
@endpush
