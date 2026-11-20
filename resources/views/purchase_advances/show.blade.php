@extends('layouts.main')

@section('title', 'Uang Muka Pembelian ' . ($advance->advance_no ?? '#' . $advance->id))

@section('title_page')
    Uang Muka Pembelian {{ $advance->advance_no ?? '#' . $advance->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchase-advances.index') }}">Uang Muka Pembelian</a></li>
    <li class="breadcrumb-item active">{{ $advance->advance_no ?? '#' . $advance->id }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">
                                Uang Muka Pembelian {{ $advance->advance_no ?? '#' . $advance->id }}
                                @php
                                    $badge = match ($advance->status) {
                                        'posted' => 'success',
                                        'cancelled' => 'secondary',
                                        default => 'warning',
                                    };
                                @endphp
                                <span class="badge badge-{{ $badge }} ml-2">{{ strtoupper($advance->status) }}</span>
                            </h3>
                            <div>
                                <a href="{{ route('purchase-advances.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Daftar
                                </a>
                                @can('ap.payments.post')
                                    @if ($advance->status === 'draft')
                                        <form method="post" action="{{ route('purchase-advances.post', $advance->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit"
                                                onclick="return confirm('Posting uang muka pembelian ini?')">Posting</button>
                                        </form>
                                    @endif
                                    @if ($advance->status === 'posted' && $advance->applications->isEmpty())
                                        <form method="post" action="{{ route('purchase-advances.cancel', $advance->id) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-danger" type="submit"
                                                onclick="return confirm('Batalkan uang muka yang sudah diposting? Jurnal akan dibalik.')">Batalkan</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3"><i class="fas fa-info-circle mr-1"></i> Informasi</h5>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <th width="40%">No. Dokumen:</th>
                                            <td><strong>{{ $advance->advance_no ?? 'N/A' }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal:</th>
                                            <td>{{ $advance->date->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Supplier:</th>
                                            <td>
                                                <a href="{{ route('business_partners.show', $advance->business_partner_id) }}">
                                                    {{ optional($advance->businessPartner)->name }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Entitas:</th>
                                            <td>{{ optional($advance->companyEntity)->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Purchase Order:</th>
                                            <td>
                                                @if ($advance->purchase_order_id)
                                                    <a href="{{ route('purchase-orders.show', $advance->purchase_order_id) }}">
                                                        {{ optional($advance->purchaseOrder)->order_no ?? '#'.$advance->purchase_order_id }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Keterangan:</th>
                                            <td>{{ $advance->description ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total:</th>
                                            <td><strong>{{ number_format((float) $advance->total_amount, 2) }}</strong></td>
                                        </tr>
                                        @if ($advance->status === 'posted')
                                            <tr>
                                                <th>Terpakai:</th>
                                                <td>{{ number_format((float) ($usedAmount ?? 0), 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Sisa uang muka:</th>
                                                <td><strong>{{ number_format((float) ($remainingAdvance ?? 0), 2) }}</strong></td>
                                            </tr>
                                        @endif
                                        @if ($advance->posted_at)
                                            <tr>
                                                <th>Diposting:</th>
                                                <td>{{ $advance->posted_at->format('d M Y H:i') }}</td>
                                            </tr>
                                        @endif
                                        @if ($advance->journal_id)
                                            <tr>
                                                <th>Jurnal:</th>
                                                <td>#{{ $advance->journal_id }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-2"><i class="fas fa-list mr-1"></i> Baris Kas/Bank</h5>
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Akun</th>
                                        <th>Keterangan</th>
                                        <th class="text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($advance->lines as $line)
                                        @php
                                            $account = DB::table('accounts')->find($line->account_id);
                                        @endphp
                                        <tr>
                                            <td>{{ $account ? $account->code.' — '.$account->name : '#'.$line->account_id }}</td>
                                            <td>{{ $line->description ?: '—' }}</td>
                                            <td class="text-right">{{ number_format((float) $line->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">Total</th>
                                        <th class="text-right">{{ number_format((float) $advance->total_amount, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>

                            @if ($advance->applications->isNotEmpty())
                                <h5 class="mt-4 mb-2"><i class="fas fa-file-invoice mr-1"></i> Pemakaian ke Faktur</h5>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Faktur</th>
                                            <th>Tanggal</th>
                                            <th class="text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($advance->applications as $app)
                                            <tr>
                                                <td>
                                                    @if ($app->invoice)
                                                        <a href="{{ route('purchase-invoices.show', $app->invoice_id) }}">
                                                            {{ $app->invoice->invoice_no }}
                                                        </a>
                                                    @else
                                                        #{{ $app->invoice_id }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $appDate = optional($app->payment)->date ?? $app->created_at;
                                                    @endphp
                                                    {{ $appDate ? \Carbon\Carbon::parse($appDate)->format('d M Y') : '—' }}
                                                </td>
                                                <td class="text-right">{{ number_format((float) $app->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @can('ap.payments.post')
                                @if ($advance->purchase_order_id)
                                    <p class="text-muted small mt-3 mb-0">
                                        <i class="fas fa-link mr-1"></i> Uang muka sudah tertaut ke purchase order.
                                    </p>
                                @elseif ($advance->applications->isNotEmpty())
                                    <p class="text-muted small mt-3 mb-0">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Tautan PO tidak tersedia karena uang muka sudah memiliki {{ $advance->applications->count() }} pemakaian.
                                    </p>
                                @elseif (isset($linkablePurchaseOrders) && $linkablePurchaseOrders->isNotEmpty())
                                    <h5 class="mt-4 mb-2"><i class="fas fa-link mr-1"></i> Tautkan ke Purchase Order</h5>
                                    <form method="post" action="{{ route('purchase-advances.link-po', $advance->id) }}" class="form-inline">
                                        @csrf
                                        <div class="form-group mr-2 mb-2">
                                            <select name="purchase_order_id" class="form-control form-control-sm" required>
                                                <option value="">— Pilih PO —</option>
                                                @foreach ($linkablePurchaseOrders as $po)
                                                    <option value="{{ $po->id }}">
                                                        {{ $po->order_no }} ({{ number_format((float) $po->total_amount, 2) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-primary mb-2"
                                            onclick="return confirm('Tautkan PO terpilih ke uang muka ini?')">Tautkan PO</button>
                                    </form>
                                @endif
                            @endcan

                            @can('ap.payments.post')
                                @if ($advance->status === 'posted' && ($remainingAdvance ?? 0) > 0.009 && isset($availableInvoices) && $availableInvoices->isNotEmpty())
                                    <h5 class="mt-4 mb-2"><i class="fas fa-hand-holding-usd mr-1"></i> Gunakan Uang Muka</h5>
                                    <form method="post" action="{{ route('purchase-advances.apply', $advance->id) }}" class="form-inline">
                                        @csrf
                                        <div class="form-group mr-2 mb-2">
                                            <label for="apply-invoice" class="sr-only">Faktur</label>
                                            <select name="invoice_id" id="apply-invoice" class="form-control form-control-sm" required>
                                                <option value="">— Pilih faktur —</option>
                                                @foreach ($availableInvoices as $inv)
                                                    <option value="{{ $inv->id }}">
                                                        {{ $inv->invoice_no }} (sisa {{ number_format((float) $inv->remaining_balance, 2) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mr-2 mb-2">
                                            <label for="apply-amount" class="sr-only">Jumlah</label>
                                            <input type="number" name="amount" id="apply-amount" class="form-control form-control-sm"
                                                step="0.01" min="0.01" max="{{ $remainingAdvance }}" placeholder="Jumlah" required>
                                        </div>
                                        <div class="form-group mr-2 mb-2">
                                            <label for="apply-date" class="sr-only">Tanggal</label>
                                            <input type="date" name="date" id="apply-date" class="form-control form-control-sm"
                                                value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-primary mb-2"
                                            onclick="return confirm('Pakai uang muka ke faktur terpilih?')">Gunakan</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection