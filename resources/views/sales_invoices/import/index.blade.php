@extends('layouts.main')

@section('title', 'Sales Invoice Import - Opening Balance')

@section('title_page')
    Sales Invoice Import - Opening Balance
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-invoices.index') }}">Sales Invoices</a></li>
    <li class="breadcrumb-item active">Import Opening Balance</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-upload"></i> Bulk Sales Invoice Import - Opening Balance
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> Important Information</h5>
                                <ul class="mb-0">
                                    <li>All imported invoices will be marked as <strong>Opening Balance</strong> invoices</li>
                                    <li>Posting date will be set to <strong>01-01-2026</strong> for all invoices</li>
                                    <li>Journal entries will be: <strong>Debit: Piutang Dagang</strong> vs <strong>Credit: Saldo Awal Laba Ditahan (3.3.1)</strong></li>
                                    <li>Multiple lines with the same customer, document date, due date, reference no, and delivery order no will be grouped into a single invoice</li>
                                </ul>
                            </div>

                            <!-- Step 1: Download Template -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5><i class="fas fa-download text-primary"></i> Step 1: Download Template</h5>
                                    <p>Download the Excel template to see the required format and sample data.</p>
                                    <a href="{{ route('sales-invoices.import.template') }}" class="btn btn-primary">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                </div>
                            </div>

                            <!-- Step 2: Upload File -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5><i class="fas fa-upload text-success"></i> Step 2: Upload Excel File</h5>
                                    <p>Select your Excel file (.xlsx, .xls, or .csv) and configure import options.</p>

                                    <form id="importForm" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="file">Excel File</label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="file" name="file"
                                                                accept=".xlsx,.xls,.csv" required>
                                                            <label class="custom-file-label" for="file">Choose file</label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">Maximum file size: 10MB. Supported formats: .xlsx, .xls, .csv</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="company_entity_id">Company Entity</label>
                                                    <select class="form-control select2bs4" id="company_entity_id"
                                                        name="company_entity_id">
                                                        @foreach($entities as $entity)
                                                            <option value="{{ $entity->id }}" 
                                                                {{ $entity->id == $defaultEntity->id ? 'selected' : '' }}>
                                                                {{ $entity->code }} - {{ $entity->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="button" class="btn btn-info" id="validateBtn">
                                                <i class="fas fa-check"></i> Validate File
                                            </button>
                                            <button type="submit" class="btn btn-success" id="importBtn" disabled>
                                                <i class="fas fa-upload"></i> Import Sales Invoices
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

            <!-- Column Reference -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Excel Column Reference
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Column Name</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                            <th>Example</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Customer Code</strong></td>
                                            <td><span class="badge badge-danger">Yes</span></td>
                                            <td>Business Partner code (must be a customer)</td>
                                            <td>CUST001</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Document Date</strong></td>
                                            <td><span class="badge badge-danger">Yes</span></td>
                                            <td>Original invoice date (YYYY-MM-DD)</td>
                                            <td>2025-12-15</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Due Date</strong></td>
                                            <td><span class="badge badge-danger">Yes</span></td>
                                            <td>Payment due date (YYYY-MM-DD)</td>
                                            <td>2026-01-15</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Reference No</strong></td>
                                            <td><span class="badge badge-secondary">No</span></td>
                                            <td>Customer PO Number</td>
                                            <td>PO-2025-001</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Delivery Order No</strong></td>
                                            <td><span class="badge badge-secondary">No</span></td>
                                            <td>Delivery Order number (will be placed in description)</td>
                                            <td>DO-2025-001</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Account Code</strong></td>
                                            <td><span class="badge badge-danger">Yes</span></td>
                                            <td>Revenue account code</td>
                                            <td>4.1.1</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Description</strong></td>
                                            <td><span class="badge badge-secondary">No</span></td>
                                            <td>Line item description</td>
                                            <td>Product Sales</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Qty</strong></td>
                                            <td><span class="badge badge-danger">Yes</span></td>
                                            <td>Quantity (must be > 0)</td>
                                            <td>10</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Unit Price</strong></td>
                                            <td><span class="badge badge-danger">Yes</span></td>
                                            <td>Unit price (must be >= 0)</td>
                                            <td>100000</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tax Code</strong></td>
                                            <td><span class="badge badge-secondary">No</span></td>
                                            <td>Tax code (e.g., PPN11). <strong>Leave empty if no VAT applies.</strong></td>
                                            <td>PPN11 or (empty)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .import-step {
            border-left: 4px solid #007bff;
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .validation-result,
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
                importSalesInvoices();
            });
        });

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
                url: '{{ route('sales-invoices.import.validate') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    displayValidationResults(response);
                    $('#importBtn').prop('disabled', !response.valid);
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toastr.error(response.message || 'Validation failed');
                },
                complete: function() {
                    $('#validateBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Validate File');
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

        function importSalesInvoices() {
            const formData = new FormData($('#importForm')[0]);

            $('#importBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');

            $.ajax({
                url: '{{ route('sales-invoices.import.store') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    displayImportResults(response);
                    if (response.success) {
                        setTimeout(function() {
                            window.location.href = '{{ route('sales-invoices.index') }}';
                        }, 3000);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toastr.error(response.message || 'Import failed');
                    $('#importBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Import Sales Invoices');
                },
                complete: function() {
                    // Don't re-enable if success (will redirect)
                    if (!$('#importResults').find('.alert-success').length) {
                        $('#importBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Import Sales Invoices');
                    }
                }
            });
        }

        function displayImportResults(result) {
            let html = '<div class="import-result">';

            if (result.success) {
                html += '<div class="alert alert-success">';
                html += '<h6><i class="fas fa-check-circle"></i> Import completed successfully!</h6>';
                html += '<p>Imported: ' + result.imported_count + ' invoice(s)<br>';
                html += 'Skipped: ' + result.skipped_count + ' invoice(s)</p>';
                html += '<p class="mb-0"><strong>You will be redirected to Sales Invoices list in 3 seconds...</strong></p>';
                html += '</div>';

                toastr.success('Sales Invoices imported successfully!');
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
