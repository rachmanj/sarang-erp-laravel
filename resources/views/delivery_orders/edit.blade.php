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
