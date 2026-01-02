@extends('layouts.main')

@section('title_page')
    Sales Quotation {{ $salesQuotation->quotation_no ?? '#' . $salesQuotation->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales-quotations.index') }}">Sales Quotations</a></li>
    <li class="breadcrumb-item active">{{ $salesQuotation->quotation_no ?? '#' . $salesQuotation->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">
                            <i class="fas fa-file-invoice mr-1"></i>
                            Sales Quotation {{ $salesQuotation->quotation_no ?? '#' . $salesQuotation->id }}
                        </h3>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-info mr-1"
                            onclick="showRelationshipMap('sales-quotations', {{ $salesQuotation->id }})">
                            <i class="fas fa-sitemap"></i> Relationship Map
                        </button>
                        <a href="{{ route('sales-quotations.index') }}" class="btn btn-sm btn-secondary mr-1">
                            <i class="fas fa-arrow-left"></i> Back to Quotations
                        </a>
                        @if($salesQuotation->status === 'draft' && $salesQuotation->approval_status === 'pending')
                            <a href="{{ route('sales-quotations.edit', $salesQuotation->id) }}" class="btn btn-sm btn-warning mr-1">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                        @if($salesQuotation->canBeApproved() && auth()->user()->can('sales.quotations.approve'))
                            <form method="post" action="{{ route('sales-quotations.approve', $salesQuotation->id) }}" class="d-inline mr-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this quotation?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="post" action="{{ route('sales-quotations.reject', $salesQuotation->id) }}" class="d-inline mr-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this quotation?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        @endif
                        @if($salesQuotation->canBeSent() && auth()->user()->can('sales.quotations.send'))
                            <form method="post" action="{{ route('sales-quotations.send', $salesQuotation->id) }}" class="d-inline mr-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Send this quotation to customer?')">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </form>
                        @endif
                        @if($salesQuotation->canBeConverted() && auth()->user()->can('sales.quotations.convert'))
                            <a href="{{ route('sales-quotations.convert', $salesQuotation->id) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-exchange-alt"></i> Convert to SO
                            </a>
                        @endif
                        @if(in_array($salesQuotation->status, ['accepted', 'sent', 'draft']) && auth()->user()->can('ar.invoices.create'))
                            <a href="{{ route('sales-invoices.create', ['quotation_id' => $salesQuotation->id]) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                            </a>
                        @endif
                        <a href="{{ route('sales-quotations.print', $salesQuotation->id) }}" class="btn btn-sm btn-info" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <b>Date</b>
                            <div>{{ $salesQuotation->date->format('d-M-Y') }}</div>
                        </div>
                        <div class="col-md-3">
                            <b>Valid Until</b>
                            <div>
                                @if($salesQuotation->valid_until_date)
                                    {{ $salesQuotation->valid_until_date->format('d-M-Y') }}
                                    @if($salesQuotation->is_expired)
                                        <span class="badge badge-danger">Expired</span>
                                    @elseif($salesQuotation->valid_until_date <= now()->addDays(3))
                                        <span class="badge badge-warning">Expiring Soon</span>
                                    @endif
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <b>Customer</b>
                            <div>{{ $salesQuotation->businessPartner->name ?? '#' . $salesQuotation->business_partner_id }}</div>
                        </div>
                        <div class="col-md-3">
                            <b>Status</b>
                            <div>
                                <span class="badge badge-{{ $salesQuotation->status === 'draft' ? 'secondary' : ($salesQuotation->status === 'sent' ? 'info' : ($salesQuotation->status === 'accepted' ? 'success' : ($salesQuotation->status === 'rejected' ? 'danger' : ($salesQuotation->status === 'expired' ? 'warning' : 'primary')))) }}">
                                    {{ strtoupper($salesQuotation->status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <b>Approval Status</b>
                            <div>
                                <span class="badge badge-{{ $salesQuotation->approval_status === 'pending' ? 'warning' : ($salesQuotation->approval_status === 'approved' ? 'success' : 'danger') }}">
                                    {{ strtoupper($salesQuotation->approval_status) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <b>Total Amount</b>
                            <div>Rp {{ number_format($salesQuotation->total_amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-3">
                            <b>Discount</b>
                            <div>
                                @if($salesQuotation->discount_percentage > 0)
                                    {{ number_format($salesQuotation->discount_percentage, 2) }}% (Rp {{ number_format($salesQuotation->discount_amount, 0, ',', '.') }})
                                @else
                                    Rp {{ number_format($salesQuotation->discount_amount, 0, ',', '.') }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <b>Net Amount</b>
                            <div><strong>Rp {{ number_format($salesQuotation->net_amount, 0, ',', '.') }}</strong></div>
                        </div>
                    </div>

                    @if($salesQuotation->reference_no)
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <b>Reference No:</b> {{ $salesQuotation->reference_no }}
                        </div>
                    </div>
                    @endif

                    @if($salesQuotation->description)
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <b>Description:</b> {{ $salesQuotation->description }}
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Item/Account</th>
                                    <th>Description</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">VAT</th>
                                    <th class="text-right">WTax</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($salesQuotation->lines as $l)
                                    <tr>
                                        <td>
                                            @if ($l->inventory_item_id && $l->inventoryItem)
                                                <strong>{{ $l->inventoryItem->code }}</strong><br>
                                                <small class="text-muted">{{ $l->inventoryItem->name }}</small>
                                            @elseif($l->item_code)
                                                <strong>{{ $l->item_code }}</strong><br>
                                                <small class="text-muted">{{ $l->item_name }}</small>
                                            @elseif($l->account_id && $l->account)
                                                <strong>{{ $l->account->code }}</strong><br>
                                                <small class="text-muted">{{ $l->account->name }}</small>
                                            @else
                                                <span class="text-muted">#{{ $l->account_id }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $l->description }}</td>
                                        <td class="text-right">{{ number_format($l->qty, 2) }}</td>
                                        <td class="text-right">Rp {{ number_format($l->unit_price, 0, ',', '.') }}</td>
                                        <td class="text-right">{{ $l->vat_rate ?? 0 }}%</td>
                                        <td class="text-right">{{ $l->wtax_rate ?? 0 }}%</td>
                                        <td class="text-right">Rp {{ number_format($l->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-right">Subtotal:</th>
                                    <th class="text-right">Rp {{ number_format($salesQuotation->total_amount, 0, ',', '.') }}</th>
                                </tr>
                                @if($salesQuotation->discount_amount > 0)
                                <tr>
                                    <th colspan="6" class="text-right">Discount:</th>
                                    <th class="text-right">- Rp {{ number_format($salesQuotation->discount_amount, 0, ',', '.') }}</th>
                                </tr>
                                @endif
                                <tr>
                                    <th colspan="6" class="text-right">Total:</th>
                                    <th class="text-right">Rp {{ number_format($salesQuotation->net_amount, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($salesQuotation->terms_conditions)
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>Terms & Conditions</h5>
                            <p>{!! nl2br(e($salesQuotation->terms_conditions)) !!}</p>
                        </div>
                    </div>
                    @endif

                    @if($salesQuotation->notes)
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h5>Notes</h5>
                            <p>{!! nl2br(e($salesQuotation->notes)) !!}</p>
                        </div>
                    </div>
                    @endif

                    @if($salesQuotation->convertedToSalesOrder)
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> This quotation has been converted to 
                                <a href="{{ route('sales-orders.show', $salesQuotation->convertedToSalesOrder->id) }}">
                                    Sales Order {{ $salesQuotation->convertedToSalesOrder->order_no }}
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Include Relationship Map Modal --}}
    @include('components.relationship-map-modal')
@endsection
