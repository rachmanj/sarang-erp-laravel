@extends('layouts.main')

@section('title_page')
    AR Aging
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">AR Aging</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">AR Aging</h3>
                            <form method="get" class="form-inline">
                                <input type="date" name="as_of" value="{{ request('as_of', now()->toDateString()) }}"
                                    class="form-control form-control-sm mr-2">
                                <label class="mr-2"><input type="checkbox" name="overdue" value="1"
                                        {{ request('overdue') ? 'checked' : '' }}> Overdue only</label>
                                <button class="btn btn-sm btn-secondary mr-2">Apply</button>
                                <a class="btn btn-sm btn-outline-success mr-2"
                                    href="{{ route('reports.ar-aging', array_merge(request()->query(), ['export' => 'csv'])) }}">CSV</a>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('reports.ar-aging', array_merge(request()->query(), ['export' => 'pdf'])) }}"
                                    target="_blank">PDF</a>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-right">Current</th>
                                        <th class="text-right">31-60</th>
                                        <th class="text-right">61-90</th>
                                        <th class="text-right">91+</th>
                                        <th class="text-right">Total</th>
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
                as_of: '{{ request('as_of', now()->toDateString()) }}',
                overdue: '{{ request('overdue') ? 1 : 0 }}'
            });
            const res = await fetch(`{{ route('reports.ar-aging') }}?${params.toString()}`, {
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
      <td>${(r.customer_name||('#'+r.customer_id))}</td>
      <td class="text-right">${r.current.toFixed(2)}</td>
      <td class="text-right">${r.d31_60.toFixed(2)}</td>
      <td class="text-right">${r.d61_90.toFixed(2)}</td>
      <td class="text-right">${r.d91_plus.toFixed(2)}</td>
      <td class="text-right">${r.total.toFixed(2)}</td>
    `;
                tbody.appendChild(tr);
            });
        });
    </script>
@endpush
