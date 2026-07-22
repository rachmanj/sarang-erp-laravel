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
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="card-title" id="tb-title">Trial Balance Report</h3>
                        <p class="mb-0 small text-muted" id="tb-entity"></p>
                    </div>
                    <form class="form-inline align-items-end flex-wrap" id="form">
                        <label class="mr-2 mb-0 small">As of
                            <input type="date" name="date" class="form-control form-control-sm ml-1"
                                value="{{ now()->toDateString() }}" />
                        </label>
                        <label class="mr-2 mb-0 small">Period
                            <input type="number" name="period_year" class="form-control form-control-sm ml-1"
                                style="width:5rem" placeholder="Year" min="2000" max="2100" />
                            <input type="number" name="period_month" class="form-control form-control-sm ml-1"
                                style="width:4rem" placeholder="Mo" min="1" max="12" />
                        </label>
                        @if (($companyEntities ?? collect())->isNotEmpty())
                            <label class="mr-2 mb-0 small">Entity
                                <select name="company_entity_id" class="form-control form-control-sm ml-1">
                                    <option value="">All</option>
                                    @foreach ($companyEntities as $entity)
                                        <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <label class="mr-2 mb-0 small">
                            <input type="checkbox" name="include_unposted" id="tb_include_unposted" value="1">
                            Include unposted journals
                        </label>
                        <button class="btn btn-primary btn-sm mr-2" type="submit">Load</button>
                        <a class="btn btn-sm btn-outline-success mr-2" id="tb-csv-link" href="#">CSV</a>
                        <a class="btn btn-sm btn-outline-primary" id="tb-pdf-link" href="#" target="_blank">PDF</a>
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
                                <th id="tdebit" class="text-right text-nowrap">Rp 0,00</th>
                                <th id="tcredit" class="text-right text-nowrap">Rp 0,00</th>
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

        function formatTrialBalanceIdr(n) {
            const x = Number(n);
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(x);
        }

        function accountDrillUrl(accountId) {
            const asOf = form.date.value;
            const year = asOf ? asOf.substring(0, 4) : new Date().getFullYear();
            const p = new URLSearchParams();
            p.set('from', year + '-01-01');
            p.set('to', asOf);
            if (form.company_entity_id && form.company_entity_id.value) {
                p.set('company_entity_id', form.company_entity_id.value);
            }
            return '/accounts/' + accountId + '?' + p.toString();
        }

        function tbQuery() {
            const p = new URLSearchParams();
            p.set('date', form.date.value);
            if (form.period_year && form.period_year.value) p.set('period_year', form.period_year.value);
            if (form.period_month && form.period_month.value) p.set('period_month', form.period_month.value);
            if (form.company_entity_id && form.company_entity_id.value) {
                p.set('company_entity_id', form.company_entity_id.value);
            }
            if (document.getElementById('tb_include_unposted').checked) p.set('include_unposted', '1');
            return p.toString();
        }

        function tbUpdateExports() {
            const q = tbQuery();
            document.getElementById('tb-csv-link').href = '/reports/trial-balance?' + q + '&export=csv';
            document.getElementById('tb-pdf-link').href = '/reports/trial-balance?' + q + '&export=pdf';
        }

        async function load() {
            tbUpdateExports();
            const res = await fetch('/reports/trial-balance?' + tbQuery(), {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            document.getElementById('tb-entity').textContent = data.entity_name || '';
            tbody.innerHTML = '';
            let tdebit = 0,
                tcredit = 0;
            data.rows.forEach(r => {
                tdebit += r.debit;
                tcredit += r.credit;
                const tr = document.createElement('tr');
                const codeCell = r.account_id ?
                    '<a href="' + accountDrillUrl(r.account_id) + '">' + r.code + '</a>' :
                    r.code;
                const nameCell = r.account_id ?
                    '<a href="' + accountDrillUrl(r.account_id) + '">' + r.name + '</a>' :
                    r.name;
                tr.innerHTML =
                    `<td>${codeCell}</td><td>${nameCell}</td><td>${r.currencies || 'IDR'}</td>` +
                    `<td class="text-right text-nowrap">${formatTrialBalanceIdr(r.debit)}</td>` +
                    `<td class="text-right text-nowrap">${formatTrialBalanceIdr(r.credit)}</td>` +
                    `<td class="text-right text-nowrap">${formatTrialBalanceIdr(r.balance)}</td>`;
                tbody.appendChild(tr);
            });
            document.getElementById('tdebit').innerText = formatTrialBalanceIdr(tdebit);
            document.getElementById('tcredit').innerText = formatTrialBalanceIdr(tcredit);
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            load();
        });
        document.getElementById('tb_include_unposted').addEventListener('change', tbUpdateExports);
        ['date', 'period_year', 'period_month'].forEach(name => {
            if (form[name]) form[name].addEventListener('change', tbUpdateExports);
        });
        if (form.company_entity_id) {
            form.company_entity_id.addEventListener('change', tbUpdateExports);
        }
        load();
    </script>
@endsection
