@extends('layouts.main')

@section('title_page')
    Cash Ledger
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Cash Ledger</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Cash Ledger</h3>
                            <form method="get" class="form-inline">
                                <input type="date" name="from" value="{{ request('from') }}"
                                    class="form-control form-control-sm mr-2">
                                <input type="date" name="to" value="{{ request('to') }}"
                                    class="form-control form-control-sm mr-2">
                                <button class="btn btn-sm btn-secondary mr-2">Apply</button>
                                <a class="btn btn-sm btn-outline-success mr-2"
                                    href="{{ route('reports.cash-ledger', array_merge(request()->query(), ['export' => 'csv'])) }}">CSV</a>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('reports.cash-ledger', array_merge(request()->query(), ['export' => 'pdf'])) }}"
                                    target="_blank">PDF</a>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="rows"></tbody>
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
        $(async function() {
            const params = new URLSearchParams({
                from: '{{ request('from') }}',
                to: '{{ request('to') }}'
            });
            const res = await fetch(`{{ route('reports.cash-ledger') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            const tbody = document.getElementById('rows');
            tbody.innerHTML = '';
            data.rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
      <td>${r.date}</td>
      <td>${r.description || ''}</td>
      <td class="text-right">${r.debit.toFixed(2)}</td>
      <td class="text-right">${r.credit.toFixed(2)}</td>
      <td class="text-right">${r.balance.toFixed(2)}</td>
    `;
                tbody.appendChild(tr);
            });
        });
    </script>
@endpush
