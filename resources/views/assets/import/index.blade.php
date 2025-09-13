@extends('layouts.app')

@section('title', 'Asset Import')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Asset Import</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                            <li class="breadcrumb-item active">Import</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <!-- Import Steps -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-upload"></i> Bulk Asset Import
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Step 1: Download Template -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h5><i class="fas fa-download text-primary"></i> Step 1: Download Template</h5>
                                        <p>Download the CSV template to see the required format and sample data.</p>
                                        <a href="{{ route('assets.import.template') }}" class="btn btn-primary">
                                            <i class="fas fa-download"></i> Download Template
                                        </a>
                                    </div>
                                </div>

                                <!-- Step 2: Upload File -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h5><i class="fas fa-upload text-success"></i> Step 2: Upload CSV File</h5>
                                        <p>Select your CSV file and configure import options.</p>

                                        <form id="importForm" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="file">CSV File</label>
                                                        <div class="input-group">
                                                            <div class="custom-file">
                                                                <input type="file" class="custom-file-input"
                                                                    id="file" name="file" accept=".csv,.txt"
                                                                    required>
                                                                <label class="custom-file-label" for="file">Choose
                                                                    file</label>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Maximum file size: 10MB</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="default_fund">Default Fund</label>
                                                        <select class="form-control select2bs4" id="default_fund"
                                                            name="options[default_fund]">
                                                            <option value="">Select Fund (Optional)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="default_project">Default Project</label>
                                                        <select class="form-control select2bs4" id="default_project"
                                                            name="options[default_project]">
                                                            <option value="">Select Project (Optional)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="default_department">Default Department</label>
                                                        <select class="form-control select2bs4" id="default_department"
                                                            name="options[default_department]">
                                                            <option value="">Select Department (Optional)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="skip_duplicates"
                                                        name="options[skip_duplicates]" value="1">
                                                    <label class="form-check-label" for="skip_duplicates">
                                                        Skip duplicate assets (by code)
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <button type="button" class="btn btn-info" id="validateBtn">
                                                    <i class="fas fa-check"></i> Validate File
                                                </button>
                                                <button type="submit" class="btn btn-success" id="importBtn" disabled>
                                                    <i class="fas fa-upload"></i> Import Assets
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Step 3: Results -->
                                <div class="row" id="resultsSection" style="display: none;">
                                    <div class="col-12">
                                        <h5><i class="fas fa-chart-bar text-info"></i> Step 3: Import Results</h5>
                                        <div id="importResults"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reference Data -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle"></i> Reference Data
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Asset Categories</h6>
                                        <div id="categoriesList"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Funds</h6>
                                        <div id="fundsList"></div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h6>Projects</h6>
                                        <div id="projectsList"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Departments</h6>
                                        <div id="departmentsList"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .import-step {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .reference-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 2px 0;
            font-size: 0.9em;
        }

        .reference-item strong {
            color: #495057;
        }

        .validation-result {
            margin-top: 15px;
        }

        .import-result {
            margin-top: 15px;
        }

        .error-list,
        .warning-list {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Load reference data
            loadReferenceData();

            // File input change handler
            $('#file').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });

            // Validate button click
            $('#validateBtn').on('click', function() {
                validateFile();
            });

            // Import form submit
            $('#importForm').on('submit', function(e) {
                e.preventDefault();
                importAssets();
            });
        });

        function loadReferenceData() {
            $.ajax({
                url: '{{ route('assets.import.reference-data') }}',
                method: 'GET',
                success: function(data) {
                    // Populate dropdowns
                    populateSelect('#default_fund', data.funds);
                    populateSelect('#default_project', data.projects);
                    populateSelect('#default_department', data.departments);

                    // Display reference data
                    displayReferenceData('categoriesList', data.categories);
                    displayReferenceData('fundsList', data.funds);
                    displayReferenceData('projectsList', data.projects);
                    displayReferenceData('departmentsList', data.departments);
                },
                error: function(xhr) {
                    toastr.error('Failed to load reference data');
                }
            });
        }

        function populateSelect(selector, data) {
            const select = $(selector);
            select.empty().append('<option value="">Select ' + select.attr('id').replace('default_', '') +
                ' (Optional)</option>');

            data.forEach(function(item) {
                select.append('<option value="' + item.code + '">' + item.code + ' - ' + item.name + '</option>');
            });
        }

        function displayReferenceData(containerId, data) {
            const container = $('#' + containerId);
            container.empty();

            data.forEach(function(item) {
                container.append('<div class="reference-item"><strong>' + item.code + '</strong> - ' + item.name +
                    '</div>');
            });
        }

        function validateFile() {
            const formData = new FormData();
            const fileInput = $('#file')[0];

            if (!fileInput.files[0]) {
                toastr.error('Please select a file first');
                return;
            }

            formData.append('file', fileInput.files[0]);

            $('#validateBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Validating...');

            $.ajax({
                url: '{{ route('assets.import.validate') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    displayValidationResults(response);
                    $('#importBtn').prop('disabled', !response.valid);
                },
                error: function(xhr) {
                    toastr.error('Validation failed');
                },
                complete: function() {
                    $('#validateBtn').prop('disabled', false).html(
                    '<i class="fas fa-check"></i> Validate File');
                }
            });
        }

        function displayValidationResults(result) {
            let html = '<div class="validation-result">';

            if (result.valid) {
                html += '<div class="alert alert-success">';
                html += '<h6><i class="fas fa-check-circle"></i> File is valid!</h6>';
                html += '<p>Total rows: ' + result.total_rows + '<br>';
                html += 'Valid rows: ' + result.valid_rows + '<br>';
                html += 'Invalid rows: ' + result.invalid_rows + '</p>';
                html += '</div>';
            } else {
                html += '<div class="alert alert-danger">';
                html += '<h6><i class="fas fa-exclamation-triangle"></i> Validation failed!</h6>';
                html += '<p>Total rows: ' + result.total_rows + '<br>';
                html += 'Valid rows: ' + result.valid_rows + '<br>';
                html += 'Invalid rows: ' + result.invalid_rows + '</p>';
                html += '</div>';
            }

            if (result.errors && result.errors.length > 0) {
                html += '<div class="alert alert-danger">';
                html += '<h6>Errors:</h6>';
                html += '<ul class="error-list">';
                result.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            if (result.warnings && result.warnings.length > 0) {
                html += '<div class="alert alert-warning">';
                html += '<h6>Warnings:</h6>';
                html += '<ul class="warning-list">';
                result.warnings.forEach(function(warning) {
                    html += '<li>' + warning + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            html += '</div>';

            $('#importResults').html(html);
            $('#resultsSection').show();
        }

        function importAssets() {
            const formData = new FormData($('#importForm')[0]);

            $('#importBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');

            $.ajax({
                url: '{{ route('assets.import.import') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    displayImportResults(response);
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toastr.error(response.message || 'Import failed');
                },
                complete: function() {
                    $('#importBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Import Assets');
                }
            });
        }

        function displayImportResults(result) {
            let html = '<div class="import-result">';

            if (result.success) {
                html += '<div class="alert alert-success">';
                html += '<h6><i class="fas fa-check-circle"></i> Import completed successfully!</h6>';
                html += '<p>Imported: ' + result.imported_count + ' assets<br>';
                html += 'Skipped: ' + result.skipped_count + ' assets</p>';
                html += '</div>';

                toastr.success('Assets imported successfully!');
            } else {
                html += '<div class="alert alert-danger">';
                html += '<h6><i class="fas fa-exclamation-triangle"></i> Import failed!</h6>';
                html += '<p>' + result.message + '</p>';
                html += '</div>';
            }

            if (result.errors && result.errors.length > 0) {
                html += '<div class="alert alert-danger">';
                html += '<h6>Errors:</h6>';
                html += '<ul class="error-list">';
                result.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            if (result.warnings && result.warnings.length > 0) {
                html += '<div class="alert alert-warning">';
                html += '<h6>Warnings:</h6>';
                html += '<ul class="warning-list">';
                result.warnings.forEach(function(warning) {
                    html += '<li>' + warning + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }

            html += '</div>';

            $('#importResults').html(html);
            $('#resultsSection').show();
        }
    </script>
@endpush
