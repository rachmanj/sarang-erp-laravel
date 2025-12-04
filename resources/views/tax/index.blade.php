@extends('layouts.app')

@section('title', 'Tax Compliance Dashboard')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tax Compliance Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Tax Compliance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Current Period Summary -->
            @if ($currentPeriod)
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    Current Tax Period: {{ $currentPeriod->period_name }}
                                </h3>
                                <div class="card-tools">
                                    <span
                                        class="badge badge-{{ $currentPeriod->status === 'open' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($currentPeriod->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-receipt"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Transactions</span>
                                                <span
                                                    class="info-box-number">{{ $summary['total_transactions'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i
                                                    class="fas fa-money-bill-wave"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Tax Amount</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($summary['total_tax_amount'] ?? 0, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-primary"><i class="fas fa-chart-line"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Taxable Amount</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($summary['total_taxable_amount'] ?? 0, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-{{ $summary['ppn_status_color'] ?? 'warning' }}"><i class="fas {{ $summary['ppn_status_icon'] ?? 'fa-percentage' }}"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">PPN Balance</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format(abs($summary['ppn_net'] ?? 0), 2) }}</span>
                                                <span class="badge badge-{{ $summary['ppn_status_color'] ?? 'warning' }} mt-1" style="font-size: 0.9rem; padding: 0.35em 0.65em;">
                                                    {{ $summary['ppn_status_label'] ?? 'Balance' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PPN Balance Detail Card -->
                <div class="row">
                    <div class="col-12">
                        <div class="card card-{{ $summary['ppn_status_color'] ?? 'info' }} card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas {{ $summary['ppn_status_icon'] ?? 'fa-percentage' }} mr-2"></i>
                                    PPN Balance Detail - {{ $summary['ppn_status_label'] ?? 'Balance' }}
                                </h3>
                                <div class="card-tools">
                                    <span class="badge badge-{{ $summary['ppn_status_color'] ?? 'info' }}" style="font-size: 1rem; padding: 0.4em 0.8em;">
                                        {{ $summary['ppn_status_label'] ?? 'Balance' }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="description-block border-right">
                                            <span class="description-percentage text-info">
                                                <i class="fas fa-arrow-down"></i> PPN Masukan
                                            </span>
                                            <h5 class="description-header text-info">Rp
                                                {{ number_format($summary['ppn_input'] ?? 0, 2) }}
                                            </h5>
                                            <span class="description-text">Input VAT (Dapat Dikreditkan)</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="description-block border-right">
                                            <span class="description-percentage text-danger">
                                                <i class="fas fa-arrow-up"></i> PPN Keluaran
                                            </span>
                                            <h5 class="description-header text-danger">Rp
                                                {{ number_format($summary['ppn_output'] ?? 0, 2) }}
                                            </h5>
                                            <span class="description-text">Output VAT (Kewajiban Pajak)</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="description-block">
                                            <span class="description-percentage text-{{ $summary['ppn_status_color'] ?? 'warning' }}">
                                                <i class="fas {{ $summary['ppn_status_icon'] ?? 'fa-balance-scale' }}"></i>
                                                {{ $summary['ppn_status_label'] ?? 'Balance' }}
                                            </span>
                                            <h5 class="description-header text-{{ $summary['ppn_status_color'] ?? 'warning' }}">
                                                @if(($summary['ppn_net'] ?? 0) < 0)
                                                    -Rp {{ number_format(abs($summary['ppn_net'] ?? 0), 2) }}
                                                @else
                                                    Rp {{ number_format($summary['ppn_net'] ?? 0, 2) }}
                                                @endif
                                            </h5>
                                            <span class="description-text">
                                                @if(($summary['ppn_net'] ?? 0) > 0)
                                                    <strong class="text-danger">Harus Dibayar ke Negara</strong>
                                                @elseif(($summary['ppn_net'] ?? 0) < 0)
                                                    <strong class="text-success">Dapat Dikompensasi</strong>
                                                @else
                                                    <strong class="text-info">Balance</strong>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-{{ $summary['ppn_status_color'] ?? 'info' }} alert-dismissible">
                                            <h5><i class="icon fas {{ $summary['ppn_status_icon'] ?? 'fa-info-circle' }}"></i> 
                                                Status PPN: {{ $summary['ppn_status_label'] ?? 'Balance' }}
                                            </h5>
                                            @if(($summary['ppn_net'] ?? 0) > 0)
                                                <p class="mb-0">
                                                    <strong>Kurang Bayar PPN:</strong> Anda memiliki kewajiban membayar PPN sebesar 
                                                    <strong>Rp {{ number_format($summary['ppn_net'] ?? 0, 2) }}</strong> ke negara. 
                                                    Pastikan untuk melakukan pembayaran sebelum batas waktu yang ditentukan.
                                                </p>
                                            @elseif(($summary['ppn_net'] ?? 0) < 0)
                                                <p class="mb-0">
                                                    <strong>Lebih Bayar PPN:</strong> Anda memiliki kelebihan bayar PPN sebesar 
                                                    <strong>Rp {{ number_format(abs($summary['ppn_net'] ?? 0), 2) }}</strong>. 
                                                    Kelebihan bayar ini dapat dikompensasikan ke periode berikutnya atau diminta pengembalian.
                                                </p>
                                            @else
                                                <p class="mb-0">
                                                    <strong>Balance:</strong> PPN Masukan dan PPN Keluaran sudah seimbang. Tidak ada kewajiban pembayaran atau kelebihan bayar.
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Tax Type Summary -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-2"></i>
                                Tax Summary by Type
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="description-block border-right">
                                        <span class="description-percentage text-success">
                                            <i class="fas fa-caret-up"></i> PPN
                                        </span>
                                        <h5 class="description-header">Rp
                                            {{ number_format(($summary['ppn_input'] ?? 0) + ($summary['ppn_output'] ?? 0), 2) }}
                                        </h5>
                                        <span class="description-text">VAT Tax</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="description-block">
                                        <span class="description-percentage text-info">
                                            <i class="fas fa-caret-up"></i> PPh
                                        </span>
                                        <h5 class="description-header">Rp
                                            {{ number_format(($summary['pph_21'] ?? 0) + ($summary['pph_22'] ?? 0) + ($summary['pph_23'] ?? 0) + ($summary['pph_26'] ?? 0) + ($summary['pph_4_2'] ?? 0), 2) }}
                                        </h5>
                                        <span class="description-text">Income Tax</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tax Type</th>
                                                    <th class="text-right">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-arrow-down text-info mr-1"></i>
                                                        PPN Masukan (Input)
                                                    </td>
                                                    <td class="text-right text-info">Rp
                                                        {{ number_format($summary['ppn_input'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-arrow-up text-danger mr-1"></i>
                                                        PPN Keluaran (Output)
                                                    </td>
                                                    <td class="text-right text-danger">Rp
                                                        {{ number_format($summary['ppn_output'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr class="bg-{{ $summary['ppn_status_color'] ?? 'light' }}">
                                                    <td>
                                                        <strong>
                                                            <i class="fas {{ $summary['ppn_status_icon'] ?? 'fa-balance-scale' }} text-{{ $summary['ppn_status_color'] ?? 'warning' }} mr-1"></i>
                                                            {{ $summary['ppn_status_label'] ?? 'PPN Net' }}
                                                        </strong>
                                                    </td>
                                                    <td class="text-right">
                                                        <strong class="text-{{ $summary['ppn_status_color'] ?? 'warning' }}">
                                                            @if(($summary['ppn_net'] ?? 0) < 0)
                                                                -Rp {{ number_format(abs($summary['ppn_net'] ?? 0), 2) }}
                                                            @else
                                                                Rp {{ number_format($summary['ppn_net'] ?? 0, 2) }}
                                                            @endif
                                                        </strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>PPh 21</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($summary['pph_21'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>PPh 22</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($summary['pph_22'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>PPh 23</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($summary['pph_23'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>PPh 26</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($summary['pph_26'] ?? 0, 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>PPh 4(2)</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($summary['pph_4_2'] ?? 0, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Overdue Items
                            </h3>
                        </div>
                        <div class="card-body">
                            @if ($overdueTransactions->count() > 0)
                                <div class="alert alert-warning">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Overdue Transactions!</h5>
                                    You have {{ $overdueTransactions->count() }} overdue tax transactions.
                                    <a href="{{ route('tax.transactions') }}" class="btn btn-warning btn-sm ml-2">
                                        View Transactions
                                    </a>
                                </div>
                            @endif

                            @if ($overdueReports->count() > 0)
                                <div class="alert alert-danger">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Overdue Reports!</h5>
                                    You have {{ $overdueReports->count() }} overdue tax reports.
                                    <a href="{{ route('tax.reports') }}" class="btn btn-danger btn-sm ml-2">
                                        View Reports
                                    </a>
                                </div>
                            @endif

                            @if ($overdueTransactions->count() == 0 && $overdueReports->count() == 0)
                                <div class="alert alert-success">
                                    <h5><i class="icon fas fa-check"></i> All Up to Date!</h5>
                                    No overdue transactions or reports found.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tax Calendar -->
            @if ($taxCalendar && count($taxCalendar) > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calendar mr-2"></i>
                                    Upcoming Tax Deadlines
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($taxCalendar as $deadline)
                                        <div class="col-md-6">
                                            <div
                                                class="card card-outline card-{{ $deadline['event_type'] === 'deadline' ? 'warning' : 'info' }}">
                                                <div class="card-header">
                                                    <h3 class="card-title">
                                                        <i class="fas fa-calendar-day mr-2"></i>
                                                        {{ $deadline['event_name'] }}
                                                    </h3>
                                                    <div class="card-tools">
                                                        <span
                                                            class="badge badge-{{ $deadline['event_type'] === 'deadline' ? 'warning' : 'info' }}">
                                                            {{ \Carbon\Carbon::parse($deadline['date'])->format('d M Y') }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <p class="text-muted">{{ $deadline['description'] }}</p>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($deadline['date'])->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt mr-2"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="{{ route('tax.transactions.create') }}" class="btn btn-primary btn-block">
                                        <i class="fas fa-plus mr-2"></i>
                                        Create Tax Transaction
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('tax.transactions') }}" class="btn btn-info btn-block">
                                        <i class="fas fa-list mr-2"></i>
                                        View Transactions
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('tax.reports') }}" class="btn btn-success btn-block">
                                        <i class="fas fa-file-alt mr-2"></i>
                                        Tax Reports
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('tax.settings') }}" class="btn btn-warning btn-block">
                                        <i class="fas fa-cog mr-2"></i>
                                        Tax Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Auto-refresh dashboard every 5 minutes
            setInterval(function() {
                location.reload();
            }, 300000);
        });
    </script>
@endpush
