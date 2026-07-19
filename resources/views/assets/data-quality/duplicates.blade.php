@extends('layouts.main')

@section('title', 'Duplicate Assets')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Duplicate Assets</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('assets.data-quality.index') }}">Data Quality</a>
                        </li>
                        <li class="breadcrumb-item active">Duplicates</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="mb-3">
                <a href="{{ route('assets.data-quality.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Data Quality
                </a>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Duplicate Names</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-right">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($duplicates['duplicate_names'] as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td class="text-right">{{ $item->count }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center p-3">No duplicate names</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Duplicate Serial Numbers</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th class="text-right">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($duplicates['duplicate_serials'] as $item)
                                        <tr>
                                            <td>{{ $item->serial_number }}</td>
                                            <td class="text-right">{{ $item->count }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center p-3">No duplicate serials</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Duplicate Codes</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th class="text-right">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($duplicates['duplicate_codes'] as $item)
                                        <tr>
                                            <td>{{ $item->code }}</td>
                                            <td class="text-right">{{ $item->count }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center p-3">No duplicate codes</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
