@extends('layouts.main')

@section('title_page')
    Create ERP Parameter
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('erp-parameters.index') }}">ERP Parameters</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Create ERP Parameter</h3>
                            <div class="card-tools">
                                <a href="{{ route('erp-parameters.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('erp-parameters.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category">Category <span class="text-danger">*</span></label>
                                            <select class="form-control @error('category') is-invalid @enderror"
                                                id="category" name="category" required>
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category }}"
                                                        {{ old('category') == $category ? 'selected' : '' }}>
                                                        {{ ucfirst(str_replace('_', ' ', $category)) }}
                                                    </option>
                                                @endforeach
                                                <option value="new_category"
                                                    {{ old('category') == 'new_category' ? 'selected' : '' }}>Create New
                                                    Category</option>
                                            </select>
                                            @error('category')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="new_category_group" style="display: none;">
                                            <label for="new_category_name">New Category Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('new_category_name') is-invalid @enderror"
                                                id="new_category_name" name="new_category_name"
                                                value="{{ old('new_category_name') }}">
                                            @error('new_category_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parameter_key">Parameter Key <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('parameter_key') is-invalid @enderror"
                                                id="parameter_key" name="parameter_key" value="{{ old('parameter_key') }}"
                                                required>
                                            @error('parameter_key')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parameter_name">Parameter Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('parameter_name') is-invalid @enderror"
                                                id="parameter_name" name="parameter_name"
                                                value="{{ old('parameter_name') }}" required>
                                            @error('parameter_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="data_type">Data Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('data_type') is-invalid @enderror"
                                                id="data_type" name="data_type" required>
                                                <option value="">Select Data Type</option>
                                                <option value="string"
                                                    {{ old('data_type') == 'string' ? 'selected' : '' }}>String</option>
                                                <option value="integer"
                                                    {{ old('data_type') == 'integer' ? 'selected' : '' }}>Integer</option>
                                                <option value="boolean"
                                                    {{ old('data_type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                                <option value="json" {{ old('data_type') == 'json' ? 'selected' : '' }}>
                                                    JSON</option>
                                            </select>
                                            @error('data_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parameter_value">Parameter Value <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('parameter_value') is-invalid @enderror"
                                                id="parameter_value" name="parameter_value"
                                                value="{{ old('parameter_value') }}" required>
                                            @error('parameter_value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                        rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                            value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Parameter
                                </button>
                                <a href="{{ route('erp-parameters.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Show/hide new category input
            $('#category').on('change', function() {
                if ($(this).val() === 'new_category') {
                    $('#new_category_group').show();
                    $('#new_category_name').prop('required', true);
                } else {
                    $('#new_category_group').hide();
                    $('#new_category_name').prop('required', false);
                }
            });

            // Data type specific value input
            $('#data_type').on('change', function() {
                var dataType = $(this).val();
                var valueInput = $('#parameter_value');

                switch (dataType) {
                    case 'boolean':
                        valueInput.replaceWith(
                            '<select class="form-control" id="parameter_value" name="parameter_value" required>' +
                            '<option value="1">Yes</option>' +
                            '<option value="0">No</option>' +
                            '</select>');
                        break;
                    case 'json':
                        valueInput.replaceWith(
                            '<textarea class="form-control" id="parameter_value" name="parameter_value" rows="3" required></textarea>'
                            );
                        break;
                    default:
                        valueInput.replaceWith(
                            '<input type="text" class="form-control" id="parameter_value" name="parameter_value" required>'
                            );
                }
            });
        });
    </script>
@endsection
