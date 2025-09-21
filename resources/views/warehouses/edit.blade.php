@extends('layouts.main')

@section('title', 'Edit Warehouse')

@section('title_page')
    Edit Warehouse
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.index') }}">Warehouses</a></li>
    <li class="breadcrumb-item"><a href="{{ route('warehouses.show', $warehouse->id) }}">{{ $warehouse->name }}</a></li>
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
                                <i class="fas fa-edit mr-1"></i>
                                Edit Warehouse Information
                            </h3>
                            <a href="{{ route('warehouses.show', $warehouse->id) }}"
                                class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-arrow-left"></i> Back to Warehouse
                            </a>
                        </div>
                        <form method="post" action="{{ route('warehouses.update', $warehouse->id) }}" id="warehouse-form">
                            @csrf
                            @method('PATCH')
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse Code <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control form-control-sm" name="code"
                                                    value="{{ old('code', $warehouse->code) }}"
                                                    placeholder="Enter warehouse code" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-3 col-form-label">Warehouse Name <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-sm-9">
                                                <input type="text" class="form-control form-control-sm" name="name"
                                                    value="{{ old('name', $warehouse->name) }}"
                                                    placeholder="Enter warehouse name" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-2 col-form-label">Address</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control form-control-sm" name="address" rows="2" placeholder="Enter warehouse address">{{ old('address', $warehouse->address) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Contact Person</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control form-control-sm"
                                                    name="contact_person"
                                                    value="{{ old('contact_person', $warehouse->contact_person) }}"
                                                    placeholder="Enter contact person name">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Phone</label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control form-control-sm" name="phone"
                                                    value="{{ old('phone', $warehouse->phone) }}"
                                                    placeholder="Enter phone number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group row mb-2">
                                            <label class="col-sm-4 col-form-label">Email</label>
                                            <div class="col-sm-8">
                                                <input type="email" class="form-control form-control-sm" name="email"
                                                    value="{{ old('email', $warehouse->email) }}"
                                                    placeholder="Enter email address">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group row mb-2">
                                            <div class="col-sm-2"></div>
                                            <div class="col-sm-10">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="is_active"
                                                        value="1"
                                                        {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                                                    <label class="form-check-label">Active Warehouse</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 text-right">
                                        <a href="{{ route('warehouses.show', $warehouse->id) }}"
                                            class="btn btn-secondary btn-sm">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save"></i> Update Warehouse
                                        </button>
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
