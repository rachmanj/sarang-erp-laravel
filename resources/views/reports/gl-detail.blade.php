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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">GL Detail Report</h3>
                    </div>
                    <form class="form-inline" id="form">
                        <label class="mr-2">From <input type="date" name="from"
                                class="form-control form-control-sm ml-1"
                                value="{{ now()->startOfMonth()->toDateString() }}" /></label>
                        <label class="mr-2">To <input type="date" name="to"
                                class="form-control form-control-sm ml-1" value="{{ now()->toDateString() }}" /></label>
                        <button class="btn btn-primary btn-sm" type="submit">Load</button>
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
        async function load() {
            const params = new URLSearchParams({
                from: form.from.value,
                to: form.to.value
            });
            const res = await fetch(`/reports/gl-detail?${params.toString()}`);
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
        load();
    </script>
@endsection
