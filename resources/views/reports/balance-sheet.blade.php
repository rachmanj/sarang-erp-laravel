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
                        <form class="form-inline align-items-end flex-shrink-0 flex-wrap" id="form">
                            <label class="mr-2 mb-0 small font-weight-normal">As of
                                <input type="date" name="as_of" class="form-control form-control-sm ml-1"
                                    value="{{ request('as_of', now()->toDateString()) }}" />
                            </label>
                            <label class="mr-2 mb-0 small font-weight-normal">Compare as of
                                <input type="date" name="prior_as_of" class="form-control form-control-sm ml-1"
                                    value="{{ request('prior_as_of') }}" />
                            </label>
                            <label class="mr-2 mb-0 small font-weight-normal">Period
                                <input type="number" name="period_year" class="form-control form-control-sm ml-1"
                                    style="width:5rem" placeholder="Year" min="2000" max="2100"
                                    value="{{ request('period_year') }}" />
                                <input type="number" name="period_month" class="form-control form-control-sm ml-1"
                                    style="width:4rem" placeholder="Mo" min="1" max="12"
                                    value="{{ request('period_month') }}" />
                            </label>
                            @if (($companyEntities ?? collect())->isNotEmpty())
                                <label class="mr-2 mb-0 small font-weight-normal">Entity
                                    <select name="company_entity_id" class="form-control form-control-sm ml-1">
                                        <option value="">All</option>
                                        @foreach ($companyEntities as $entity)
                                            <option value="{{ $entity->id }}"
                                                {{ (string) request('company_entity_id') === (string) $entity->id ? 'selected' : '' }}>
                                                {{ $entity->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
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
                        accounts are excluded; closing entries move P&amp;L into equity for a balanced equation. Click a
                        postable account to open its ledger.</p>
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
            if (form.prior_as_of.value) p.set('prior_as_of', form.prior_as_of.value);
            if (form.period_year && form.period_year.value) p.set('period_year', form.period_year.value);
            if (form.period_month && form.period_month.value) p.set('period_month', form.period_month.value);
            if (form.company_entity_id && form.company_entity_id.value) {
                p.set('company_entity_id', form.company_entity_id.value);
            }
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

        function fmt(n) {
            return Number(n).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function fmtPct(current, prior) {
            if (prior === null || prior === undefined || Math.abs(Number(prior)) < 0.0005) {
                return '—';
            }
            return ((Number(current) - Number(prior)) / Math.abs(Number(prior)) * 100).toLocaleString(undefined, {
                minimumFractionDigits: 1,
                maximumFractionDigits: 1
            }) + '%';
        }

        function accountDrillUrl(accountId) {
            const asOf = form.as_of.value;
            const year = asOf ? asOf.substring(0, 4) : new Date().getFullYear();
            const p = new URLSearchParams();
            p.set('from', year + '-01-01');
            p.set('to', asOf);
            if (form.company_entity_id && form.company_entity_id.value) {
                p.set('company_entity_id', form.company_entity_id.value);
            }
            return '/accounts/' + accountId + '?' + p.toString();
        }

        function renderRow(r, showPrior) {
            const depth = r.depth || 0;
            const pad = depth * 1.1;
            const parent = r.is_parent;
            const trClass = parent ? 'report-row-parent' : '';
            const nameInner = (parent ?
                    '<span class="text-muted mr-1" title="Subtotal of child accounts">↳</span>' : '') +
                escapeHtml(r.name);
            const canDrill = r.is_postable && r.account_id && !parent;
            const codeCell = canDrill ?
                '<a href="' + accountDrillUrl(r.account_id) + '">' + escapeHtml(r.code) + '</a>' :
                escapeHtml(r.code);
            const nameCell = canDrill ?
                '<a href="' + accountDrillUrl(r.account_id) + '">' + nameInner + '</a>' :
                nameInner;
            let html = '<tr class="' + trClass + '">' +
                '<td class="text-monospace small align-middle">' + codeCell + '</td>' +
                '<td class="align-middle" style="padding-left:' + pad + 'rem">' + nameCell + '</td>' +
                '<td class="text-right text-nowrap align-middle">' + fmt(r.amount) + '</td>';
            if (showPrior) {
                const prior = r.prior_amount ?? 0;
                const variance = Number(r.amount) - Number(prior);
                html += '<td class="text-right text-nowrap align-middle">' + fmt(prior) + '</td>' +
                    '<td class="text-right text-nowrap align-middle">' + fmt(variance) + '</td>' +
                    '<td class="text-right text-nowrap align-middle">' + fmtPct(r.amount, prior) + '</td>';
            }
            html += '</tr>';
            return html;
        }

        function summaryRow(label, amount, priorAmount, showPrior) {
            let html = '<tr><th>' + label + '</th><td class="text-right">' + fmt(amount) + '</td>';
            if (showPrior) {
                const prior = priorAmount ?? 0;
                html += '<td class="text-right">' + fmt(prior) + '</td>' +
                    '<td class="text-right">' + fmt(Number(amount) - Number(prior)) + '</td>' +
                    '<td class="text-right">' + fmtPct(amount, prior) + '</td>';
            }
            html += '</tr>';
            return html;
        }

        async function load() {
            updateExportLinks();
            const res = await fetch('/reports/balance-sheet?' + buildQuery(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            const showPrior = !!data.prior_as_of;
            document.getElementById('report-entity').textContent = data.entity_name || '—';
            meta.textContent = 'As of ' + data.as_of +
                (showPrior ? ' · Compare as of ' + data.prior_as_of : '') +
                (data.only_posted_journals ? ' · Posted journals only' : ' · Including unposted journals') +
                (data.hide_zero_lines ? ' · Hiding zero lines' : ' · Showing zero lines');
            let html = '';
            for (const sec of data.sections) {
                html += '<h5 class="mt-4 mb-2 font-weight-bold text-secondary">' + escapeHtml(sec.label) + '</h5>';
                html += '<div class="table-responsive"><table class="table table-report table-bordered table-sm mb-0">' +
                    '<thead><tr><th style="width:9rem">Code</th><th>Description</th>' +
                    '<th class="text-right" style="width:10rem">Amount (IDR)</th>';
                if (showPrior) {
                    html += '<th class="text-right" style="width:10rem">Prior (IDR)</th>' +
                        '<th class="text-right" style="width:9rem">Variance</th>' +
                        '<th class="text-right" style="width:6rem">%</th>';
                }
                html += '</tr></thead><tbody>';
                for (const r of sec.rows) {
                    html += renderRow(r, showPrior);
                }
                const colSpan = showPrior ? 5 : 2;
                html += '</tbody><tfoot><tr><th colspan="' + colSpan + '">Total ' + escapeHtml(sec.label) +
                    '</th><th class="text-right">' + fmt(sec.total) + '</th></tr></tfoot></table></div>';
            }
            const prior = data.totals.prior || {};
            html += '<div class="table-responsive mt-4"><table class="table table-sm table-bordered report-summary-table">' +
                '<thead><tr><th></th><th class="text-right">Current</th>';
            if (showPrior) {
                html += '<th class="text-right">Prior</th><th class="text-right">Variance</th><th class="text-right">%</th>';
            }
            html += '</tr></thead><tbody>';
            html += summaryRow('Total assets', data.totals.assets, prior.assets, showPrior);
            html += summaryRow('Total liabilities', data.totals.liabilities, prior.liabilities, showPrior);
            html += summaryRow('Total equity / net assets', data.totals.equity, prior.equity, showPrior);
            html += summaryRow('Difference (Assets − Liabilities − Equity)', data.totals.difference, prior.difference,
                showPrior);
            html += summaryRow('Unclosed P&amp;L (income &amp; expense, cumulative to date)', data.totals
                .unclosed_pnl_cumulative, prior.unclosed_pnl_cumulative, showPrior);
            html += summaryRow('Check (difference − unclosed P&amp;L)', data.totals.difference_vs_unclosed_pnl, prior
                .difference_vs_unclosed_pnl, showPrior);
            html += '</tbody></table></div>';
            area.innerHTML = html;
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            load();
        });
        document.getElementById('bs_include_unposted').addEventListener('change', updateExportLinks);
        document.getElementById('bs_show_zero').addEventListener('change', updateExportLinks);
        ['as_of', 'prior_as_of', 'period_year', 'period_month'].forEach(name => {
            if (form[name]) form[name].addEventListener('change', updateExportLinks);
        });
        if (form.company_entity_id) {
            form.company_entity_id.addEventListener('change', updateExportLinks);
        }
        load();
    </script>
@endsection
