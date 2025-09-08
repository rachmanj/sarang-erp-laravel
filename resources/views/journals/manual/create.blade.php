@extends('layouts.main')

@section('title_page')
    Manual Journal
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Manual Journal</li>
@endsection

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Manual Journal</h4>
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('journals.manual.store') }}">
            @csrf
            <div class="mb-3">
                <label>Date</label>
                <input class="form-control" type="date" name="date" value="{{ now()->toDateString() }}" />
            </div>
            <div class="mb-3">
                <label>Description</label>
                <input class="form-control" type="text" name="description" />
            </div>

            <table class="table" id="lines">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Project</th>
                        <th>Fund</th>
                        <th>Dept</th>
                        <th>Memo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button type="button" class="btn btn-secondary" onclick="addLine()">Add Line</button>
            <span class="ms-3">Total Debit: <strong id="td">0.00</strong> | Total Credit: <strong
                    id="tc">0.00</strong> | Diff: <strong id="diff">0.00</strong></span>
            <button type="submit" class="btn btn-primary ms-3" id="btnPost" disabled>Post Journal</button>
        </form>
    </div>
    <script>
        const accounts = @json($accounts);
        const projects = @json($projects);
        const funds = @json($funds);
        const departments = @json($departments);

        function accountSelectHtml(name) {
            let html = `<select name="${name}" class="form-control">`;
            accounts.forEach(a => {
                html += `<option value="${a.id}">${a.code} - ${a.name}</option>`
            });
            html += `</select>`;
            return html;
        }

        function dimSelectHtml(list, name, placeholder) {
            let html = `<select name="${name}" class="form-control">`;
            html += `<option value="">${placeholder}</option>`;
            list.forEach(x => {
                html += `<option value="${x.id}">${x.code} - ${x.name}</option>`
            });
            html += `</select>`;
            return html;
        }

        function addLine() {
            const tbody = document.querySelector('#lines tbody');
            const idx = tbody.children.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${accountSelectHtml(`lines[${idx}][account_id]`)}</td>
                <td><input type="number" step="0.01" min="0" name="lines[${idx}][debit]" class="form-control" /></td>
                <td><input type="number" step="0.01" min="0" name="lines[${idx}][credit]" class="form-control" /></td>
                <td>${dimSelectHtml(projects, `lines[${idx}][project_id]`, '-- project --')}</td>
                <td>${dimSelectHtml(funds, `lines[${idx}][fund_id]`, '-- fund --')}</td>
                <td>${dimSelectHtml(departments, `lines[${idx}][dept_id]`, '-- dept --')}</td>
                <td><input type="text" name="lines[${idx}][memo]" class="form-control" /></td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove(); recalc();">X</button></td>
            `;
            tbody.appendChild(tr);
            tr.querySelectorAll('input').forEach(i => i.addEventListener('input', recalc));
            recalc();
        }

        function recalc() {
            let td = 0,
                tc = 0;
            document.querySelectorAll('#lines tbody input[name$="[debit]"]').forEach(i => {
                td += parseFloat(i.value || 0)
            });
            document.querySelectorAll('#lines tbody input[name$="[credit]"]').forEach(i => {
                tc += parseFloat(i.value || 0)
            });
            const diff = (td - tc);
            document.getElementById('td').innerText = td.toFixed(2);
            document.getElementById('tc').innerText = tc.toFixed(2);
            document.getElementById('diff').innerText = diff.toFixed(2);
            document.getElementById('btnPost').disabled = Math.abs(diff) > 0.005 || td + tc === 0;
        }
        addLine();
    </script>
@endsection
