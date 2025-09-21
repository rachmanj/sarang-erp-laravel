@extends('layouts.main')

@section('title', 'Create ' . ($documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue'))

@section('title_page')
    Create {{ $documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue' }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('gr-gi.index') }}">GR/GI Management</a></li>
    <li class="breadcrumb-item active">Create {{ $documentType === 'goods_receipt' ? 'GR' : 'GI' }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus mr-1"></i>
                                Create {{ $documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue' }}
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('gr-gi.index') }}" class="btn btn-tool btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to GR/GI
                                </a>
                            </div>
                        </div>
                        <form action="{{ route('gr-gi.store') }}" method="POST" id="gr-gi-form">
                            @csrf
                            <div class="card-body">
                                <!-- Header Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="document_type">Document Type</label>
                                            <input type="text" class="form-control"
                                                value="{{ $documentType === 'goods_receipt' ? 'Goods Receipt' : 'Goods Issue' }}"
                                                readonly>
                                            <input type="hidden" name="document_type" value="{{ $documentType }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="purpose_id">Purpose <span class="text-danger">*</span></label>
                                            <select class="form-control" name="purpose_id" id="purpose_id" required>
                                                <option value="">Select Purpose</option>
                                                @foreach ($purposes as $purpose)
                                                    <option value="{{ $purpose->id }}">{{ $purpose->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('purpose_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="warehouse_id">Warehouse <span class="text-danger">*</span></label>
                                            <select class="form-control" name="warehouse_id" id="warehouse_id" required>
                                                <option value="">Select Warehouse</option>
                                                @foreach ($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}">{{ $warehouse->code }} -
                                                        {{ $warehouse->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('warehouse_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_date">Transaction Date <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="transaction_date"
                                                id="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}"
                                                required>
                                            @error('transaction_date')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_number">Reference Number</label>
                                            <input type="text" class="form-control" name="reference_number"
                                                id="reference_number" value="{{ old('reference_number') }}"
                                                placeholder="Optional reference number">
                                            @error('reference_number')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="notes">Notes</label>
                                            <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Lines Section -->
                                <hr>
                                <h5><i class="fas fa-list mr-1"></i> Document Lines</h5>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="lines-table">
                                        <thead>
                                            <tr>
                                                <th width="30%">Item <span class="text-danger">*</span></th>
                                                <th width="15%">Quantity <span class="text-danger">*</span></th>
                                                <th width="15%">Unit Price <span class="text-danger">*</span></th>
                                                <th width="15%">Total Amount</th>
                                                <th width="20%">Notes</th>
                                                <th width="5%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lines-tbody">
                                            <tr class="line-row">
                                                <td>
                                                    <select class="form-control item-select" name="lines[0][item_id]"
                                                        required>
                                                        <option value="">Select Item</option>
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                data-code="{{ $item->code }}"
                                                                data-unit="{{ $item->unit_of_measure }}">
                                                                {{ $item->code }} - {{ $item->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control quantity-input"
                                                        name="lines[0][quantity]" step="0.001" min="0.001" required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control unit-price-input"
                                                        name="lines[0][unit_price]" step="0.01" min="0"
                                                        required>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control total-amount-input"
                                                        readonly>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="lines[0][notes]"
                                                        placeholder="Optional notes">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm remove-line"
                                                        disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-success btn-sm" id="add-line">
                                            <i class="fas fa-plus"></i> Add Line
                                        </button>
                                    </div>
                                </div>

                                <!-- Total Summary -->
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 offset-md-6">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <tr>
                                                    <th>Total Amount:</th>
                                                    <td class="text-right">
                                                        <strong id="total-amount">0.00</strong>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create
                                    {{ $documentType === 'goods_receipt' ? 'GR' : 'GI' }}
                                </button>
                                <a href="{{ route('gr-gi.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let lineIndex = 0;

            // Add line functionality
            $('#add-line').on('click', function() {
                lineIndex++;
                const newRow = `
            <tr class="line-row">
                <td>
                    <select class="form-control item-select" name="lines[${lineIndex}][item_id]" required>
                        <option value="">Select Item</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" data-code="{{ $item->code }}" data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input" name="lines[${lineIndex}][quantity]" step="0.001" min="0.001" required>
                </td>
                <td>
                    <input type="number" class="form-control unit-price-input" name="lines[${lineIndex}][unit_price]" step="0.01" min="0" required>
                </td>
                <td>
                    <input type="number" class="form-control total-amount-input" readonly>
                </td>
                <td>
                    <input type="text" class="form-control" name="lines[${lineIndex}][notes]" placeholder="Optional notes">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-line">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
                $('#lines-tbody').append(newRow);
                updateRemoveButtons();
            });

            // Remove line functionality
            $(document).on('click', '.remove-line', function() {
                $(this).closest('tr').remove();
                updateRemoveButtons();
                calculateTotal();
            });

            // Update remove buttons state
            function updateRemoveButtons() {
                const rows = $('.line-row').length;
                $('.remove-line').prop('disabled', rows <= 1);
            }

            // Calculate total amount for a line
            $(document).on('input', '.quantity-input, .unit-price-input', function() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
                const totalAmount = quantity * unitPrice;

                row.find('.total-amount-input').val(totalAmount.toFixed(2));
                calculateTotal();
            });

            // Calculate total amount
            function calculateTotal() {
                let total = 0;
                $('.total-amount-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#total-amount').text(total.toFixed(2));
            }

            // Auto-fill unit price for GI documents
            @if ($documentType === 'goods_issue')
                $(document).on('change', '.item-select', function() {
                    const row = $(this).closest('tr');
                    const itemId = $(this).val();

                    if (itemId) {
                        // Calculate valuation using FIFO/LIFO/Average
                        $.ajax({
                            url: '{{ route('gr-gi.calculate-valuation') }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                item_id: itemId,
                                quantity: 1, // Default quantity for calculation
                                method: 'FIFO'
                            },
                            success: function(response) {
                                row.find('.unit-price-input').val(response.unit_price);
                                // Trigger calculation
                                row.find('.quantity-input').trigger('input');
                            },
                            error: function() {
                                console.log('Error calculating valuation');
                            }
                        });
                    }
                });
            @endif

            // Form validation
            $('#gr-gi-form').on('submit', function(e) {
                const lines = $('.line-row').length;
                if (lines === 0) {
                    e.preventDefault();
                    alert('Please add at least one line item.');
                    return false;
                }

                // Validate all required fields
                let isValid = true;
                $('.line-row').each(function() {
                    const itemId = $(this).find('.item-select').val();
                    const quantity = $(this).find('.quantity-input').val();
                    const unitPrice = $(this).find('.unit-price-input').val();

                    if (!itemId || !quantity || !unitPrice) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields for all line items.');
                    return false;
                }
            });

            // Initialize
            updateRemoveButtons();
        });
    </script>
@endpush
