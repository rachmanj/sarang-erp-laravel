@extends('layouts.app')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Sales Order</h3>
                </div>
                <form method="post" action="{{ route('sales-orders.store') }}" id="so-form">
                    @csrf
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>Date</label>
                                <input type="date" name="date" class="form-control"
                                    value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Customer</label>
                                <select name="customer_id" class="form-control" required>
                                    <option value="">-- choose --</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="lines">
                                <thead>
                                    <tr>
                                        <th style="width:24%">Account</th>
                                        <th style="width:28%">Description</th>
                                        <th style="width:12%">Qty</th>
                                        <th style="width:16%">Unit Price</th>
                                        <th style="width:12%">Tax</th>
                                        <th style="width:8%"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-secondary" id="add-line">Add Line</button>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('sales-orders.index') }}" class="btn btn-default">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.prefill = @json($prefill ?? null);

        function lineRow(data) {
            const acc = `
  <select name="lines[__i__][account_id]" class="form-control" required>
    <option value="">-- choose --</option>
    @foreach ($accounts as $a)
      <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
    @endforeach
  </select>`;
            const tax = `
  <select name="lines[__i__][tax_code_id]" class="form-control">
    <option value="">-</option>
    @foreach ($taxCodes as $t)
      <option value="{{ $t->id }}">{{ $t->code }}</option>
    @endforeach
  </select>`;
            return `<tr>
    <td>${acc}</td>
    <td><input name="lines[__i__][description]" class="form-control"/></td>
    <td><input type="number" step="0.01" min="0.01" name="lines[__i__][qty]" class="form-control" value="1" required/></td>
    <td><input type="number" step="0.01" min="0" name="lines[__i__][unit_price]" class="form-control" value="0" required/></td>
    <td>${tax}</td>
    <td><button type="button" class="btn btn-sm btn-danger rm">&times;</button></td>
  </tr>`;
        }
        $(function() {
            let i = 0;
            const $tb = $('#lines tbody');
            $('#add-line').on('click', function() {
                $tb.append(lineRow({}).replaceAll('__i__', i++));
            }).trigger('click');
            $tb.on('click', '.rm', function() {
                $(this).closest('tr').remove();
            });
            if (window.prefill) {
                $tb.empty();
                i = 0;
                $('[name=date]').val(window.prefill.date);
                $('[name=customer_id]').val(window.prefill.customer_id);
                (window.prefill.lines || []).forEach(function(l) {
                    $tb.append(lineRow({}).replaceAll('__i__', i++));
                    const $row = $tb.find('tr').last();
                    $row.find('select[name$="[account_id]"]').val(l.account_id);
                    $row.find('input[name$="[description]"]').val(l.description || '');
                    $row.find('input[name$="[qty]"]').val(l.qty);
                    $row.find('input[name$="[unit_price]"]').val(l.unit_price);
                    $row.find('select[name$="[tax_code_id]"]').val(l.tax_code_id || '');
                });
            }
        });
    </script>
@endpush
