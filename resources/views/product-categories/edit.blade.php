@extends('layouts.main')

@section('title_page')
    Edit Product Category
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('product-categories.index') }}">Product Categories</a></li>
    <li class="breadcrumb-item"><a
            href="{{ route('product-categories.show', $productCategory->id) }}">{{ $productCategory->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Category: {{ $productCategory->name }}
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('product-categories.show', $productCategory->id) }}"
                                    class="btn btn-info btn-sm">
                                    <i class="fas fa-eye mr-1"></i>
                                    View Details
                                </a>
                                <a href="{{ route('product-categories.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i>
                                    Back to Categories
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('product-categories.update', $productCategory->id) }}" method="POST">
                                @csrf
                                @method('PATCH')

                                <div class="row">
                                    <!-- Basic Information -->
                                    <div class="col-md-6">
                                        <div class="card card-outline">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Basic Information
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="code">Category Code <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text"
                                                        class="form-control @error('code') is-invalid @enderror"
                                                        id="code" name="code"
                                                        value="{{ old('code', $productCategory->code) }}"
                                                        placeholder="e.g., CAT001" required>
                                                    @error('code')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="name">Category Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text"
                                                        class="form-control @error('name') is-invalid @enderror"
                                                        id="name" name="name"
                                                        value="{{ old('name', $productCategory->name) }}"
                                                        placeholder="e.g., Electronics" required>
                                                    @error('name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="description">Description</label>
                                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                                        rows="3" placeholder="Category description...">{{ old('description', $productCategory->description) }}</textarea>
                                                    @error('description')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="parent_id">Parent Category</label>
                                                    <select
                                                        class="form-control select2bs4 @error('parent_id') is-invalid @enderror"
                                                        id="parent_id" name="parent_id">
                                                        <option value="">Select Parent Category (Optional)</option>
                                                        @foreach ($parentCategories as $parentCategory)
                                                            <option value="{{ $parentCategory->id }}"
                                                                {{ old('parent_id', $productCategory->parent_id) == $parentCategory->id ? 'selected' : '' }}>
                                                                {{ $parentCategory->name }} ({{ $parentCategory->code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('parent_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="is_active"
                                                            name="is_active"
                                                            {{ old('is_active', $productCategory->is_active) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="is_active">
                                                            Active Category
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Account Mapping -->
                                    <div class="col-md-6">
                                        <div class="card card-outline">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-chart-line mr-2"></i>
                                                    Account Mapping
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="inventory_account_id">Inventory Account</label>
                                                    <select
                                                        class="form-control select2bs4 @error('inventory_account_id') is-invalid @enderror"
                                                        id="inventory_account_id" name="inventory_account_id">
                                                        <option value="">Select Inventory Account (Optional)</option>
                                                        @foreach ($inventoryAccounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ old('inventory_account_id', $productCategory->inventory_account_id) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->code }} - {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Required for physical items. Leave empty for services.
                                                    </small>
                                                    @error('inventory_account_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="cogs_account_id">COGS Account <span
                                                            class="text-danger">*</span></label>
                                                    <select
                                                        class="form-control select2bs4 @error('cogs_account_id') is-invalid @enderror"
                                                        id="cogs_account_id" name="cogs_account_id" required>
                                                        <option value="">Select COGS Account</option>
                                                        @foreach ($cogsAccounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ old('cogs_account_id', $productCategory->cogs_account_id) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->code }} - {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('cogs_account_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="sales_account_id">Sales Account <span
                                                            class="text-danger">*</span></label>
                                                    <select
                                                        class="form-control select2bs4 @error('sales_account_id') is-invalid @enderror"
                                                        id="sales_account_id" name="sales_account_id" required>
                                                        <option value="">Select Sales Account</option>
                                                        @foreach ($salesAccounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ old('sales_account_id', $productCategory->sales_account_id) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->code }} - {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('sales_account_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <strong>Account Mapping:</strong> Items in this category will
                                                    automatically use these accounts for inventory valuation, cost of goods
                                                    sold, and sales revenue recognition.
                                                </div>

                                                <!-- Current Account Mapping Display -->
                                                <div class="alert alert-light">
                                                    <h6><strong>Current Account Mapping:</strong></h6>
                                                    <div class="row">
                                                        <div class="col-4">
                                                            <small class="text-muted">Inventory:</small><br>
                                                            @if ($productCategory->inventoryAccount)
                                                                <strong>{{ $productCategory->inventoryAccount->code }}</strong><br>
                                                                <small>{{ $productCategory->inventoryAccount->name }}</small>
                                                            @else
                                                                <span class="text-muted">Not Set</span>
                                                            @endif
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted">COGS:</small><br>
                                                            @if ($productCategory->cogsAccount)
                                                                <strong>{{ $productCategory->cogsAccount->code }}</strong><br>
                                                                <small>{{ $productCategory->cogsAccount->name }}</small>
                                                            @else
                                                                <span class="text-muted">Not Set</span>
                                                            @endif
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted">Sales:</small><br>
                                                            @if ($productCategory->salesAccount)
                                                                <strong>{{ $productCategory->salesAccount->code }}</strong><br>
                                                                <small>{{ $productCategory->salesAccount->name }}</small>
                                                            @else
                                                                <span class="text-muted">Not Set</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card card-outline">
                                            <div class="card-body text-center">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-save mr-2"></i>
                                                    Update Category
                                                </button>
                                                <a href="{{ route('product-categories.show', $productCategory->id) }}"
                                                    class="btn btn-secondary btn-lg ml-2">
                                                    <i class="fas fa-times mr-2"></i>
                                                    Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an option',
                allowClear: true
            });
        });
    </script>
@endpush
