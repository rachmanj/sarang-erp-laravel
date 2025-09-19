@extends('layouts.main')

@section('title_page')
    Generate Account Statement
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('account-statements.index') }}">Account Statements</a></li>
    <li class="breadcrumb-item active">Generate</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Generate Account Statement</h3>
                </div>
                <form method="POST" action="{{ route('account-statements.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="statement_type">Statement Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('statement_type') is-invalid @enderror"
                                        id="statement_type" name="statement_type" required>
                                        <option value="">Select Statement Type</option>
                                        <option value="gl_account"
                                            {{ old('statement_type', $selectedType) == 'gl_account' ? 'selected' : '' }}>GL
                                            Account Statement</option>
                                        <option value="business_partner"
                                            {{ old('statement_type', $selectedType) == 'business_partner' ? 'selected' : '' }}>
                                            Business Partner Statement</option>
                                    </select>
                                    @error('statement_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" id="account_group" style="display: none;">
                                    <label for="account_id">Account <span class="text-danger">*</span></label>
                                    <select class="form-control @error('account_id') is-invalid @enderror" id="account_id"
                                        name="account_id">
                                        <option value="">Select Account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                {{ old('account_id', $selectedAccountId) == $account->id ? 'selected' : '' }}>
                                                {{ $account->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('account_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group" id="business_partner_group" style="display: none;">
                                    <label for="business_partner_id">Business Partner <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control @error('business_partner_id') is-invalid @enderror"
                                        id="business_partner_id" name="business_partner_id">
                                        <option value="">Select Business Partner</option>
                                        @foreach ($businessPartners as $partner)
                                            <option value="{{ $partner->id }}"
                                                {{ old('business_partner_id', $selectedBusinessPartnerId) == $partner->id ? 'selected' : '' }}>
                                                {{ $partner->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('business_partner_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="from_date">From Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('from_date') is-invalid @enderror"
                                        id="from_date" name="from_date"
                                        value="{{ old('from_date', now()->startOfMonth()->format('Y-m-d')) }}" required>
                                    @error('from_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="to_date">To Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('to_date') is-invalid @enderror"
                                        id="to_date" name="to_date" value="{{ old('to_date', now()->format('Y-m-d')) }}"
                                        required>
                                    @error('to_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="project_id">Project (Optional)</label>
                                    <select class="form-control" id="project_id" name="project_id">
                                        <option value="">All Projects</option>
                                        <!-- Projects will be loaded via AJAX -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dept_id">Department (Optional)</label>
                                    <select class="form-control" id="dept_id" name="dept_id">
                                        <option value="">All Departments</option>
                                        <!-- Departments will be loaded via AJAX -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('account-statements.index') }}" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary">Generate Statement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Show/hide account or business partner fields based on statement type
            $('#statement_type').change(function() {
                const type = $(this).val();
                if (type === 'gl_account') {
                    $('#account_group').show();
                    $('#business_partner_group').hide();
                    $('#account_id').prop('required', true);
                    $('#business_partner_id').prop('required', false);
                } else if (type === 'business_partner') {
                    $('#account_group').hide();
                    $('#business_partner_group').show();
                    $('#account_id').prop('required', false);
                    $('#business_partner_id').prop('required', true);
                } else {
                    $('#account_group').hide();
                    $('#business_partner_group').hide();
                    $('#account_id').prop('required', false);
                    $('#business_partner_id').prop('required', false);
                }
            });

            // Initialize based on current values
            $('#statement_type').trigger('change');
        });
    </script>
@endsection
