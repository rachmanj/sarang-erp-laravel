@extends('layouts.main')

@section('title_page')
    Edit ERP Parameter
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('erp-parameters.index') }}">ERP Parameters</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Edit ERP Parameter</h3>
                            <div class="card-tools">
                                <a href="{{ route('erp-parameters.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('erp-parameters.update', $erpParameter) }}" method="POST">
                            @csrf
                            @method('PATCH')
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
                                                        {{ old('category', $erpParameter->category) == $category ? 'selected' : '' }}>
                                                        {{ ucfirst(str_replace('_', ' ', $category)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('category')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parameter_key">Parameter Key <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('parameter_key') is-invalid @enderror"
                                                id="parameter_key" name="parameter_key"
                                                value="{{ old('parameter_key', $erpParameter->parameter_key) }}" required>
                                            @error('parameter_key')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parameter_name">Parameter Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('parameter_name') is-invalid @enderror"
                                                id="parameter_name" name="parameter_name"
                                                value="{{ old('parameter_name', $erpParameter->parameter_name) }}"
                                                required>
                                            @error('parameter_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="data_type">Data Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('data_type') is-invalid @enderror"
                                                id="data_type" name="data_type" required>
                                                @foreach (['string', 'integer', 'decimal', 'boolean', 'json'] as $type)
                                                    <option value="{{ $type }}"
                                                        {{ old('data_type', $erpParameter->data_type) == $type ? 'selected' : '' }}>
                                                        {{ ucfirst($type) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('data_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="parameter_value">Parameter Value <span class="text-danger">*</span></label>
                                    @if (old('data_type', $erpParameter->data_type) === 'boolean')
                                        <select class="form-control @error('parameter_value') is-invalid @enderror"
                                            id="parameter_value" name="parameter_value" required>
                                            <option value="1"
                                                {{ old('parameter_value', $erpParameter->parameter_value) == '1' ? 'selected' : '' }}>
                                                Yes</option>
                                            <option value="0"
                                                {{ old('parameter_value', $erpParameter->parameter_value) == '0' ? 'selected' : '' }}>
                                                No</option>
                                        </select>
                                    @elseif(old('data_type', $erpParameter->data_type) === 'json')
                                        <textarea class="form-control @error('parameter_value') is-invalid @enderror"
                                            id="parameter_value" name="parameter_value" rows="3"
                                            required>{{ old('parameter_value', $erpParameter->parameter_value) }}</textarea>
                                    @else
                                        <input type="text"
                                            class="form-control @error('parameter_value') is-invalid @enderror"
                                            id="parameter_value" name="parameter_value"
                                            value="{{ old('parameter_value', $erpParameter->parameter_value) }}" required>
                                    @endif
                                    @error('parameter_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                        name="description" rows="3">{{ old('description', $erpParameter->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active"
                                            name="is_active" value="1"
                                            {{ old('is_active', $erpParameter->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Parameter
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
            $('#data_type').on('change', function() {
                var dataType = $(this).val();
                var currentValue = $('#parameter_value').val() || '';
                var valueContainer = $('#parameter_value').parent();
                var labelHtml = valueContainer.find('label').prop('outerHTML');
                var errorHtml = valueContainer.find('.invalid-feedback').prop('outerHTML') || '';

                if (dataType === 'boolean') {
                    valueContainer.html(labelHtml +
                        '<select class="form-control" id="parameter_value" name="parameter_value" required>' +
                        '<option value="1"' + (currentValue === '1' ? ' selected' : '') + '>Yes</option>' +
                        '<option value="0"' + (currentValue === '0' ? ' selected' : '') + '>No</option>' +
                        '</select>' + errorHtml);
                } else if (dataType === 'json') {
                    valueContainer.html(labelHtml +
                        '<textarea class="form-control" id="parameter_value" name="parameter_value" rows="3" required>' +
                        currentValue + '</textarea>' + errorHtml);
                } else {
                    valueContainer.html(labelHtml +
                        '<input type="text" class="form-control" id="parameter_value" name="parameter_value" value="' +
                        currentValue.replace(/"/g, '&quot;') + '" required>' + errorHtml);
                }
            });
        });
    </script>
@endsection
