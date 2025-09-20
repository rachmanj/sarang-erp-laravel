@extends('layouts.main')

@section('title_page')
    ERP Parameters
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">ERP Parameters</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">ERP Parameters Management</h3>
                            <div class="card-tools">
                                <a href="{{ route('erp-parameters.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Add Parameter
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                        aria-hidden="true">&times;</button>
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_filter">Filter by Category:</label>
                                        <select class="form-control" id="category_filter">
                                            <option value="">All Categories</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category }}">
                                                    {{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            @foreach ($parameters as $category => $categoryParameters)
                                <div class="card card-outline card-primary category-section"
                                    data-category="{{ $category }}">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-cog"></i> {{ ucfirst(str_replace('_', ' ', $category)) }}
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Parameter Name</th>
                                                        <th>Key</th>
                                                        <th>Value</th>
                                                        <th>Data Type</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($categoryParameters as $parameter)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $parameter->parameter_name }}</strong>
                                                                @if ($parameter->description)
                                                                    <br><small
                                                                        class="text-muted">{{ $parameter->description }}</small>
                                                                @endif
                                                            </td>
                                                            <td><code>{{ $parameter->parameter_key }}</code></td>
                                                            <td>
                                                                @if ($parameter->data_type === 'boolean')
                                                                    <span
                                                                        class="badge badge-{{ $parameter->parameter_value ? 'success' : 'danger' }}">
                                                                        {{ $parameter->parameter_value ? 'Yes' : 'No' }}
                                                                    </span>
                                                                @elseif($parameter->data_type === 'json')
                                                                    <pre class="mb-0">{{ json_encode(json_decode($parameter->parameter_value), JSON_PRETTY_PRINT) }}</pre>
                                                                @else
                                                                    {{ $parameter->parameter_value }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-info">{{ $parameter->data_type }}</span>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-{{ $parameter->is_active ? 'success' : 'secondary' }}">
                                                                    {{ $parameter->is_active ? 'Active' : 'Inactive' }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="{{ route('erp-parameters.show', $parameter) }}"
                                                                        class="btn btn-info btn-sm">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="{{ route('erp-parameters.edit', $parameter) }}"
                                                                        class="btn btn-warning btn-sm">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <form
                                                                        action="{{ route('erp-parameters.destroy', $parameter) }}"
                                                                        method="POST" style="display: inline-block;"
                                                                        onsubmit="return confirm('Are you sure you want to delete this parameter?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="btn btn-danger btn-sm">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Category filter functionality
            $('#category_filter').on('change', function() {
                var selectedCategory = $(this).val();

                if (selectedCategory === '') {
                    $('.category-section').show();
                } else {
                    $('.category-section').hide();
                    $('.category-section[data-category="' + selectedCategory + '"]').show();
                }
            });
        });
    </script>
@endsection
