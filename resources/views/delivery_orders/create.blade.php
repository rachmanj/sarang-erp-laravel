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
                                                <select name="sales_order_id"
                                                    class="form-control form-control-sm select2bs4" id="sales_order_id"
                                                    required>
                                                    <option value="">-- select sales order --</option>
                                                    @foreach ($salesOrders as $so)
                                                        <option value="{{ $so->id }}"
                                                            {{ $salesOrder && $salesOrder->id == $so->id ? 'selected' : '' }}
                                                            data-customer-name="{{ $so->customer->name }}"
                                                            data-customer-address="{{ $so->customer->address }}"
                                                            data-customer-contact="{{ $so->customer->contact_person }}"
                                                            data-customer-phone="{{ $so->customer->phone }}"
                                                            data-expected-delivery="{{ $so->expected_delivery_date }}">
                                                            {{ $so->order_no }} - {{ $so->customer->name }}
                                                            ({{ $so->date }})
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
                                                <textarea name="delivery_address" class="form-control form-control-sm" rows="3" required>{{ old('delivery_address', $salesOrder ? $salesOrder->customer->address : '') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Contact Person</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="delivery_contact_person"
                                                    value="{{ old('delivery_contact_person', $salesOrder ? $salesOrder->customer->contact_person : '') }}"
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
                                                    value="{{ old('delivery_phone', $salesOrder ? $salesOrder->customer->phone : '') }}"
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
                                        <div class="card-header py-2">
                                            <h3 class="card-title">
                                                <i class="fas fa-list-ul mr-1"></i>
                                                Sales Order Items
                                            </h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Item</th>
                                                            <th>Description</th>
                                                            <th>Qty</th>
                                                            <th>Unit Price</th>
                                                            <th>Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($salesOrder->lines as $line)
                                                            <tr>
                                                                <td>{{ $line->item_code ?? 'N/A' }}</td>
                                                                <td>{{ $line->description ?? 'N/A' }}</td>
                                                                <td class="text-right">{{ number_format($line->qty, 2) }}
                                                                </td>
                                                                <td class="text-right">
                                                                    {{ number_format($line->unit_price, 2) }}</td>
                                                                <td class="text-right">
                                                                    {{ number_format($line->amount, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="4" class="text-right">Total:</th>
                                                            <th class="text-right">
                                                                {{ number_format($salesOrder->total_amount, 2) }}</th>
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
        $(document).ready(function() {
            // Initialize Select2BS4
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Auto-fill customer details when sales order is selected
            $('#sales_order_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    $('textarea[name="delivery_address"]').val(selectedOption.data('customer-address'));
                    $('input[name="delivery_contact_person"]').val(selectedOption.data('customer-contact'));
                    $('input[name="delivery_phone"]').val(selectedOption.data('customer-phone'));
                    $('input[name="planned_delivery_date"]').val(selectedOption.data('expected-delivery'));
                }
            });
        });
    </script>
@endpush
