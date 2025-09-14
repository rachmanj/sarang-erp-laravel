@extends('layouts.app')

@section('title', 'Tax Settings')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Tax Settings</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form action="{{ route('tax.settings.update') }}" method="POST">
            @csrf
            <div class="row">
                <!-- Tax Rates -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-percentage mr-2"></i>
                                Tax Rates Configuration
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="ppn_rate">PPN (VAT) Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('ppn_rate') is-invalid @enderror" 
                                           id="ppn_rate" name="ppn_rate" 
                                           value="{{ old('ppn_rate', $taxRates['ppn_rate']) }}" 
                                           step="0.01" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('ppn_rate')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Current Indonesian PPN rate</small>
                            </div>

                            <div class="form-group">
                                <label for="pph_21_rate">PPh 21 Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('pph_21_rate') is-invalid @enderror" 
                                           id="pph_21_rate" name="pph_21_rate" 
                                           value="{{ old('pph_21_rate', $taxRates['pph_21_rate']) }}" 
                                           step="0.01" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('pph_21_rate')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Income tax withholding rate</small>
                            </div>

                            <div class="form-group">
                                <label for="pph_22_rate">PPh 22 Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('pph_22_rate') is-invalid @enderror" 
                                           id="pph_22_rate" name="pph_22_rate" 
                                           value="{{ old('pph_22_rate', $taxRates['pph_22_rate']) }}" 
                                           step="0.01" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('pph_22_rate')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Import tax withholding rate</small>
                            </div>

                            <div class="form-group">
                                <label for="pph_23_rate">PPh 23 Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('pph_23_rate') is-invalid @enderror" 
                                           id="pph_23_rate" name="pph_23_rate" 
                                           value="{{ old('pph_23_rate', $taxRates['pph_23_rate']) }}" 
                                           step="0.01" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('pph_23_rate')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Service tax withholding rate</small>
                            </div>

                            <div class="form-group">
                                <label for="pph_26_rate">PPh 26 Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('pph_26_rate') is-invalid @enderror" 
                                           id="pph_26_rate" name="pph_26_rate" 
                                           value="{{ old('pph_26_rate', $taxRates['pph_26_rate']) }}" 
                                           step="0.01" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('pph_26_rate')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Foreign entity tax withholding rate</small>
                            </div>

                            <div class="form-group">
                                <label for="pph_4_2_rate">PPh 4(2) Rate (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('pph_4_2_rate') is-invalid @enderror" 
                                           id="pph_4_2_rate" name="pph_4_2_rate" 
                                           value="{{ old('pph_4_2_rate', $taxRates['pph_4_2_rate']) }}" 
                                           step="0.01" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                @error('pph_4_2_rate')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Construction tax withholding rate</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-building mr-2"></i>
                                Company Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                       id="company_name" name="company_name" 
                                       value="{{ old('company_name', $companyInfo['company_name']) }}" required>
                                @error('company_name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="company_npwp">Company NPWP</label>
                                <input type="text" class="form-control @error('company_npwp') is-invalid @enderror" 
                                       id="company_npwp" name="company_npwp" 
                                       value="{{ old('company_npwp', $companyInfo['company_npwp']) }}" 
                                       placeholder="12.345.678.9-012.000" required>
                                @error('company_npwp')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="company_address">Company Address</label>
                                <textarea class="form-control @error('company_address') is-invalid @enderror" 
                                          id="company_address" name="company_address" rows="3" required>{{ old('company_address', $companyInfo['company_address']) }}</textarea>
                                @error('company_address')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="company_phone">Company Phone</label>
                                <input type="text" class="form-control @error('company_phone') is-invalid @enderror" 
                                       id="company_phone" name="company_phone" 
                                       value="{{ old('company_phone', $companyInfo['company_phone']) }}">
                                @error('company_phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="company_email">Company Email</label>
                                <input type="email" class="form-control @error('company_email') is-invalid @enderror" 
                                       id="company_email" name="company_email" 
                                       value="{{ old('company_email', $companyInfo['company_email']) }}">
                                @error('company_email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Tax Office Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-landmark mr-2"></i>
                                Tax Office Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="tax_office_code">Tax Office Code</label>
                                <input type="text" class="form-control @error('tax_office_code') is-invalid @enderror" 
                                       id="tax_office_code" name="tax_office_code" 
                                       value="{{ old('tax_office_code', $taxOfficeInfo['tax_office_code']) }}" required>
                                @error('tax_office_code')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="tax_office_name">Tax Office Name</label>
                                <input type="text" class="form-control @error('tax_office_name') is-invalid @enderror" 
                                       id="tax_office_name" name="tax_office_name" 
                                       value="{{ old('tax_office_name', $taxOfficeInfo['tax_office_name']) }}" required>
                                @error('tax_office_name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="tax_office_address">Tax Office Address</label>
                                <textarea class="form-control @error('tax_office_address') is-invalid @enderror" 
                                          id="tax_office_address" name="tax_office_address" rows="3" required>{{ old('tax_office_address', $taxOfficeInfo['tax_office_address']) }}</textarea>
                                @error('tax_office_address')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reporting Settings -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-alt mr-2"></i>
                                Reporting Settings
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="auto_generate_reports" name="auto_generate_reports" 
                                           value="1" {{ old('auto_generate_reports', $reportingSettings['auto_generate_reports']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="auto_generate_reports">
                                        Auto Generate Reports
                                    </label>
                                </div>
                                <small class="form-text text-muted">Automatically generate tax reports at period end</small>
                            </div>

                            <div class="form-group">
                                <label for="report_due_day">Report Due Day</label>
                                <select class="form-control @error('report_due_day') is-invalid @enderror" 
                                        id="report_due_day" name="report_due_day" required>
                                    @for($i = 1; $i <= 31; $i++)
                                        <option value="{{ $i }}" {{ old('report_due_day', $reportingSettings['report_due_day']) == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                                @error('report_due_day')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Day of month when reports are due (usually 20th)</small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="send_reminders" name="send_reminders" 
                                           value="1" {{ old('send_reminders', $reportingSettings['send_reminders']) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="send_reminders">
                                        Send Reminders
                                    </label>
                                </div>
                                <small class="form-text text-muted">Send reminder notifications for upcoming deadlines</small>
                            </div>

                            <div class="form-group">
                                <label for="reminder_days_before">Reminder Days Before</label>
                                <select class="form-control @error('reminder_days_before') is-invalid @enderror" 
                                        id="reminder_days_before" name="reminder_days_before" required>
                                    @for($i = 1; $i <= 30; $i++)
                                        <option value="{{ $i }}" {{ old('reminder_days_before', $reportingSettings['reminder_days_before']) == $i ? 'selected' : '' }}>
                                            {{ $i }} day{{ $i > 1 ? 's' : '' }}
                                        </option>
                                    @endfor
                                </select>
                                @error('reminder_days_before')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Days before due date to send reminders</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Save Settings
                            </button>
                            <a href="{{ route('tax.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Format NPWP input
    $('#company_npwp').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length >= 15) {
            value = value.substring(0, 15);
            var formatted = value.substring(0, 2) + '.' + 
                           value.substring(2, 5) + '.' + 
                           value.substring(5, 8) + '.' + 
                           value.substring(8, 9) + '-' + 
                           value.substring(9, 12) + '.' + 
                           value.substring(12, 15);
            $(this).val(formatted);
        }
    });

    // Form validation
    $('form').submit(function(e) {
        var isValid = true;
        
        // Check required fields
        $('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // Check tax rates are within valid range
        $('input[name$="_rate"]').each(function() {
            var value = parseFloat($(this).val());
            if (value < 0 || value > 100) {
                $(this).addClass('is-invalid');
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            toastr.error('Please fix the errors before submitting.');
        }
    });
});
</script>
@endpush