@extends('layouts.main')

@section('title_page')
    Profit &amp; Loss
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Profit &amp; Loss</li>
@endsection

@push('css')
    <style>
        .report-statement .report-banner {
            border-bottom: 3px double #1e3a5f;
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
        }

        .report-statement .report-entity {
            font-size: 1.35rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: #1e3a5f;
        }

        .dark .report-statement .report-entity {
            color: #e2e8f0;
        }

        .report-statement .report-subtitle {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
        }

        .report-statement .table-report {
            font-size: 0.875rem;
        }

        .report-statement .table-report thead th {
            background: #f1f5f9;
            border-bottom: 2px solid #cbd5e1;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.06em;
            color: #475569;
        }

        .dark .report-statement .table-report thead th {
            background: #1e293b;
            color: #94a3b8;
            border-color: #334155;
        }

        .report-statement .table-report tfoot th {
            background: #e8eef5;
            font-weight: 600;
        }

        .dark .report-statement .table-report tfoot th {
            background: #0f172a;
        }

        .report-statement .report-row-parent td {
            font-weight: 600;
            color: #0f172a;
        }

        .dark .report-statement .report-row-parent td {
            color: #f1f5f9;
        }

        .report-statement .report-pl-highlight th,
        .report-statement .report-pl-highlight td {
            background: #eff6ff;
            font-weight: 600;
        }

        .dark .report-statement .report-pl-highlight th,
        .dark .report-statement .report-pl-highlight td {
            background: #172554;
        }
    </style>
@endpush

@section('content')
    <div class="row report-statement">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="report-banner flex-grow-1">
                            <div class="report-entity" id="pl-report-entity">—</div>
                            <div class="report-subtitle mt-1">Statement of Profit or Loss</div>
                        </div>
                        <form class="form-inline align-items-end flex-shrink-0" id="form">
                            <label class="mr-2 mb-0 small font-weight-normal">From
                                <input type="date" name="from" class="form-control form-control-sm ml-1"
                                    value="{{ request('from', now()->startOfMonth()->toDateString()) }}" />
                            </label>
                            <label class="mr-2 mb-0 small font-weight-normal">To
                                <input type="date" name="to" class="form-control form-control-sm ml-1"
                                    value="{{ request('to', now()->toDateString()) }}" />
                            </label>
                            <label class="mr-2 mb-0 small">
                                <input type="checkbox" name="include_unposted" id="pl_include_unposted" value="1"
                                    {{ request('include_unposted') ? 'checked' : '' }}>
                                Include unposted journals
                            </label>
                            <label class="mr-2 mb-0 small">
                                <input type="checkbox" name="show_zero" id="pl_show_zero" value="1"
                                    {{ request('show_zero') ? 'checked' : '' }}>
                                Show zero balances
                            </label>
                            <button class="btn btn-primary btn-sm mr-2" type="submit">Load</button>
                            <a class="btn btn-sm btn-outline-secondary mr-2" id="pl-csv-link" href="#">CSV</a>
                            <a class="btn btn-sm btn-outline-primary" id="pl-pdf-link" href="#" target="_blank">PDF</a>
                        </form>
                    </div>
                    <p class="text-muted small mb-0 mt-2">Sections follow COA roots: 4 revenue, 5 cost/HPP/direct, 6 operating,
                        7 other (by account type).</p>
                </div>
                <div class="card-body pt-2">
                    <p class="small text-muted mb-3" id="pl-meta"></p>
                    <div id="pl-content"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const plForm = document.getElementById('form');
        const plArea = document.getElementById('pl-content');
        const plMeta = document.getElementById('pl-meta');

        function plQuery() {
            const p = new URLSearchParams();
            p.set('from', plForm.from.value);
            p.set('to', plForm.to.value);
            if (document.getElementById('pl_include_unposted').checked) p.set('include_unposted', '1');
            if (document.getElementById('pl_show_zero').checked) p.set('show_zero', '1');
            return p.toString();
        }

        function plUpdateExports() {
            const q = plQuery();
            document.getElementById('pl-csv-link').href = '/reports/profit-loss?' + q + '&export=csv';
            document.getElementById('pl-pdf-link').href = '/reports/profit-loss?' + q + '&export=pdf';
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function plRenderRow(r) {
            const depth = r.depth || 0;
            const pad = depth * 1.1;
            const parent = r.is_parent;
            const trClass = parent ? 'report-row-parent' : '';
            const nameInner = (parent ?
                    '<span class="text-muted mr-1" title="Subtotal of child accounts">↳</span>' : '') +
                escapeHtml(r.name);
            return '<tr class="' + trClass + '">' +
                '<td class="text-monospace small align-middle">' + escapeHtml(r.code) + '</td>' +
                '<td class="align-middle" style="padding-left:' + pad + 'rem">' + nameInner + '</td>' +
                '<td class="text-right text-nowrap align-middle">' + Number(r.amount).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
        }

        async function plLoad() {
            plUpdateExports();
            const res = await fetch('/reports/profit-loss?' + plQuery(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            document.getElementById('pl-report-entity').textContent = data.entity_name || '—';
            plMeta.textContent = data.from + ' → ' + data.to + (data.only_posted_journals ? ' · Posted only' :
                ' · Including unposted') + (data.hide_zero_lines ? ' · Hiding zeros' : ' · Showing zeros');
            let html = '';
            for (const sec of data.sections) {
                html += '<h5 class="mt-4 mb-2 font-weight-bold text-secondary">' + escapeHtml(sec.label) + '</h5>';
                html += '<div class="table-responsive"><table class="table table-report table-bordered table-sm mb-0">' +
                    '<thead><tr><th style="width:9rem">Code</th><th>Description</th>' +
                    '<th class="text-right" style="width:12rem">Amount (IDR)</th></tr></thead><tbody>';
                for (const r of sec.rows) {
                    html += plRenderRow(r);
                }
                html += '</tbody><tfoot><tr><th colspan="2">Section total</th><th class="text-right">' + sec.total
                    .toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + '</th></tr></tfoot></table></div>';
            }
            html += '<div class="table-responsive mt-4"><table class="table table-sm table-bordered">' +
                '<tbody>';
            html += '<tr><th>Gross profit</th><td class="text-right">' + data.subtotals.gross_profit.toLocaleString(
                undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '<tr><th>Operating income</th><td class="text-right">' + data.subtotals.operating_income
                .toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '<tr class="report-pl-highlight"><th>Net income</th><td class="text-right">' + data.subtotals
                .net_income.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '</tbody></table></div>';
            plArea.innerHTML = html;
        }
        plForm.addEventListener('submit', (e) => {
            e.preventDefault();
            plLoad();
        });
        ['pl_include_unposted', 'pl_show_zero'].forEach(id => {
            document.getElementById(id).addEventListener('change', plUpdateExports);
        });
        plForm.from.addEventListener('change', plUpdateExports);
        plForm.to.addEventListener('change', plUpdateExports);
        plLoad();
    </script>
@endsection
