@extends('layouts.main')

@section('title_page')
    Company Information
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Company Information</li>
@endsection

@section('content')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building"></i> Company Information
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
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

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                        aria-hidden="true">&times;</button>
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form action="{{ route('company-info.update') }}" method="POST" id="companyInfoForm">
                                @csrf

                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-md-6">
                                        <!-- Basic Information -->
                                        <div class="card card-outline card-info">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-info-circle"></i> Basic Information
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="company_name">Company Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text"
                                                        class="form-control @error('company_name') is-invalid @enderror"
                                                        id="company_name" name="company_name"
                                                        value="{{ old('company_name', $companyInfo['company_name']) }}"
                                                        required>
                                                    @error('company_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="company_address">Company Address</label>
                                                    <textarea class="form-control @error('company_address') is-invalid @enderror" id="company_address"
                                                        name="company_address" rows="3">{{ old('company_address', $companyInfo['company_address']) }}</textarea>
                                                    @error('company_address')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="company_tax_number">Tax Number (NPWP)</label>
                                                    <input type="text"
                                                        class="form-control @error('company_tax_number') is-invalid @enderror"
                                                        id="company_tax_number" name="company_tax_number"
                                                        value="{{ old('company_tax_number', $companyInfo['company_tax_number']) }}">
                                                    @error('company_tax_number')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contact Information -->
                                        <div class="card card-outline card-success">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-phone"></i> Contact Information
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <label for="company_phone">Phone Number</label>
                                                    <input type="text"
                                                        class="form-control @error('company_phone') is-invalid @enderror"
                                                        id="company_phone" name="company_phone"
                                                        value="{{ old('company_phone', $companyInfo['company_phone']) }}">
                                                    @error('company_phone')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="company_email">Email Address</label>
                                                    <input type="email"
                                                        class="form-control @error('company_email') is-invalid @enderror"
                                                        id="company_email" name="company_email"
                                                        value="{{ old('company_email', $companyInfo['company_email']) }}">
                                                    @error('company_email')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="form-group">
                                                    <label for="company_website">Website</label>
                                                    <input type="text"
                                                        class="form-control @error('company_website') is-invalid @enderror"
                                                        id="company_website" name="company_website"
                                                        value="{{ old('company_website', $companyInfo['company_website']) }}">
                                                    @error('company_website')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-md-6">
                                        <!-- Company Logo -->
                                        <div class="card card-outline card-warning">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-image"></i> Company Logo
                                                </h3>
                                            </div>
                                            <div class="card-body text-center">
                                                <div id="logo-preview" class="mb-3">
                                                    @if ($companyInfo['company_logo_path'])
                                                        <img src="{{ Storage::url($companyInfo['company_logo_path']) }}"
                                                            alt="Company Logo" class="img-fluid"
                                                            style="max-height: 200px; max-width: 300px;">
                                                    @else
                                                        <div class="text-muted">
                                                            <i class="fas fa-image fa-3x"></i>
                                                            <p>No logo uploaded</p>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="form-group">
                                                    <label for="logo">Upload Logo</label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input"
                                                                id="logo" accept="image/*">
                                                            <label class="custom-file-label" for="logo">Choose
                                                                file</label>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-primary"
                                                                id="uploadLogoBtn">
                                                                <i class="fas fa-upload"></i> Upload
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        Supported formats: JPEG, PNG, JPG. Max size: 2MB
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Preview Section -->
                                        <div class="card card-outline card-secondary">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    <i class="fas fa-eye"></i> Document Preview
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="border p-3" style="background-color: #f8f9fa;">
                                                    <div class="text-center mb-2">
                                                        <strong
                                                            id="preview-company-name">{{ $companyInfo['company_name'] ?: 'Company Name' }}</strong>
                                                    </div>
                                                    <div class="text-center small text-muted">
                                                        <div id="preview-company-address">
                                                            {{ $companyInfo['company_address'] ?: 'Company Address' }}
                                                        </div>
                                                        <div id="preview-company-contact">
                                                            @if ($companyInfo['company_phone'])
                                                                Phone: {{ $companyInfo['company_phone'] }}
                                                            @endif
                                                            @if ($companyInfo['company_email'])
                                                                | Email: {{ $companyInfo['company_email'] }}
                                                            @endif
                                                        </div>
                                                        @if ($companyInfo['company_tax_number'])
                                                            <div>Tax Number: {{ $companyInfo['company_tax_number'] }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-group text-center">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-save"></i> Save Company Information
                                            </button>
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
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Update preview when form fields change
            $('#company_name').on('input', function() {
                $('#preview-company-name').text($(this).val() || 'Company Name');
            });

            $('#company_address').on('input', function() {
                $('#preview-company-address').text($(this).val() || 'Company Address');
            });

            $('#company_phone, #company_email').on('input', function() {
                var phone = $('#company_phone').val();
                var email = $('#company_email').val();
                var contact = '';

                if (phone) contact += 'Phone: ' + phone;
                if (phone && email) contact += ' | ';
                if (email) contact += 'Email: ' + email;

                $('#preview-company-contact').text(contact);
            });

            // Logo upload functionality
            $('#uploadLogoBtn').on('click', function() {
                var fileInput = $('#logo')[0];
                var file = fileInput.files[0];

                if (!file) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No File Selected',
                        text: 'Please select a logo file to upload.'
                    });
                    return;
                }

                var formData = new FormData();
                formData.append('logo', file);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                // Show loading
                Swal.fire({
                    title: 'Uploading Logo...',
                    text: 'Please wait while we upload your logo.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route('company-info.upload-logo') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Update logo preview
                            $('#logo-preview').html('<img src="' + response.logo_url +
                                '" alt="Company Logo" class="img-fluid" style="max-height: 200px; max-width: 300px;">'
                                );

                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Upload Failed',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: response.message ||
                                'An error occurred while uploading the logo.'
                        });
                    }
                });
            });

            // Form submission with SweetAlert2 confirmation
            $('#companyInfoForm').on('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Save Company Information?',
                    text: 'Are you sure you want to save the company information?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Save!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Saving...',
                            text: 'Please wait while we save your information.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Submit the form
                        this.submit();
                    }
                });
            });
        });
    </script>
@endsection
