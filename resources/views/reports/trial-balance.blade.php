@extends('layouts.main')

@section('title_page')
    Trial Balance
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Trial Balance</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">Trial Balance Report</h3>
                    </div>
                    <form class="form-inline" id="form">
                        <input type="date" name="date" class="form-control form-control-sm mr-1"
                            value="{{ now()->toDateString() }}" />
                        <button class="btn btn-primary btn-sm" type="submit">Load</button>
                    </form>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped" id="tb">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Currencies</th>
                                <th>Debit (IDR)</th>
                                <th>Credit (IDR)</th>
                                <th>Balance (IDR)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Totals</th>
                                <th id="tdebit">0</th>
                                <th id="tcredit">0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('form');
        const tbody = document.querySelector('#tb tbody');
        async function load() {
            const date = form.date.value;
            const res = await fetch(`/reports/trial-balance?date=${date}`);
            const data = await res.json();
            tbody.innerHTML = '';
            let tdebit = 0,
                tcredit = 0;
            data.rows.forEach(r => {
                tdebit += r.debit;
                tcredit += r.credit;
                const tr = document.createElement('tr');
                tr.innerHTML =
                    `<td>${r.code}</td><td>${r.name}</td><td>${r.currencies || 'IDR'}</td><td>${r.debit.toFixed(2)}</td><td>${r.credit.toFixed(2)}</td><td>${r.balance.toFixed(2)}</td>`;
                tbody.appendChild(tr);
            });
            document.getElementById('tdebit').innerText = tdebit.toFixed(2);
            document.getElementById('tcredit').innerText = tcredit.toFixed(2);
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            load();
        });
        load();
    </script>
@endsection
