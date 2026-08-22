@extends('layouts.main')

@section('title_page')
    Audit Run #{{ $run->id }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('audit.index') }}">Audit Data</a></li>
    <li class="breadcrumb-item active">Run #{{ $run->id }}</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shield-alt"></i> Ringkasan Audit Run #{{ $run->id }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('audit.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Status</strong>
                        <p class="mb-0">
                            @if ($run->status === 'completed')
                                <span class="badge badge-success">Selesai</span>
                            @elseif ($run->status === 'running')
                                <span class="badge badge-primary">Berjalan</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($run->status) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <strong>Mulai</strong>
                        <p class="mb-0">{{ $run->started_at?->format('d/m/Y H:i:s') ?? '—' }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>Selesai</strong>
                        <p class="mb-0">{{ $run->finished_at?->format('d/m/Y H:i:s') ?? '—' }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>Pemicu</strong>
                        <p class="mb-0">{{ $run->triggered_by }}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <strong>Total Cek</strong>
                        <p class="mb-0">{{ $run->total_checks }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>Lulus</strong>
                        <p class="mb-0"><span class="badge badge-success">{{ $run->passed_checks }}</span></p>
                    </div>
                    <div class="col-md-3">
                        <strong>Gagal</strong>
                        <p class="mb-0"><span class="badge badge-danger">{{ $run->failed_checks }}</span></p>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Isu</strong>
                        <p class="mb-0">
                            @if ($run->total_issues > 0)
                                <span class="badge badge-danger">{{ $run->total_issues }}</span>
                            @else
                                <span class="badge badge-success">0</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list-check"></i> Hasil Pemeriksaan
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Kunci Cek</th>
                                <th>Nama Cek</th>
                                <th>Status</th>
                                <th class="text-center">Jumlah Isu</th>
                                <th>Detail Isu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($run->results as $result)
                                @php
                                    $issues = $result->details
                                        ? json_decode($result->details, true)
                                        : [];
                                    if (! is_array($issues)) {
                                        $issues = [];
                                    }
                                @endphp
                                <tr>
                                    <td><code>{{ $result->check_key }}</code></td>
                                    <td>{{ $result->check_name }}</td>
                                    <td>
                                        @if ($result->status === 'pass')
                                            <span class="badge badge-success">Lulus</span>
                                        @elseif ($result->status === 'fail')
                                            <span class="badge badge-danger">Gagal</span>
                                        @else
                                            <span class="badge badge-warning">{{ ucfirst($result->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($result->issue_count > 0)
                                            <span class="badge badge-danger">{{ $result->issue_count }}</span>
                                        @else
                                            <span class="badge badge-success">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($result->status === 'fail' && count($issues) > 0)
                                            <ul class="mb-0 pl-3">
                                                @foreach ($issues as $issue)
                                                    <li><small>{{ $issue }}</small></li>
                                                @endforeach
                                            </ul>
                                        @elseif ($result->status === 'fail')
                                            <span class="text-muted">—</span>
                                        @else
                                            <span class="text-muted">Tidak ada isu</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
