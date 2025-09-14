@extends('layouts.app')

@section('title', 'Create Tax Transaction')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Tax Transaction</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('tax.transactions') }}">Transactions</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus mr-2"></i>
                                New Tax Transaction
                            </h3>
                        </div>
                        <form action="{{ route('tax.transactions.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_date">Transaction Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date"
                                                class="form-control @error('transaction_date') is-invalid @enderror"
                                                id="transaction_date" name="transaction_date"
                                                value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                            @error('transaction_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_type">Transaction Type <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control @error('transaction_type') is-invalid @enderror"
                                                id="transaction_type" name="transaction_type" required>
                                                <option value="">Select Transaction Type</option>
                                                <option value="purchase"
                                                    {{ old('transaction_type') == 'purchase' ? 'selected' : '' }}>Purchase
                                                </option>
                                                <option value="sales"
                                                    {{ old('transaction_type') == 'sales' ? 'selected' : '' }}>Sales
                                                </option>
                                                <option value="adjustment"
                                                    {{ old('transaction_type') == 'adjustment' ? 'selected' : '' }}>
                                                    Adjustment</option>
                                                <option value="refund"
                                                    {{ old('transaction_type') == 'refund' ? 'selected' : '' }}>Refund
                                                </option>
                                            </select>
                                            @error('transaction_type')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_type">Tax Type <span class="text-danger">*</span></label>
                                            <select class="form-control @error('tax_type') is-invalid @enderror"
                                                id="tax_type" name="tax_type" required>
                                                <option value="">Select Tax Type</option>
                                                <option value="ppn" {{ old('tax_type') == 'ppn' ? 'selected' : '' }}>PPN
                                                    (VAT)</option>
                                                <option value="pph_21" {{ old('tax_type') == 'pph_21' ? 'selected' : '' }}>
                                                    PPh 21</option>
                                                <option value="pph_22" {{ old('tax_type') == 'pph_22' ? 'selected' : '' }}>
                                                    PPh 22</option>
                                                <option value="pph_23" {{ old('tax_type') == 'pph_23' ? 'selected' : '' }}>
                                                    PPh 23</option>
                                                <option value="pph_26" {{ old('tax_type') == 'pph_26' ? 'selected' : '' }}>
                                                    PPh 26</option>
                                                <option value="pph_4_2"
                                                    {{ old('tax_type') == 'pph_4_2' ? 'selected' : '' }}>PPh 4(2)</option>
                                            </select>
                                            @error('tax_type')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_category">Tax Category <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control @error('tax_category') is-invalid @enderror"
                                                id="tax_category" name="tax_category" required>
                                                <option value="">Select Tax Category</option>
                                                <option value="input"
                                                    {{ old('tax_category') == 'input' ? 'selected' : '' }}>Input Tax
                                                </option>
                                                <option value="output"
                                                    {{ old('tax_category') == 'output' ? 'selected' : '' }}>Output Tax
                                                </option>
                                                <option value="withholding"
                                                    {{ old('tax_category') == 'withholding' ? 'selected' : '' }}>
                                                    Withholding Tax</option>
                                            </select>
                                            @error('tax_category')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="vendor_id">Vendor</label>
                                            <select class="form-control @error('vendor_id') is-invalid @enderror"
                                                id="vendor_id" name="vendor_id">
                                                <option value="">Select Vendor</option>
                                                @foreach ($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}"
                                                        {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                        {{ $vendor->name }} ({{ $vendor->npwp ?? 'No NPWP' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('vendor_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="customer_id">Customer</label>
                                            <select class="form-control @error('customer_id') is-invalid @enderror"
                                                id="customer_id" name="customer_id">
                                                <option value="">Select Customer</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}"
                                                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                        {{ $customer->name }} ({{ $customer->npwp ?? 'No NPWP' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('customer_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_number">Tax Number (NPWP)</label>
                                            <input type="text"
                                                class="form-control @error('tax_number') is-invalid @enderror"
                                                id="tax_number" name="tax_number" value="{{ old('tax_number') }}"
                                                placeholder="e.g., 12.345.678.9-012.000">
                                            @error('tax_number')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_name">Tax Entity Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text"
                                                class="form-control @error('tax_name') is-invalid @enderror"
                                                id="tax_name" name="tax_name" value="{{ old('tax_name') }}"
                                                placeholder="Enter tax entity name" required>
                                            @error('tax_name')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="tax_address">Tax Entity Address</label>
                                    <textarea class="form-control @error('tax_address') is-invalid @enderror" id="tax_address" name="tax_address"
                                        rows="3" placeholder="Enter tax entity address">{{ old('tax_address') }}</textarea>
                                    @error('tax_address')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="taxable_amount">Taxable Amount <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="number"
                                                    class="form-control @error('taxable_amount') is-invalid @enderror"
                                                    id="taxable_amount" name="taxable_amount"
                                                    value="{{ old('taxable_amount') }}" step="0.01" min="0"
                                                    placeholder="0.00" required>
                                            </div>
                                            @error('taxable_amount')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax_rate">Tax Rate (%) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number"
                                                    class="form-control @error('tax_rate') is-invalid @enderror"
                                                    id="tax_rate" name="tax_rate" value="{{ old('tax_rate') }}"
                                                    step="0.01" min="0" max="100" placeholder="0.00"
                                                    required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            @error('tax_rate')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="due_date">Due Date</label>
                                            <input type="date"
                                                class="form-control @error('due_date') is-invalid @enderror"
                                                id="due_date" name="due_date"
                                                value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}">
                                            @error('due_date')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3"
                                                placeholder="Additional notes">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>
                                    Create Transaction
                                </button>
                                <a href="{{ route('tax.transactions') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>
                                Tax Information
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tax Type</th>
                                            <th>Default Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>PPN (VAT)</td>
                                            <td>{{ $taxRates['ppn_rate'] }}%</td>
                                        </tr>
                                        <tr>
                                            <td>PPh 21</td>
                                            <td>{{ $taxRates['pph_21_rate'] }}%</td>
                                        </tr>
                                        <tr>
                                            <td>PPh 22</td>
                                            <td>{{ $taxRates['pph_22_rate'] }}%</td>
                                        </tr>
                                        <tr>
                                            <td>PPh 23</td>
                                            <td>{{ $taxRates['pph_23_rate'] }}%</td>
                                        </tr>
                                        <tr>
                                            <td>PPh 26</td>
                                            <td>{{ $taxRates['pph_26_rate'] }}%</td>
                                        </tr>
                                        <tr>
                                            <td>PPh 4(2)</td>
                                            <td>{{ $taxRates['pph_4_2_rate'] }}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calculator mr-2"></i>
                                Tax Calculation Preview
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Tax Amount</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control" id="preview_tax_amount" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Total Amount</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control" id="preview_total_amount" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Auto-fill tax rate based on tax type
            $('#tax_type').change(function() {
                var taxType = $(this).val();
                var taxRates = @json($taxRates);

                if (taxType && taxRates[taxType + '_rate']) {
                    $('#tax_rate').val(taxRates[taxType + '_rate']);
                    calculateTax();
                }
            });

            // Auto-fill vendor/customer information
            $('#vendor_id').change(function() {
                var vendorId = $(this).val();
                if (vendorId) {
                    // You can add AJAX call to get vendor details
                    $('#customer_id').val('').trigger('change');
                }
            });

            $('#customer_id').change(function() {
                var customerId = $(this).val();
                if (customerId) {
                    // You can add AJAX call to get customer details
                    $('#vendor_id').val('').trigger('change');
                }
            });

            // Calculate tax amount
            function calculateTax() {
                var taxableAmount = parseFloat($('#taxable_amount').val()) || 0;
                var taxRate = parseFloat($('#tax_rate').val()) || 0;

                var taxAmount = (taxableAmount * taxRate) / 100;
                var totalAmount = taxableAmount + taxAmount;

                $('#preview_tax_amount').val(taxAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));
                $('#preview_total_amount').val(totalAmount.toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }));
            }

            // Trigger calculation on input change
            $('#taxable_amount, #tax_rate').on('input', calculateTax);

            // Format NPWP input
            $('#tax_number').on('input', function() {
                var value = $(this).val().replace(/\D/g, '');
                if (value.length >= 15) {
                    value = value.substring(0, 15);
                    var formatted = value.substring(0, 2) + '.' +
                        value.substring(2, 5) + '.' +
                        value.substring(5, 8) + '.' +
                        value.substring(8, 9) + '-' +
                        value.substring(9, 12) + '.' +
                        value.substring(12, 15);
                    $(this).val(formatted);
                }
            });
        });
    </script>
@endpush
