@extends('layouts.app')

@section('title', 'Tax Compliance Logs')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tax Compliance Logs</h1>
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
                                <th>When</th>
                                <th>Entity</th>
                                <th>Action</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs ?? [] as $log)
                                <tr>
                                    <td>{{ $log->created_at }}</td>
                                    <td>{{ $log->entity_type ?? '' }} #{{ $log->entity_id ?? '' }}</td>
                                    <td>{{ $log->action ?? '' }}</td>
                                    <td>{{ $log->user_id ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No compliance logs yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (method_exists($logs, 'links'))
                    <div class="card-footer">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
