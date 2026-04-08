@extends('layouts.main')

@section('title_page')
    Cash Flow Statement
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Cash Flow Statement</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title">Statement of Cash Flows (Indirect)</h3>
                        <p class="text-muted small mb-0">Not the same as <a href="{{ route('reports.cash-ledger') }}">Cash Ledger</a>.
                            Prefixes: <code>config/cash_flow.php</code>.</p>
                    </div>
                    <form class="form-inline align-items-end" id="cf-form">
                        <label class="mr-2 mb-0">From
                            <input type="date" name="from" class="form-control form-control-sm ml-1"
                                value="{{ request('from', now()->startOfMonth()->toDateString()) }}" />
                        </label>
                        <label class="mr-2 mb-0">To
                            <input type="date" name="to" class="form-control form-control-sm ml-1"
                                value="{{ request('to', now()->toDateString()) }}" />
                        </label>
                        <label class="mr-2 mb-0 small">
                            <input type="checkbox" name="include_unposted" id="cf_include_unposted" value="1">
                            Include unposted journals
                        </label>
                        <button class="btn btn-primary btn-sm mr-2" type="submit">Load</button>
                        <a class="btn btn-sm btn-outline-success mr-2" id="cf-csv-link" href="#">CSV</a>
                        <a class="btn btn-sm btn-outline-primary" id="cf-pdf-link" href="#" target="_blank">PDF</a>
                    </form>
                </div>
                <div class="card-body">
                    <p class="small text-muted" id="cf-meta"></p>
                    <ul class="small text-muted" id="cf-notes"></ul>
                    <div id="cf-content"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const cfForm = document.getElementById('cf-form');

        function cfQuery() {
            const p = new URLSearchParams();
            p.set('from', cfForm.from.value);
            p.set('to', cfForm.to.value);
            if (document.getElementById('cf_include_unposted').checked) p.set('include_unposted', '1');
            return p.toString();
        }

        function cfUpdateExports() {
            const q = cfQuery();
            document.getElementById('cf-csv-link').href = '/reports/cash-flow-statement?' + q + '&export=csv';
            document.getElementById('cf-pdf-link').href = '/reports/cash-flow-statement?' + q + '&export=pdf';
        }

        function renderBlock(block) {
            let html = '<h5 class="mt-3">' + block.label + '</h5>';
            html += '<table class="table table-bordered table-sm"><tbody>';
            block.lines.forEach(l => {
                html += '<tr><td>' + l.label + '</td><td class="text-right">' + l.amount.toFixed(2) + '</td></tr>';
            });
            html += '<tr class="table-secondary"><th>Subtotal</th><th class="text-right">' + block.subtotal.toFixed(2) +
                '</th></tr>';
            html += '</tbody></table>';
            return html;
        }

        async function cfLoad() {
            cfUpdateExports();
            const res = await fetch('/reports/cash-flow-statement?' + cfQuery(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const d = await res.json();
            document.getElementById('cf-meta').textContent = d.from + ' → ' + d.to + ' (opening balances as of ' + d
                .begin_balance_date + ')' + (d.only_posted_journals ? ' · Posted only' : ' · Including unposted');
            const notesEl = document.getElementById('cf-notes');
            notesEl.innerHTML = '';
            (d.notes || []).forEach(n => {
                const li = document.createElement('li');
                li.textContent = n;
                notesEl.appendChild(li);
            });
            let html = renderBlock(d.operating);
            html += renderBlock(d.investing);
            html += renderBlock(d.financing);
            html += '<h5 class="mt-3">Summary &amp; reconciliation</h5>';
            html += '<table class="table table-sm table-bordered"><tbody>';
            html += '<tr><td>Net change in cash (computed)</td><td class="text-right">' + d.summary.net_change_computed
                .toFixed(2) + '</td></tr>';
            html += '<tr><td>Net change in cash &amp; bank (BS, configured prefixes)</td><td class="text-right">' + d
                .summary.net_change_cash_accounts.toFixed(2) + '</td></tr>';
            html += '<tr class="table-warning"><th>Reconciliation difference</th><th class="text-right">' + d.summary
                .reconciliation_difference.toFixed(2) + '</th></tr>';
            html += '</tbody></table>';
            document.getElementById('cf-content').innerHTML = html;
        }
        cfForm.addEventListener('submit', e => {
            e.preventDefault();
            cfLoad();
        });
        document.getElementById('cf_include_unposted').addEventListener('change', cfUpdateExports);
        cfForm.from.addEventListener('change', cfUpdateExports);
        cfForm.to.addEventListener('change', cfUpdateExports);
        cfLoad();
    </script>
@endsection
