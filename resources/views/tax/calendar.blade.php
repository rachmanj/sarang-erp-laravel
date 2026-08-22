@extends('layouts.main')

@section('title_page', 'Tax Calendar')

@section('breadcrumb_title')
@endsection

@section('content')
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Event</th>
                                <th>Tax Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events ?? [] as $event)
                                <tr>
                                    <td>{{ $event['date'] ?? '' }}</td>
                                    <td>{{ $event['event_name'] ?? '' }}</td>
                                    <td>{{ $event['tax_type'] ?? '' }}</td>
                                    <td>{{ $event['description'] ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No upcoming tax deadlines in range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
@endsection
