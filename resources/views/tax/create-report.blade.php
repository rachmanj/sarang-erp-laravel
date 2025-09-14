@extends('layouts.app')

@section('title', 'Generate Tax Report')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Generate Tax Report</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.index') }}">Tax Compliance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tax.reports') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Generate</li>
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
                            <i class="fas fa-file-alt mr-2"></i>
                            Generate New Tax Report
                        </h3>
                    </div>
                    <form action="{{ route('tax.reports.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="tax_period_id">Tax Period <span class="text-danger">*</span></label>
                                <select class="form-control @error('tax_period_id') is-invalid @enderror" 
                                        id="tax_period_id" name="tax_period_id" required>
                                    <option value="">Select Tax Period</option>
                                    @foreach($periods as $period)
                                        <option value="{{ $period->id }}" {{ old('tax_period_id') == $period->id ? 'selected' : '' }}>
                                            {{ $period->period_name }} 
                                            ({{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }})
                                            @if($period->status === 'closed')
                                                - Closed
                                            @else
                                                - {{ ucfirst($period->status) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('tax_period_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Select a closed tax period to generate reports</small>
                            </div>

                            <div class="form-group">
                                <label for="report_type">Report Type <span class="text-danger">*</span></label>
                                <select class="form-control @error('report_type') is-invalid @enderror" 
                                        id="report_type" name="report_type" required>
                                    <option value="">Select Report Type</option>
                                    @foreach($reportTypes as $type => $name)
                                        <option value="{{ $type }}" {{ old('report_type') == $type ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('report_type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Select the type of tax report to generate</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-file-alt mr-2"></i>
                                Generate Report
                            </button>
                            <a href="{{ route('tax.reports') }}" class="btn btn-secondary">
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
                            Report Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Selected Period</label>
                            <div class="alert alert-info" id="period-info">
                                Select a tax period to view details
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Report Type</label>
                            <div class="alert alert-secondary" id="report-type-info">
                                Select a report type to view details
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Due Date</label>
                            <div class="alert alert-warning" id="due-date-info">
                                Due date will be calculated based on the selected period
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Report Types Guide
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Report Type</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>SPT PPN</strong></td>
                                        <td>VAT Tax Report</td>
                                    </tr>
                                    <tr>
                                        <td><strong>SPT PPh 21</strong></td>
                                        <td>Income Tax Report</td>
                                    </tr>
                                    <tr>
                                        <td><strong>SPT PPh 22</strong></td>
                                        <td>Import Tax Report</td>
                                    </tr>
                                    <tr>
                                        <td><strong>SPT PPh 23</strong></td>
                                        <td>Service Tax Report</td>
                                    </tr>
                                    <tr>
                                        <td><strong>SPT PPh 26</strong></td>
                                        <td>Foreign Entity Tax Report</td>
                                    </tr>
                                    <tr>
                                        <td><strong>SPT PPh 4(2)</strong></td>
                                        <td>Construction Tax Report</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Important Notes
                        </h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success mr-2"></i> Only closed tax periods can be used for report generation</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Reports are generated in draft status initially</li>
                            <li><i class="fas fa-check text-success mr-2"></i> You can review and edit reports before submission</li>
                            <li><i class="fas fa-check text-success mr-2"></i> Due dates are automatically calculated based on Indonesian tax regulations</li>
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
    var periods = @json($periods);
    var reportTypes = @json($reportTypes);

    function updatePeriodInfo() {
        var periodId = $('#tax_period_id').val();
        if (periodId) {
            var period = periods.find(p => p.id == periodId);
            if (period) {
                $('#period-info').html(`
                    <strong>${period.period_name}</strong><br>
                    <small>${period.start_date} to ${period.end_date}</small><br>
                    <span class="badge badge-${period.status === 'closed' ? 'secondary' : 'warning'}">${period.status}</span>
                `);
            }
        } else {
            $('#period-info').text('Select a tax period to view details');
        }
    }

    function updateReportTypeInfo() {
        var reportType = $('#report_type').val();
        if (reportType) {
            var typeName = reportTypes[reportType];
            $('#report-type-info').html(`
                <strong>${typeName}</strong><br>
                <small>Indonesian tax report for ${reportType.toUpperCase()}</small>
            `);
        } else {
            $('#report-type-info').text('Select a report type to view details');
        }
    }

    function updateDueDateInfo() {
        var periodId = $('#tax_period_id').val();
        if (periodId) {
            var period = periods.find(p => p.id == periodId);
            if (period) {
                // Calculate due date (usually 20th of next month)
                var endDate = new Date(period.end_date);
                var dueDate = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 20);
                
                $('#due-date-info').html(`
                    <strong>${dueDate.toLocaleDateString('id-ID')}</strong><br>
                    <small>20th of the month following the tax period</small>
                `);
            }
        } else {
            $('#due-date-info').text('Due date will be calculated based on the selected period');
        }
    }

    // Update info when selections change
    $('#tax_period_id').change(function() {
        updatePeriodInfo();
        updateDueDateInfo();
    });

    $('#report_type').change(function() {
        updateReportTypeInfo();
    });

    // Initial updates
    updatePeriodInfo();
    updateReportTypeInfo();
    updateDueDateInfo();
});
</script>
@endpush