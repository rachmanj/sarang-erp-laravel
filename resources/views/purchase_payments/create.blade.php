@extends('layouts.main')

@section('title', 'Create Purchase Payment')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">New Purchase Payment</h3>
                        </div>
                        <form method="post" action="{{ route('purchase-payments.store') }}">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                                        class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Vendor</label>
                                    <select name="vendor_id" class="form-control" required>
                                        <option value="">-- select --</option>
                                        @foreach ($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="description" value="{{ old('description') }}"
                                        class="form-control">
                                </div>
                                <hr>
                                <h5>Lines</h5>
                                <div id="lines">
                                    <div class="line-item row mb-2">
                                        <div class="col-md-8">
                                            <label>Bank/Cash Account</label>
                                            <select name="lines[0][account_id]" class="form-control" required>
                                                @foreach ($accounts as $a)
                                                    <option value="{{ $a->id }}">{{ $a->code }} -
                                                        {{ $a->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Amount</label>
                                            <input type="number" step="0.01" min="0.01" name="lines[0][amount]"
                                                class="form-control" value="0">
                                        </div>
                                        <div class="col-md-2">
                                            <label>Notes</label>
                                            <input type="text" name="lines[0][description]" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="addLine()">Add
                                    Line</button>
                                <hr>
                                <h5>Allocation Preview</h5>
                                <div class="form-inline mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="previewAlloc()">Preview Oldest-First Allocation</button>
                                </div>
                                <table class="table table-sm table-striped" id="alloc-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th class="text-right">Remaining</th>
                                            <th class="text-right">Allocate</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary" type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        let idx = 1;

        function addLine() {
            const container = document.getElementById('lines');
            const row = document.createElement('div');
            row.className = 'line-item row mb-2';
            row.innerHTML = `
        <div class="col-md-8">
            <select name="lines[${idx}][account_id]" class="form-control" required>
                ${@json($accounts).map(a => `<option value=\"${a.id}\">${a.code} - ${a.name}</option>`).join('')}
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" min="0.01" name="lines[${idx}][amount]" class="form-control" value="0">
        </div>
        <div class="col-md-2">
            <input type="text" name="lines[${idx}][description]" class="form-control" placeholder="Notes">
        </div>`;
            container.appendChild(row);
            idx++;
        }
        async function previewAlloc() {
            const amount = Array.from(document.querySelectorAll('input[name^="lines"][name$="[amount]"]'))
                .reduce((s, el) => s + parseFloat(el.value || 0), 0);
            const vendorId = document.querySelector('select[name="vendor_id"]').value;
            if (!vendorId || amount <= 0) {
                toastr.warning('Select vendor and enter amount');
                return;
            }
            const params = new URLSearchParams({
                vendor_id: vendorId,
                amount: amount
            });
            const res = await fetch(`{{ route('purchase-payments.previewAllocation') }}?${params.toString()}`);
            const data = await res.json();
            const tbody = document.querySelector('#alloc-table tbody');
            tbody.innerHTML = '';
            data.rows.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    `<td>${r.invoice_no}</td><td class="text-right">${Number(r.remaining_before).toFixed(2)}</td><td class="text-right">${Number(r.allocate).toFixed(2)}</td>`;
                tbody.appendChild(tr);
            });
        }
    </script>
@endsection
