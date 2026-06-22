@extends('layouts.app')

@section('title', 'Tax Calendar')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tax Calendar</h1>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
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
        </div>
    </section>
@endsection
