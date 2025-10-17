@extends('layouts.app')

@section('title', 'Exchange Rates')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Exchange Rates</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Exchange Rates</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Exchange Rate Management</h3>
                            <div class="card-tools">
                                @can('exchange-rates.create')
                                    <a href="{{ route('exchange-rates.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Exchange Rate
                                    </a>
                                    <a href="{{ route('exchange-rates.daily-rates') }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-calendar"></i> Daily Rates
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="exchange-rates-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>From Currency</th>
                                        <th>To Currency</th>
                                        <th>Rate</th>
                                        <th>Effective Date</th>
                                        <th>Rate Type</th>
                                        <th>Source</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
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
            $('#exchange-rates-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('exchange-rates.data') }}",
                    type: 'GET'
                },
                columns: [{
                        data: 'from_currency_code',
                        name: 'from_currency_code'
                    },
                    {
                        data: 'to_currency_code',
                        name: 'to_currency_code'
                    },
                    {
                        data: 'rate',
                        name: 'rate'
                    },
                    {
                        data: 'effective_date',
                        name: 'effective_date'
                    },
                    {
                        data: 'rate_type',
                        name: 'rate_type'
                    },
                    {
                        data: 'source',
                        name: 'source'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [3, 'desc']
                ],
                pageLength: 25,
                responsive: true
            });
        });
    </script>
@endpush
