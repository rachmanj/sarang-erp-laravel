@extends('layouts.main')

@section('title_page')
    Balance Sheet
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Balance Sheet</li>
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

        .report-statement .report-summary-table th {
            width: 55%;
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
                            <div class="report-entity" id="report-entity">—</div>
                            <div class="report-subtitle mt-1">Statement of Financial Position</div>
                        </div>
                        <form class="form-inline align-items-end flex-shrink-0" id="form">
                            <label class="mr-2 mb-0 small font-weight-normal">As of
                                <input type="date" name="as_of" class="form-control form-control-sm ml-1"
                                    value="{{ request('as_of', now()->toDateString()) }}" />
                            </label>
                            <label class="mr-2 mb-0 small">
                                <input type="checkbox" name="include_unposted" id="bs_include_unposted" value="1"
                                    {{ request('include_unposted') ? 'checked' : '' }}>
                                Include unposted journals
                            </label>
                            <label class="mr-2 mb-0 small">
                                <input type="checkbox" name="show_zero" id="bs_show_zero" value="1"
                                    {{ request('show_zero') ? 'checked' : '' }}>
                                Show zero balances
                            </label>
                            <button class="btn btn-primary btn-sm mr-2" type="submit">Load</button>
                            <a class="btn btn-sm btn-outline-secondary mr-2" id="csv-link" href="#">CSV</a>
                            <a class="btn btn-sm btn-outline-primary" id="pdf-link" href="#" target="_blank">PDF</a>
                        </form>
                    </div>
                    <p class="text-muted small mb-0 mt-2">Assets, liabilities, and equity (net_assets). Income and expense
                        accounts are excluded; closing entries move P&amp;L into equity for a balanced equation.</p>
                </div>
                <div class="card-body pt-2">
                    <p class="small text-muted mb-3" id="meta"></p>
                    <div id="content-area"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('form');
        const area = document.getElementById('content-area');
        const meta = document.getElementById('meta');
        const csvLink = document.getElementById('csv-link');
        const pdfLink = document.getElementById('pdf-link');

        function buildQuery() {
            const p = new URLSearchParams();
            p.set('as_of', form.as_of.value);
            if (document.getElementById('bs_include_unposted').checked) p.set('include_unposted', '1');
            if (document.getElementById('bs_show_zero').checked) p.set('show_zero', '1');
            return p.toString();
        }

        function updateExportLinks() {
            const q = buildQuery();
            csvLink.href = '/reports/balance-sheet?' + q + '&export=csv';
            pdfLink.href = '/reports/balance-sheet?' + q + '&export=pdf';
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function renderRow(r) {
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

        async function load() {
            updateExportLinks();
            const res = await fetch('/reports/balance-sheet?' + buildQuery(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            document.getElementById('report-entity').textContent = data.entity_name || '—';
            meta.textContent = 'As of ' + data.as_of + (data.only_posted_journals ? ' · Posted journals only' :
                ' · Including unposted journals') + (data.hide_zero_lines ? ' · Hiding zero lines' :
                ' · Showing zero lines');
            let html = '';
            for (const sec of data.sections) {
                html += '<h5 class="mt-4 mb-2 font-weight-bold text-secondary">' + escapeHtml(sec.label) + '</h5>';
                html += '<div class="table-responsive"><table class="table table-report table-bordered table-sm mb-0">' +
                    '<thead><tr><th style="width:9rem">Code</th><th>Description</th>' +
                    '<th class="text-right" style="width:12rem">Amount (IDR)</th></tr></thead><tbody>';
                for (const r of sec.rows) {
                    html += renderRow(r);
                }
                html += '</tbody><tfoot><tr><th colspan="2">Total ' + escapeHtml(sec.label) + '</th><th class="text-right">' +
                    sec.total.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) + '</th></tr></tfoot></table></div>';
            }
            html += '<div class="table-responsive mt-4"><table class="table table-sm table-bordered report-summary-table">' +
                '<tbody>';
            html += '<tr><th>Total assets</th><td class="text-right">' + data.totals.assets.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + '</td></tr>';
            html += '<tr><th>Total liabilities</th><td class="text-right">' + data.totals.liabilities.toLocaleString(
                undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '<tr><th>Total equity / net assets</th><td class="text-right">' + data.totals.equity.toLocaleString(
                undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '<tr><th>Difference (Assets − Liabilities − Equity)</th><td class="text-right">' + data.totals
                .difference.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '<tr><th>Unclosed P&amp;L (income &amp; expense, cumulative to date)</th><td class="text-right">' +
                data.totals.unclosed_pnl_cumulative.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '<tr><th>Check (difference − unclosed P&amp;L)</th><td class="text-right">' + data.totals
                .difference_vs_unclosed_pnl.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '</td></tr>';
            html += '</tbody></table></div>';
            area.innerHTML = html;
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            load();
        });
        document.getElementById('bs_include_unposted').addEventListener('change', updateExportLinks);
        document.getElementById('bs_show_zero').addEventListener('change', updateExportLinks);
        form.as_of.addEventListener('change', updateExportLinks);
        load();
    </script>
@endsection
