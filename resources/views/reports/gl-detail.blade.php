@extends('layouts.main')

@section('title_page')
    GL Detail
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">GL Detail</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title">GL Detail Report</h3>
                    </div>
                    <form class="form-inline align-items-end" id="form">
                        <label class="mr-2 mb-0">From <input type="date" name="from"
                                class="form-control form-control-sm ml-1"
                                value="{{ now()->startOfMonth()->toDateString() }}" /></label>
                        <label class="mr-2 mb-0">To <input type="date" name="to"
                                class="form-control form-control-sm ml-1" value="{{ now()->toDateString() }}" /></label>
                        <label class="mr-2 mb-0 small">
                            <input type="checkbox" name="include_unposted" id="gl_include_unposted" value="1">
                            Include unposted journals
                        </label>
                        <button class="btn btn-primary btn-sm mr-2" type="submit">Load</button>
                        <a class="btn btn-sm btn-outline-success mr-2" id="gl-csv-link" href="#">CSV</a>
                        <a class="btn btn-sm btn-outline-primary" id="gl-pdf-link" href="#" target="_blank">PDF</a>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tb">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Journal</th>
                                <th>Account</th>
                                <th>Currency</th>
                                <th>Debit (IDR)</th>
                                <th>Credit (IDR)</th>
                                <th>Debit (FC)</th>
                                <th>Credit (FC)</th>
                                <th>Rate</th>
                                <th>Memo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('form');
        const tbody = document.querySelector('#tb tbody');

        function glQuery() {
            const p = new URLSearchParams({
                from: form.from.value,
                to: form.to.value
            });
            if (document.getElementById('gl_include_unposted').checked) p.set('include_unposted', '1');
            return p.toString();
        }

        function glUpdateExports() {
            const q = glQuery();
            document.getElementById('gl-csv-link').href = '/reports/gl-detail?' + q + '&export=csv';
            document.getElementById('gl-pdf-link').href = '/reports/gl-detail?' + q + '&export=pdf';
        }

        async function load() {
            glUpdateExports();
            const res = await fetch('/reports/gl-detail?' + glQuery(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            tbody.innerHTML = '';
            data.rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    `<td>${r.date}</td><td>${r.journal_desc ?? ''}</td><td>${r.account_code} - ${r.account_name}</td><td>${r.currency_code || 'IDR'}</td><td>${r.debit.toFixed(2)}</td><td>${r.credit.toFixed(2)}</td><td>${r.debit_foreign.toFixed(2)}</td><td>${r.credit_foreign.toFixed(2)}</td><td>${r.exchange_rate.toFixed(6)}</td><td>${r.memo ?? ''}</td>`;
                tbody.appendChild(tr);
            });
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            load();
        });
        document.getElementById('gl_include_unposted').addEventListener('change', glUpdateExports);
        form.from.addEventListener('change', glUpdateExports);
        form.to.addEventListener('change', glUpdateExports);
        load();
    </script>
@endsection
