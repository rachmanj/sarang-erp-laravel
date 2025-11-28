@extends('layouts.main')

@section('title_page')
    {{ $businessPartner->name }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('business_partners.index') }}">Business Partners</a></li>
    <li class="breadcrumb-item active">{{ $businessPartner->code }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <!-- Main Information Card -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Business Partner Details</h3>
                    <div class="card-tools">
                        @can('business_partners.manage')
                            <a href="{{ route('business_partners.edit', $businessPartner) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endcan
                        <a href="{{ route('business_partners.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h2>{{ $businessPartner->name }} <small
                                    class="text-muted">({{ $businessPartner->code }})</small></h2>
                            <div class="mt-2">
                                @if ($businessPartner->partner_type == 'customer')
                                    <span class="badge badge-info">Customer</span>
                                @elseif($businessPartner->partner_type == 'supplier')
                                    <span class="badge badge-warning">Supplier</span>
                                @endif

                                @if ($businessPartner->status == 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($businessPartner->status == 'inactive')
                                    <span class="badge badge-secondary">Inactive</span>
                                @else
                                    <span class="badge badge-danger">Suspended</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            @if ($businessPartner->website)
                                <a href="{{ $businessPartner->website }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-globe"></i> Visit Website
                                </a>
                            @endif
                        </div>
                    </div>

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
                        <li class="nav-item">
                            <a class="nav-link" id="transactions-tab" data-toggle="tab" href="#transactions" role="tab"
                                aria-controls="transactions" aria-selected="false">
                                <i class="fas fa-exchange-alt"></i> Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="journal-history-tab" data-toggle="tab" href="#journal-history"
                                role="tab" aria-controls="journal-history" aria-selected="false">
                                <i class="fas fa-book"></i> Account Balance - Journal History
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
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">General Information</h5>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th style="width: 30%">Code</th>
                                                    <td>{{ $businessPartner->code }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Name</th>
                                                    <td>{{ $businessPartner->name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Partner Type</th>
                                                    <td>
                                                        @if ($businessPartner->partner_type == 'customer')
                                                            Customer
                                                        @elseif($businessPartner->partner_type == 'supplier')
                                                            Supplier
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>
                                                        @if ($businessPartner->status == 'active')
                                                            <span class="badge badge-success">Active</span>
                                                        @elseif($businessPartner->status == 'inactive')
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @else
                                                            <span class="badge badge-danger">Suspended</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Website</th>
                                                    <td>
                                                        @if ($businessPartner->website)
                                                            <a href="{{ $businessPartner->website }}"
                                                                target="_blank">{{ $businessPartner->website }}</a>
                                                        @else
                                                            <span class="text-muted">Not provided</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Primary Contact</h5>
                                            @if ($businessPartner->primaryContact)
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th style="width: 30%">Name</th>
                                                        <td>{{ $businessPartner->primaryContact->name }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Position</th>
                                                        <td>{{ $businessPartner->primaryContact->position ?? 'Not provided' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Email</th>
                                                        <td>
                                                            @if ($businessPartner->primaryContact->email)
                                                                <a
                                                                    href="mailto:{{ $businessPartner->primaryContact->email }}">{{ $businessPartner->primaryContact->email }}</a>
                                                            @else
                                                                <span class="text-muted">Not provided</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Phone</th>
                                                        <td>{{ $businessPartner->primaryContact->phone ?? 'Not provided' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Mobile</th>
                                                        <td>{{ $businessPartner->primaryContact->mobile ?? 'Not provided' }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            @else
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle"></i> No primary contact set.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($businessPartner->notes)
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Notes</h5>
                                        <p>{{ $businessPartner->notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Contacts Tab -->
                        <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
                            @if ($businessPartner->contacts->count() > 0)
                                <div class="row">
                                    @foreach ($businessPartner->contacts as $contact)
                                        <div class="col-md-6">
                                            <div
                                                class="card {{ $contact->is_primary ? 'card-outline card-primary' : '' }} mb-3">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        {{ ucfirst($contact->contact_type) }} Contact
                                                        @if ($contact->is_primary)
                                                            <span class="badge badge-primary">Primary</span>
                                                        @endif
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <h5>{{ $contact->name }}</h5>
                                                    @if ($contact->position)
                                                        <p class="text-muted">{{ $contact->position }}</p>
                                                    @endif

                                                    <div class="mt-3">
                                                        @if ($contact->email)
                                                            <p>
                                                                <i class="fas fa-envelope"></i>
                                                                <a
                                                                    href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                                            </p>
                                                        @endif

                                                        @if ($contact->phone)
                                                            <p>
                                                                <i class="fas fa-phone"></i> {{ $contact->phone }}
                                                            </p>
                                                        @endif

                                                        @if ($contact->mobile)
                                                            <p>
                                                                <i class="fas fa-mobile-alt"></i> {{ $contact->mobile }}
                                                            </p>
                                                        @endif
                                                    </div>

                                                    @if ($contact->notes)
                                                        <div class="mt-3">
                                                            <strong>Notes:</strong>
                                                            <p>{{ $contact->notes }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No contacts added yet.
                                </div>
                            @endif
                        </div>

                        <!-- Addresses Tab -->
                        <div class="tab-pane fade" id="addresses" role="tabpanel" aria-labelledby="addresses-tab">
                            @if ($businessPartner->addresses->count() > 0)
                                <div class="row">
                                    @foreach ($businessPartner->addresses as $address)
                                        <div class="col-md-6">
                                            <div
                                                class="card {{ $address->is_primary ? 'card-outline card-primary' : '' }} mb-3">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        {{ ucfirst($address->address_type) }} Address
                                                        @if ($address->is_primary)
                                                            <span class="badge badge-primary">Primary</span>
                                                        @endif
                                                    </h3>
                                                </div>
                                                <div class="card-body">
                                                    <address>
                                                        {{ $address->address_line_1 }}<br>
                                                        @if ($address->address_line_2)
                                                            {{ $address->address_line_2 }}<br>
                                                        @endif
                                                        {{ $address->city }}
                                                        @if ($address->state_province)
                                                            , {{ $address->state_province }}
                                                        @endif
                                                        @if ($address->postal_code)
                                                            {{ $address->postal_code }}
                                                        @endif
                                                        <br>
                                                        {{ $address->country ?? 'Indonesia' }}
                                                    </address>

                                                    @if ($address->notes)
                                                        <div class="mt-3">
                                                            <strong>Notes:</strong>
                                                            <p>{{ $address->notes }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No addresses added yet.
                                </div>
                            @endif
                        </div>

                        <!-- Taxation & Terms Tab -->
                        <div class="tab-pane fade" id="taxation" role="tabpanel" aria-labelledby="taxation-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Accounting</h5>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th style="width: 40%">GL Account</th>
                                                    <td>
                                                        @if ($businessPartner->account)
                                                            {{ $businessPartner->account->code }} -
                                                            {{ $businessPartner->account->name }}
                                                        @else
                                                            <span class="text-muted">Not assigned</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Default Account</th>
                                                    <td>
                                                        @php
                                                            $defaultAccount = $businessPartner->getDefaultAccount();
                                                        @endphp
                                                        @if ($defaultAccount)
                                                            {{ $defaultAccount->code }} - {{ $defaultAccount->name }}
                                                        @else
                                                            <span class="text-muted">Not available</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Tax Information</h5>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th style="width: 40%">NPWP (Tax Registration)</th>
                                                    <td>{{ $businessPartner->registration_number ?? 'Not provided' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Additional Tax ID</th>
                                                    <td>{{ $businessPartner->tax_id ?? 'Not provided' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Payment Terms</h5>
                                            <table class="table table-bordered">
                                                @php
                                                    $paymentTerms = $businessPartner->getDetailBySection(
                                                        'terms',
                                                        'payment_terms',
                                                    );
                                                    $creditLimit = $businessPartner->getDetailBySection(
                                                        'financial',
                                                        'credit_limit',
                                                    );
                                                    $discountPercentage = $businessPartner->getDetailBySection(
                                                        'financial',
                                                        'discount_percentage',
                                                    );
                                                @endphp

                                                <tr>
                                                    <th style="width: 40%">Payment Terms</th>
                                                    <td>
                                                        @if ($paymentTerms)
                                                            @php
                                                                $terms = [
                                                                    'immediate' => 'Immediate Payment',
                                                                    'net_15' => 'Net 15 Days',
                                                                    'net_30' => 'Net 30 Days',
                                                                    'net_45' => 'Net 45 Days',
                                                                    'net_60' => 'Net 60 Days',
                                                                    'custom' => 'Custom Terms',
                                                                ];
                                                            @endphp
                                                            {{ $terms[$paymentTerms->field_value] ?? $paymentTerms->field_value }}
                                                        @else
                                                            Net 30 Days (Default)
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Credit Limit</th>
                                                    <td>
                                                        @if ($creditLimit)
                                                            Rp {{ number_format($creditLimit->field_value, 2, ',', '.') }}
                                                        @else
                                                            Not set
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Discount Percentage</th>
                                                    <td>
                                                        @if ($discountPercentage)
                                                            {{ $discountPercentage->field_value }}%
                                                        @else
                                                            0%
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
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

                            @if ($bankName || $accountNumber || $accountName || $swiftCode)
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Banking Information</h5>
                                        <table class="table table-bordered">
                                            <tr>
                                                <th style="width: 30%">Bank Name</th>
                                                <td>{{ $bankName->field_value ?? 'Not provided' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Account Number</th>
                                                <td>{{ $accountNumber->field_value ?? 'Not provided' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Account Name</th>
                                                <td>{{ $accountName->field_value ?? 'Not provided' }}</td>
                                            </tr>
                                            <tr>
                                                <th>SWIFT/BIC Code</th>
                                                <td>{{ $swiftCode->field_value ?? 'Not provided' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No banking information provided.
                                </div>
                            @endif
                        </div>

                        <!-- Transactions Tab -->
                        <div class="tab-pane fade" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                            <div class="row">
                                @if ($businessPartner->is_supplier)
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">Recent Purchase Orders</h3>
                                            </div>
                                            <div class="card-body">
                                                @if ($businessPartner->purchaseOrders->count() > 0)
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Order No</th>
                                                                <th>Date</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($businessPartner->purchaseOrders->take(5) as $po)
                                                                <tr>
                                                                    <td>{{ $po->order_no }}</td>
                                                                    <td>{{ $po->date->format('d/m/Y') }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($po->total_amount, 2, ',', '.') }}
                                                                    </td>
                                                                    <td>{{ ucfirst($po->status) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> No purchase orders found.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">Recent Purchase Invoices</h3>
                                            </div>
                                            <div class="card-body">
                                                @if ($businessPartner->purchaseInvoices->count() > 0)
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice No</th>
                                                                <th>Date</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($businessPartner->purchaseInvoices->take(5) as $pi)
                                                                <tr>
                                                                    <td>{{ $pi->invoice_no }}</td>
                                                                    <td>{{ $pi->date->format('d/m/Y') }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($pi->total_amount, 2, ',', '.') }}
                                                                    </td>
                                                                    <td>{{ ucfirst($pi->status) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> No purchase invoices found.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($businessPartner->is_customer)
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">Recent Sales Orders</h3>
                                            </div>
                                            <div class="card-body">
                                                @if ($businessPartner->salesOrders->count() > 0)
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Order No</th>
                                                                <th>Date</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($businessPartner->salesOrders->take(5) as $so)
                                                                <tr>
                                                                    <td>{{ $so->order_no }}</td>
                                                                    <td>{{ $so->date->format('d/m/Y') }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($so->total_amount, 2, ',', '.') }}
                                                                    </td>
                                                                    <td>{{ ucfirst($so->status) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> No sales orders found.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">Recent Sales Invoices</h3>
                                            </div>
                                            <div class="card-body">
                                                @if ($businessPartner->salesInvoices->count() > 0)
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice No</th>
                                                                <th>Date</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($businessPartner->salesInvoices->take(5) as $si)
                                                                <tr>
                                                                    <td>{{ $si->invoice_no }}</td>
                                                                    <td>{{ $si->date->format('d/m/Y') }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($si->total_amount, 2, ',', '.') }}
                                                                    </td>
                                                                    <td>{{ ucfirst($si->status) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle"></i> No sales invoices found.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Journal History Tab -->
                        <div class="tab-pane fade" id="journal-history" role="tabpanel"
                            aria-labelledby="journal-history-tab">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Account Balance - Journal History</h3>
                                            <div class="card-tools">
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    id="refresh-journal-history">
                                                    <i class="fas fa-sync"></i> Refresh
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <!-- Date Range Filter -->
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="start-date">Start Date</label>
                                                    <input type="date" class="form-control" id="start-date"
                                                        value="{{ \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="end-date">End Date</label>
                                                    <input type="date" class="form-control" id="end-date"
                                                        value="{{ \Carbon\Carbon::now()->endOfYear()->format('Y-m-d') }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label>&nbsp;</label>
                                                    <div>
                                                        <button type="button" class="btn btn-primary"
                                                            id="filter-journal-history">
                                                            <i class="fas fa-filter"></i> Filter
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Summary Cards -->
                                            <div class="row mb-3" id="journal-summary">
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-info"><i
                                                                class="fas fa-wallet"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Opening Balance</span>
                                                            <span class="info-box-number" id="opening-balance">Rp 0</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-success"><i
                                                                class="fas fa-arrow-up"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Total Debits</span>
                                                            <span class="info-box-number" id="total-debits">Rp 0</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-warning"><i
                                                                class="fas fa-arrow-down"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Total Credits</span>
                                                            <span class="info-box-number" id="total-credits">Rp 0</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="info-box">
                                                        <span class="info-box-icon bg-primary"><i
                                                                class="fas fa-balance-scale"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">Closing Balance</span>
                                                            <span class="info-box-number" id="closing-balance">Rp 0</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Transactions Table -->
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped"
                                                    id="journal-history-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Posting Date</th>
                                                            <th>Create Date</th>
                                                            <th>Document Date</th>
                                                            <th>Type</th>
                                                            <th>Document No</th>
                                                            <th>Journal No</th>
                                                            <th>Description</th>
                                                            <th>Offset Account</th>
                                                            <th>Account Name</th>
                                                            <th>Debit</th>
                                                            <th>Credit</th>
                                                            <th>Cumulative Balance</th>
                                                            <th>Project</th>
                                                            <th>Created By</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Data will be loaded via AJAX -->
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Pagination -->
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div id="journal-pagination-info"></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <nav aria-label="Journal History Pagination">
                                                        <ul class="pagination justify-content-end"
                                                            id="journal-pagination">
                                                            <!-- Pagination will be loaded via AJAX -->
                                                        </ul>
                                                    </nav>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail Widget -->
            @can('admin.view')
            <div class="row mt-3">
                <div class="col-12">
                    <x-audit-trail-widget 
                        entity-type="business_partner" 
                        :entity-id="$businessPartner->id" 
                        :limit="10" 
                        :collapsible="true" />
                </div>
            </div>
            @endcan
        </div>
    </section>
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
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Tab switching functionality
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                // Handle any tab-specific functionality here
                var target = $(e.target).attr("href");
                console.log('Switched to tab:', target);

                // Load journal history when tab is shown
                if (target === '#journal-history') {
                    loadJournalHistory();
                }
            });

            // Journal History functionality
            let currentPage = 1;
            const perPage = 25;

            function loadJournalHistory(page = 1) {
                const startDate = $('#start-date').val();
                const endDate = $('#end-date').val();

                $.ajax({
                    url: '{{ route('business_partners.journal_history', $businessPartner->id) }}',
                    method: 'GET',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        page: page,
                        per_page: perPage
                    },
                    beforeSend: function() {
                        $('#journal-history-table tbody').html(
                            '<tr><td colspan="14" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>'
                            );
                    },
                    success: function(response) {
                        updateSummaryCards(response);
                        updateTransactionsTable(response.transactions);
                        updatePagination(response.pagination);
                        currentPage = page;
                    },
                    error: function(xhr) {
                        console.error('Error loading journal history:', xhr);
                        $('#journal-history-table tbody').html(
                            '<tr><td colspan="14" class="text-center text-danger">Error loading data</td></tr>'
                            );
                    }
                });
            }

            function updateSummaryCards(data) {
                $('#opening-balance').text(formatCurrency(data.opening_balance));
                $('#total-debits').text(formatCurrency(data.total_debits));
                $('#total-credits').text(formatCurrency(data.total_credits));
                $('#closing-balance').text(formatCurrency(data.closing_balance));
            }

            function updateTransactionsTable(transactions) {
                let tbody = $('#journal-history-table tbody');
                tbody.empty();

                if (transactions.length === 0) {
                    tbody.html(
                        '<tr><td colspan="14" class="text-center text-muted">No transactions found</td></tr>');
                    return;
                }

                transactions.forEach(function(transaction) {
                    const row = `
                        <tr>
                            <td>${formatDate(transaction.posting_date)}</td>
                            <td>${formatDateTime(transaction.create_date)}</td>
                            <td>${formatDate(transaction.document_date)}</td>
                            <td><span class="badge badge-info">${transaction.type}</span></td>
                            <td>${transaction.document_no || '-'}</td>
                            <td>${transaction.journal_no || '-'}</td>
                            <td>${transaction.description || '-'}</td>
                            <td>${transaction.offset_account || '-'}</td>
                            <td>${transaction.account_name || '-'}</td>
                            <td class="text-right">${transaction.debit > 0 ? formatCurrency(transaction.debit) : '-'}</td>
                            <td class="text-right">${transaction.credit > 0 ? formatCurrency(transaction.credit) : '-'}</td>
                            <td class="text-right font-weight-bold">${formatCurrency(transaction.cumulative_balance)}</td>
                            <td>${transaction.project_dept || '-'}</td>
                            <td>${transaction.created_by || '-'}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }

            function updatePagination(pagination) {
                const info =
                    `Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total_records)} of ${pagination.total_records} entries`;
                $('#journal-pagination-info').text(info);

                let paginationHtml = '';

                // Previous button
                if (pagination.current_page > 1) {
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a></li>`;
                }

                // Page numbers
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === pagination.current_page ? 'active' : '';
                    paginationHtml +=
                        `<li class="page-item ${activeClass}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }

                // Next button
                if (pagination.current_page < pagination.total_pages) {
                    paginationHtml +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a></li>`;
                }

                $('#journal-pagination').html(paginationHtml);
            }

            function formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(amount);
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                return new Date(dateString).toLocaleDateString('id-ID');
            }

            function formatDateTime(dateString) {
                if (!dateString) return '-';
                return new Date(dateString).toLocaleString('id-ID');
            }

            // Event handlers
            $('#filter-journal-history').click(function() {
                loadJournalHistory(1);
            });

            $('#refresh-journal-history').click(function() {
                loadJournalHistory(currentPage);
            });

            $(document).on('click', '#journal-pagination a', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                loadJournalHistory(page);
            });
        });
    </script>
@endpush
