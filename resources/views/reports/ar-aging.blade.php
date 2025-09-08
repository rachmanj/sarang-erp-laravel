@extends('layouts.main')

@section('title_page')
    AR Aging
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">AR Aging</li>
@endsection

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Accounts Receivable Aging</h4>
        <form id="form" class="mb-3">
            <label>As of <input type="date" name="as_of" value="{{ now()->toDateString() }}" /></label>
            <label class="ml-2"><input type="checkbox" name="overdue" /> Overdue only</label>
            <button class="btn btn-primary btn-sm" type="submit">Load</button>
        </form>
        <table class="table table-striped table-sm" id="tb">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Current</th>
                    <th>31-60</th>
                    <th>61-90</th>
                    <th>91+</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th>Totals</th>
                    <th id="tcur">0</th>
                    <th id="t3160">0</th>
                    <th id="t6190">0</th>
                    <th id="t91">0</th>
                    <th id="ttotal">0</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <script>
        const form = document.getElementById('form');
        const tbody = document.querySelector('#tb tbody');
        async function load() {
            const as_of = form.as_of.value;
            const overdue = form.overdue.checked ? '1' : '';
            const res = await fetch(`/reports/ar-aging?as_of=${as_of}&overdue=${overdue}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            tbody.innerHTML = '';
            let tcur = 0,
                t3160 = 0,
                t6190 = 0,
                t91 = 0,
                ttotal = 0;
            data.rows.forEach(r => {
                tcur += r.current;
                t3160 += r.d31_60;
                t6190 += r.d61_90;
                t91 += r.d91_plus;
                ttotal += r.total;
                const name = r.customer_name ?? `#${r.customer_id}`;
                const tr = document.createElement('tr');
                const link = `{{ route('sales-invoices.index') }}?status=posted&q=${encodeURIComponent(name)}`;
                tr.innerHTML =
                    `<td><a href="${link}">${name}</a></td><td>${r.current.toFixed(2)}</td><td>${r.d31_60.toFixed(2)}</td><td>${r.d61_90.toFixed(2)}</td><td>${r.d91_plus.toFixed(2)}</td><td>${r.total.toFixed(2)}</td>`;
                tbody.appendChild(tr);
            });
            document.getElementById('tcur').innerText = tcur.toFixed(2);
            document.getElementById('t3160').innerText = t3160.toFixed(2);
            document.getElementById('t6190').innerText = t6190.toFixed(2);
            document.getElementById('t91').innerText = t91.toFixed(2);
            document.getElementById('ttotal').innerText = ttotal.toFixed(2);
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            load();
        });
        load();
    </script>
@endsection
