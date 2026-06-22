@extends('layouts.main')

@section('title_page')
    Statement of Changes in Equity
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Statement of Changes in Equity</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $report_title ?? 'Statement of Changes in Equity' }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">{{ $from ?? '' }} to {{ $to ?? '' }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Opening</th>
                                <th class="text-right">Movement</th>
                                <th class="text-right">Closing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows ?? [] as $row)
                                <tr>
                                    <td>{{ $row['code'] }} — {{ $row['name'] }}</td>
                                    <td class="text-right">{{ number_format($row['opening'], 2, '.', ',') }}</td>
                                    <td class="text-right">{{ number_format($row['movement'], 2, '.', ',') }}</td>
                                    <td class="text-right">{{ number_format($row['closing'], 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Net income (P&amp;L)</th>
                                <th colspan="3" class="text-right">{{ number_format($net_income ?? 0, 2, '.', ',') }}</th>
                            </tr>
                            <tr>
                                <th>Totals</th>
                                <th class="text-right">{{ number_format($totals['opening'] ?? 0, 2, '.', ',') }}</th>
                                <th class="text-right">{{ number_format($totals['movement'] ?? 0, 2, '.', ',') }}</th>
                                <th class="text-right">{{ number_format($totals['closing'] ?? 0, 2, '.', ',') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
