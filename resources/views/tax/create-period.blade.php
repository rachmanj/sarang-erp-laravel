@extends('layouts.app')

@section('title', 'Create Tax Period')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Create Tax Period</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.periods') }}">Periods</a></li>
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
                            New Tax Period
                        </h3>
                    </div>
                    <form action="{{ route('tax.periods.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="year">Year <span class="text-danger">*</span></label>
                                        <select class="form-control @error('year') is-invalid @enderror" 
                                                id="year" name="year" required>
                                            <option value="">Select Year</option>
                                            @for($i = date('Y') - 2; $i <= date('Y') + 2; $i++)
                                                <option value="{{ $i }}" {{ old('year', date('Y')) == $i ? 'selected' : '' }}>
                                                    {{ $i }}
                                                </option>
                                            @endfor
                                        </select>
                                        @error('year')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="month">Month <span class="text-danger">*</span></label>
                                        <select class="form-control @error('month') is-invalid @enderror" 
                                                id="month" name="month" required>
                                            <option value="">Select Month</option>
                                            @php
                                                $months = [
                                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                                ];
                                            @endphp
                                            @foreach($months as $monthNum => $monthName)
                                                <option value="{{ $monthNum }}" {{ old('month', date('n')) == $monthNum ? 'selected' : '' }}>
                                                    {{ $monthName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('month')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="period_type">Period Type <span class="text-danger">*</span></label>
                                <select class="form-control @error('period_type') is-invalid @enderror" 
                                        id="period_type" name="period_type" required>
                                    <option value="">Select Period Type</option>
                                    <option value="monthly" {{ old('period_type', 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ old('period_type') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="annual" {{ old('period_type') == 'annual' ? 'selected' : '' }}>Annual</option>
                                </select>
                                @error('period_type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Most Indonesian tax periods are monthly</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                               id="start_date" name="start_date" 
                                               value="{{ old('start_date') }}" readonly>
                                        @error('start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">Automatically calculated based on year and month</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="end_date">End Date</label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                               id="end_date" name="end_date" 
                                               value="{{ old('end_date') }}" readonly>
                                        @error('end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                        <small class="form-text text-muted">Automatically calculated based on year and month</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Create Period
                            </button>
                            <a href="{{ route('tax.periods') }}" class="btn btn-secondary">
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
                            Period Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Period Preview</label>
                            <div class="alert alert-info">
                                <strong id="period-preview">Select year and month to preview</strong>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Date Range</label>
                            <div class="alert alert-secondary">
                                <strong>Start:</strong> <span id="start-preview">-</span><br>
                                <strong>End:</strong> <span id="end-preview">-</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Period Status</label>
                            <div class="alert alert-success">
                                <strong>Status:</strong> Open<br>
                                <small class="text-muted">New periods are created in open status</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Tips
                        </h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i> Most Indonesian tax periods are monthly</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Periods are automatically created in open status</li>
                            <li><i class="fas fa-check text-success mr-2"></i> You can close periods after all transactions are recorded</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Closed periods cannot be reopened</li>
                        </ul>
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
    var monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    function updatePeriodPreview() {
        var year = $('#year').val();
        var month = $('#month').val();
        
        if (year && month) {
            var monthName = monthNames[month - 1];
            $('#period-preview').text(monthName + ' ' + year);
            
            // Calculate start and end dates
            var startDate = new Date(year, month - 1, 1);
            var endDate = new Date(year, month, 0); // Last day of the month
            
            $('#start_date').val(formatDate(startDate));
            $('#end_date').val(formatDate(endDate));
            
            $('#start-preview').text(formatDate(startDate));
            $('#end-preview').text(formatDate(endDate));
        } else {
            $('#period-preview').text('Select year and month to preview');
            $('#start-preview').text('-');
            $('#end-preview').text('-');
        }
    }

    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    // Update preview when year or month changes
    $('#year, #month').change(updatePeriodPreview);
    
    // Initial update
    updatePeriodPreview();
});
</script>
@endpush