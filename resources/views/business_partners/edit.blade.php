@extends('layouts.main')

@section('title_page')
    Edit Business Partner
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('business_partners.index') }}">Business Partners</a></li>
    <li class="breadcrumb-item"><a
            href="{{ route('business_partners.show', $businessPartner) }}">{{ $businessPartner->code }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <form method="post" action="{{ route('business_partners.update', $businessPartner) }}"
                id="business-partner-form">
                @csrf
                @method('PUT')

                <!-- Main Information Card -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edit Business Partner</h3>
                        <div class="card-tools">
                            <a href="{{ route('business_partners.show', $businessPartner) }}"
                                class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs" id="partner-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab"
                                    aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle"></i> General Information
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="contacts-tab" data-toggle="tab" href="#contacts" role="tab"
                                    aria-controls="contacts" aria-selected="false">
                                    <i class="fas fa-address-book"></i> Contact Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="addresses-tab" data-toggle="tab" href="#addresses" role="tab"
                                    aria-controls="addresses" aria-selected="false">
                                    <i class="fas fa-map-marker-alt"></i> Addresses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="taxation-tab" data-toggle="tab" href="#taxation" role="tab"
                                    aria-controls="taxation" aria-selected="false">
                                    <i class="fas fa-file-invoice"></i> Taxation & Terms
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="banking-tab" data-toggle="tab" href="#banking" role="tab"
                                    aria-controls="banking" aria-selected="false">
                                    <i class="fas fa-university"></i> Banking
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content pt-3" id="partner-tabs-content">
                            <!-- General Information Tab -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel"
                                aria-labelledby="general-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="code">Partner Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                                id="code" name="code"
                                                value="{{ old('code', $businessPartner->code) }}" required>
                                            @error('code')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Partner Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                id="name" name="name"
                                                value="{{ old('name', $businessPartner->name) }}" required>
                                            @error('name')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="partner_type">Partner Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control @error('partner_type') is-invalid @enderror"
                                                id="partner_type" name="partner_type" required>
                                                <option value="">-- Select Type --</option>
                                                <option value="customer"
                                                    {{ old('partner_type', $businessPartner->partner_type) == 'customer' ? 'selected' : '' }}>
                                                    Customer</option>
                                                <option value="supplier"
                                                    {{ old('partner_type', $businessPartner->partner_type) == 'supplier' ? 'selected' : '' }}>
                                                    Supplier</option>
                                                <option value="both"
                                                    {{ old('partner_type', $businessPartner->partner_type) == 'both' ? 'selected' : '' }}>
                                                    Both</option>
                                            </select>
                                            @error('partner_type')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control @error('status') is-invalid @enderror"
                                                id="status" name="status">
                                                <option value="active"
                                                    {{ old('status', $businessPartner->status) == 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ old('status', $businessPartner->status) == 'inactive' ? 'selected' : '' }}>
                                                    Inactive</option>
                                                <option value="suspended"
                                                    {{ old('status', $businessPartner->status) == 'suspended' ? 'selected' : '' }}>
                                                    Suspended</option>
                                            </select>
                                            @error('status')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="website">Website</label>
                                            <input type="url"
                                                class="form-control @error('website') is-invalid @enderror" id="website"
                                                name="website" value="{{ old('website', $businessPartner->website) }}"
                                                placeholder="https://example.com">
                                            @error('website')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $businessPartner->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Contacts Tab -->
                            <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Contact Persons</h5>
                                    <button type="button" class="btn btn-sm btn-success" id="add-contact">
                                        <i class="fas fa-plus"></i> Add Contact
                                    </button>
                                </div>

                                <div id="contacts-container">
                                    <!-- Existing contacts will be loaded here -->
                                </div>

                                <div class="alert alert-info mt-3" id="no-contacts-message"
                                    style="{{ $businessPartner->contacts->count() > 0 ? 'display: none;' : '' }}">
                                    <i class="fas fa-info-circle"></i> No contact persons added yet. Click "Add Contact" to
                                    add a contact person.
                                </div>
                            </div>

                            <!-- Addresses Tab -->
                            <div class="tab-pane fade" id="addresses" role="tabpanel" aria-labelledby="addresses-tab">
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Addresses</h5>
                                    <button type="button" class="btn btn-sm btn-success" id="add-address">
                                        <i class="fas fa-plus"></i> Add Address
                                    </button>
                                </div>

                                <div id="addresses-container">
                                    <!-- Existing addresses will be loaded here -->
                                </div>

                                <div class="alert alert-info mt-3" id="no-addresses-message"
                                    style="{{ $businessPartner->addresses->count() > 0 ? 'display: none;' : '' }}">
                                    <i class="fas fa-info-circle"></i> No addresses added yet. Click "Add Address" to add
                                    an address.
                                </div>
                            </div>

                            <!-- Taxation & Terms Tab -->
                            <div class="tab-pane fade" id="taxation" role="tabpanel" aria-labelledby="taxation-tab">
                                <!-- Accounting Section -->
                                <h6>Accounting</h6>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="account_id">GL Account</label>
                                            <select class="form-control @error('account_id') is-invalid @enderror"
                                                id="account_id" name="account_id">
                                                <option value="">Select Account (Optional)</option>
                                                @php
                                                    $accounts = \App\Models\Accounting\Account::orderBy('code')->get();
                                                @endphp
                                                @foreach ($accounts as $account)
                                                    <option value="{{ $account->id }}"
                                                        {{ old('account_id', $businessPartner->account_id) == $account->id ? 'selected' : '' }}>
                                                        {{ $account->code }} - {{ $account->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">
                                                Leave empty to use default account based on partner type
                                            </small>
                                            @error('account_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Tax Information Section -->
                                <h6 class="mt-4">Tax Information</h6>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="registration_number">NPWP (Tax Registration Number)</label>
                                            <input type="text"
                                                class="form-control @error('registration_number') is-invalid @enderror"
                                                id="registration_number" name="registration_number"
                                                value="{{ old('registration_number', $businessPartner->registration_number) }}">
                                            @error('registration_number')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_id">Additional Tax ID</label>
                                            <input type="text"
                                                class="form-control @error('tax_id') is-invalid @enderror" id="tax_id"
                                                name="tax_id" value="{{ old('tax_id', $businessPartner->tax_id) }}">
                                            @error('tax_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Tax Details Section -->
                                <h6 class="mt-4">Payment Terms</h6>
                                <hr>

                                @php
                                    $paymentTerms = $businessPartner->getDetailBySection('terms', 'payment_terms');
                                    $creditLimit = $businessPartner->getDetailBySection('financial', 'credit_limit');
                                    $discountPercentage = $businessPartner->getDetailBySection(
                                        'financial',
                                        'discount_percentage',
                                    );
                                @endphp

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment_terms">Payment Terms</label>
                                            <select class="form-control" id="payment_terms"
                                                name="details[0][field_value]">
                                                <option value="immediate"
                                                    {{ $paymentTerms && $paymentTerms->field_value == 'immediate' ? 'selected' : '' }}>
                                                    Immediate</option>
                                                <option value="net_15"
                                                    {{ $paymentTerms && $paymentTerms->field_value == 'net_15' ? 'selected' : '' }}>
                                                    Net 15 Days</option>
                                                <option value="net_30"
                                                    {{ !$paymentTerms || ($paymentTerms && $paymentTerms->field_value == 'net_30') ? 'selected' : '' }}>
                                                    Net 30 Days</option>
                                                <option value="net_45"
                                                    {{ $paymentTerms && $paymentTerms->field_value == 'net_45' ? 'selected' : '' }}>
                                                    Net 45 Days</option>
                                                <option value="net_60"
                                                    {{ $paymentTerms && $paymentTerms->field_value == 'net_60' ? 'selected' : '' }}>
                                                    Net 60 Days</option>
                                                <option value="custom"
                                                    {{ $paymentTerms && $paymentTerms->field_value == 'custom' ? 'selected' : '' }}>
                                                    Custom</option>
                                            </select>
                                            <input type="hidden" name="details[0][section_type]" value="terms">
                                            <input type="hidden" name="details[0][field_name]" value="payment_terms">
                                            <input type="hidden" name="details[0][field_type]" value="text">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="credit_limit">Credit Limit</label>
                                            <input type="number" class="form-control" id="credit_limit"
                                                name="details[1][field_value]"
                                                value="{{ $creditLimit ? $creditLimit->field_value : 0 }}">
                                            <input type="hidden" name="details[1][section_type]" value="financial">
                                            <input type="hidden" name="details[1][field_name]" value="credit_limit">
                                            <input type="hidden" name="details[1][field_type]" value="number">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="discount_percentage">Discount Percentage</label>
                                            <input type="number" class="form-control" id="discount_percentage"
                                                name="details[2][field_value]"
                                                value="{{ $discountPercentage ? $discountPercentage->field_value : 0 }}"
                                                step="0.01">
                                            <input type="hidden" name="details[2][section_type]" value="financial">
                                            <input type="hidden" name="details[2][field_name]"
                                                value="discount_percentage">
                                            <input type="hidden" name="details[2][field_type]" value="number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banking Tab -->
                            <div class="tab-pane fade" id="banking" role="tabpanel" aria-labelledby="banking-tab">
                                @php
                                    $bankName = $businessPartner->getDetailBySection('banking', 'bank_name');
                                    $accountNumber = $businessPartner->getDetailBySection('banking', 'account_number');
                                    $accountName = $businessPartner->getDetailBySection('banking', 'account_name');
                                    $swiftCode = $businessPartner->getDetailBySection('banking', 'swift_code');
                                @endphp

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_name">Bank Name</label>
                                            <input type="text" class="form-control" id="bank_name"
                                                name="details[3][field_value]"
                                                value="{{ $bankName ? $bankName->field_value : '' }}">
                                            <input type="hidden" name="details[3][section_type]" value="banking">
                                            <input type="hidden" name="details[3][field_name]" value="bank_name">
                                            <input type="hidden" name="details[3][field_type]" value="text">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="account_number">Account Number</label>
                                            <input type="text" class="form-control" id="account_number"
                                                name="details[4][field_value]"
                                                value="{{ $accountNumber ? $accountNumber->field_value : '' }}">
                                            <input type="hidden" name="details[4][section_type]" value="banking">
                                            <input type="hidden" name="details[4][field_name]" value="account_number">
                                            <input type="hidden" name="details[4][field_type]" value="text">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="account_name">Account Name</label>
                                            <input type="text" class="form-control" id="account_name"
                                                name="details[5][field_value]"
                                                value="{{ $accountName ? $accountName->field_value : '' }}">
                                            <input type="hidden" name="details[5][section_type]" value="banking">
                                            <input type="hidden" name="details[5][field_name]" value="account_name">
                                            <input type="hidden" name="details[5][field_type]" value="text">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="swift_code">SWIFT/BIC Code</label>
                                            <input type="text" class="form-control" id="swift_code"
                                                name="details[6][field_value]"
                                                value="{{ $swiftCode ? $swiftCode->field_value : '' }}">
                                            <input type="hidden" name="details[6][section_type]" value="banking">
                                            <input type="hidden" name="details[6][field_name]" value="swift_code">
                                            <input type="hidden" name="details[6][field_type]" value="text">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Business Partner
                        </button>
                        <a href="{{ route('business_partners.show', $businessPartner) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Contact Template (hidden) -->
    <template id="contact-template">
        <div class="card card-outline card-info contact-item mb-3">
            <div class="card-header">
                <h3 class="card-title">Contact Person</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool remove-contact" title="Remove Contact">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contact Type <span class="text-danger">*</span></label>
                            <select class="form-control contact-type" name="contacts[__INDEX__][contact_type]" required>
                                <option value="primary">Primary Contact</option>
                                <option value="billing">Billing Contact</option>
                                <option value="shipping">Shipping Contact</option>
                                <option value="technical">Technical Contact</option>
                                <option value="sales">Sales Contact</option>
                                <option value="support">Support Contact</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control contact-name" name="contacts[__INDEX__][name]"
                                required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" class="form-control" name="contacts[__INDEX__][position]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="contacts[__INDEX__][email]">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="contacts[__INDEX__][phone]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Mobile</label>
                            <input type="text" class="form-control" name="contacts[__INDEX__][mobile]">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="form-control" name="contacts[__INDEX__][notes]" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input is-primary-contact"
                            id="is_primary_contact___INDEX__" name="contacts[__INDEX__][is_primary]" value="1">
                        <label class="custom-control-label" for="is_primary_contact___INDEX__">Set as Primary
                            Contact</label>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Address Template (hidden) -->
    <template id="address-template">
        <div class="card card-outline card-info address-item mb-3">
            <div class="card-header">
                <h3 class="card-title">Address</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool remove-address" title="Remove Address">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address Type <span class="text-danger">*</span></label>
                            <select class="form-control address-type" name="addresses[__INDEX__][address_type]" required>
                                <option value="billing">Billing Address</option>
                                <option value="shipping">Shipping Address</option>
                                <option value="registered">Registered Office</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="office">Office</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="addresses[__INDEX__][address_line_1]"
                                required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address Line 2</label>
                            <input type="text" class="form-control" name="addresses[__INDEX__][address_line_2]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="addresses[__INDEX__][city]" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>State/Province</label>
                            <input type="text" class="form-control" name="addresses[__INDEX__][state_province]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" class="form-control" name="addresses[__INDEX__][postal_code]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" class="form-control" name="addresses[__INDEX__][country]"
                                value="Indonesia">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="form-control" name="addresses[__INDEX__][notes]" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input is-primary-address"
                            id="is_primary_address___INDEX__" name="addresses[__INDEX__][is_primary]" value="1">
                        <label class="custom-control-label" for="is_primary_address___INDEX__">Set as Primary
                            Address</label>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('styles')
    <style>
        .tab-pane {
            padding: 20px 0;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            let contactIndex = {{ $businessPartner->contacts->count() }};
            let addressIndex = {{ $businessPartner->addresses->count() }};

            // Add contact
            $('#add-contact').on('click', function() {
                const template = document.getElementById('contact-template').innerHTML;
                const newContact = template
                    .replace(/__INDEX__/g, contactIndex)
                    .replace(/___INDEX__/g, contactIndex);

                $('#contacts-container').append(newContact);
                $('#no-contacts-message').hide();

                contactIndex++;

                // Attach event handlers to the new contact
                initContactEvents();
            });

            // Add address
            $('#add-address').on('click', function() {
                const template = document.getElementById('address-template').innerHTML;
                const newAddress = template
                    .replace(/__INDEX__/g, addressIndex)
                    .replace(/___INDEX__/g, addressIndex);

                $('#addresses-container').append(newAddress);
                $('#no-addresses-message').hide();

                addressIndex++;

                // Attach event handlers to the new address
                initAddressEvents();
            });

            // Initialize contact events
            function initContactEvents() {
                // Remove contact
                $('.remove-contact').off('click').on('click', function() {
                    $(this).closest('.contact-item').remove();

                    if ($('#contacts-container').children().length === 0) {
                        $('#no-contacts-message').show();
                    }
                });

                // Handle primary contact radio-like behavior
                $('.is-primary-contact').off('change').on('change', function() {
                    if ($(this).prop('checked')) {
                        $('.is-primary-contact').not(this).prop('checked', false);
                    }
                });
            }

            // Initialize address events
            function initAddressEvents() {
                // Remove address
                $('.remove-address').off('click').on('click', function() {
                    $(this).closest('.address-item').remove();

                    if ($('#addresses-container').children().length === 0) {
                        $('#no-addresses-message').show();
                    }
                });

                // Handle primary address radio-like behavior
                $('.is-primary-address').off('change').on('change', function() {
                    if ($(this).prop('checked')) {
                        $('.is-primary-address').not(this).prop('checked', false);
                    }
                });
            }

            // Load existing contacts
            @foreach ($businessPartner->contacts as $index => $contact)
                // Create contact card
                const contactTemplate = document.getElementById('contact-template').innerHTML;
                const contactHtml = contactTemplate
                    .replace(/__INDEX__/g, {{ $index }})
                    .replace(/___INDEX__/g, {{ $index }});

                $('#contacts-container').append(contactHtml);

                // Set values
                $('select[name="contacts[{{ $index }}][contact_type]"]').val(
                    '{{ $contact->contact_type }}');
                $('input[name="contacts[{{ $index }}][name]"]').val('{{ $contact->name }}');
                $('input[name="contacts[{{ $index }}][position]"]').val('{{ $contact->position }}');
                $('input[name="contacts[{{ $index }}][email]"]').val('{{ $contact->email }}');
                $('input[name="contacts[{{ $index }}][phone]"]').val('{{ $contact->phone }}');
                $('input[name="contacts[{{ $index }}][mobile]"]').val('{{ $contact->mobile }}');
                $('textarea[name="contacts[{{ $index }}][notes]"]').val('{{ $contact->notes }}');

                @if ($contact->is_primary)
                    $('input[name="contacts[{{ $index }}][is_primary]"]').prop('checked', true);
                @endif
            @endforeach

            // Load existing addresses
            @foreach ($businessPartner->addresses as $index => $address)
                // Create address card
                const addressTemplate = document.getElementById('address-template').innerHTML;
                const addressHtml = addressTemplate
                    .replace(/__INDEX__/g, {{ $index }})
                    .replace(/___INDEX__/g, {{ $index }});

                $('#addresses-container').append(addressHtml);

                // Set values
                $('select[name="addresses[{{ $index }}][address_type]"]').val(
                    '{{ $address->address_type }}');
                $('input[name="addresses[{{ $index }}][address_line_1]"]').val(
                    '{{ $address->address_line_1 }}');
                $('input[name="addresses[{{ $index }}][address_line_2]"]').val(
                    '{{ $address->address_line_2 }}');
                $('input[name="addresses[{{ $index }}][city]"]').val('{{ $address->city }}');
                $('input[name="addresses[{{ $index }}][state_province]"]').val(
                    '{{ $address->state_province }}');
                $('input[name="addresses[{{ $index }}][postal_code]"]').val('{{ $address->postal_code }}');
                $('input[name="addresses[{{ $index }}][country]"]').val('{{ $address->country }}');
                $('textarea[name="addresses[{{ $index }}][notes]"]').val('{{ $address->notes }}');

                @if ($address->is_primary)
                    $('input[name="addresses[{{ $index }}][is_primary]"]').prop('checked', true);
                @endif
            @endforeach

            // Initialize events for existing items
            initContactEvents();
            initAddressEvents();
        });
    </script>
@endpush
