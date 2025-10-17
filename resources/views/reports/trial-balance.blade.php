@extends('layouts.main')

@section('title_page')
    Trial Balance
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Trial Balance</li>
@endsection

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Trial Balance</h4>
        <form id="form" class="mb-3">
            <input type="date" name="date" value="{{ now()->toDateString() }}" />
            <button class="btn btn-primary btn-sm" type="submit">Load</button>
        </form>
        <table class="table table-striped table-sm" id="tb">
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
