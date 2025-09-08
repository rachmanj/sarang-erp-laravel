@extends('layouts.main')

@section('title_page')
    Cash Ledger
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Cash Ledger</li>
@endsection

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Cash Ledger</h4>
        <form id="form" class="mb-3">
            <label>Account
                <select name="account_id" class="form-control form-control-sm d-inline-block" style="width:240px">
                    @foreach (\DB::table('accounts')->where('code', 'like', '1.1.2%')->orderBy('code')->get(['id', 'code', 'name']) as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>From <input type="date" name="from" value="{{ now()->startOfMonth()->toDateString() }}" /></label>
            <label>To <input type="date" name="to" value="{{ now()->toDateString() }}" /></label>
            <button class="btn btn-primary btn-sm" type="submit">Load</button>
        </form>
        <table class="table table-striped table-sm" id="tb">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <script>
        const form = document.getElementById('form');
        const tbody = document.querySelector('#tb tbody');
        async function load() {
            const params = new URLSearchParams({
                account_id: form.account_id.value,
                from: form.from.value,
                to: form.to.value
            });
            const res = await fetch(`/reports/cash-ledger?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            tbody.innerHTML = '';
            if (data.opening_balance && data.opening_balance !== 0) {
                const tr0 = document.createElement('tr');
                tr0.innerHTML =
                    `<td>${form.from.value}</td><td>Opening Balance</td><td>0.00</td><td>0.00</td><td>${Number(data.opening_balance).toFixed(2)}</td>`;
                tbody.appendChild(tr0);
            }
            data.rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    `<td>${r.date}</td><td>${r.description ?? ''}</td><td>${r.debit.toFixed(2)}</td><td>${r.credit.toFixed(2)}</td><td>${r.balance.toFixed(2)}</td>`;
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
