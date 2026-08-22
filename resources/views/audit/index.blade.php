@extends('layouts.main')

@section('title_page')
    Audit Data
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Audit Data</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($stats['total_runs']) }}</h3>
                        <p>Total Audit Run</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($stats['latest_issues']) }}</h3>
                        <p>Isu pada Run Terakhir</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box {{ $stats['latest_status'] === 'completed' ? 'bg-success' : ($stats['latest_status'] === 'running' ? 'bg-primary' : 'bg-secondary') }}">
                    <div class="inner">
                        <h3>
                            @if ($stats['latest_status'] === 'completed')
                                Selesai
                            @elseif ($stats['latest_status'] === 'running')
                                Berjalan
                            @elseif ($stats['latest_status'])
                                {{ ucfirst($stats['latest_status']) }}
                            @else
                                —
                            @endif
                        </h3>
                        <p>Status Run Terakhir</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shield-alt"></i> Riwayat Audit Run
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Mulai</th>
                                <th>Selesai</th>
                                <th>Status</th>
                                <th>Pemicu</th>
                                <th class="text-center">Cek</th>
                                <th class="text-center">Lulus</th>
                                <th class="text-center">Gagal</th>
                                <th class="text-center">Isu</th>
                                <th style="width: 100px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr>
                                    <td>{{ $run->id }}</td>
                                    <td>{{ $run->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $run->finished_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($run->status === 'completed')
                                            <span class="badge badge-success">Selesai</span>
                                        @elseif ($run->status === 'running')
                                            <span class="badge badge-primary">Berjalan</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($run->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $run->triggered_by }}</td>
                                    <td class="text-center">{{ $run->total_checks }}</td>
                                    <td class="text-center">{{ $run->passed_checks }}</td>
                                    <td class="text-center">{{ $run->failed_checks }}</td>
                                    <td class="text-center">
                                        @if ($run->total_issues > 0)
                                            <span class="badge badge-danger">{{ $run->total_issues }}</span>
                                        @else
                                            <span class="badge badge-success">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('audit.show', $run) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Belum ada audit run. Jalankan <code>php artisan audit:run</code> untuk memulai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($runs->hasPages())
                <div class="card-footer">
                    {{ $runs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
