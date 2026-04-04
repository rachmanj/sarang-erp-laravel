@extends('layouts.main')

@section('title_page', 'Domain Assistant — request log')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assistant request log</h3>
        </div>
        <div class="card-body">
            <form method="get" class="mb-3">
                <div class="form-row">
                    <div class="col-md-2 mb-2">
                        <label class="small text-muted">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="success" @selected(request('status') === 'success')>Success</option>
                            <option value="error" @selected(request('status') === 'error')>Error</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small text-muted">From</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="small text-muted">To</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small text-muted">User (name / email)</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                            placeholder="Search…">
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Conversation</th>
                            <th>Status</th>
                            <th>ms</th>
                            <th>Tools</th>
                            <th>Error</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at }}</td>
                                <td>{{ $log->user?->name ?? '—' }}<br><small
                                        class="text-muted">{{ $log->user?->email }}</small></td>
                                <td>{{ $log->conversation?->title ?? ('#'.$log->assistant_conversation_id) }}</td>
                                <td>
                                    @if ($log->status === 'success')
                                        <span class="badge badge-success">success</span>
                                    @else
                                        <span class="badge badge-danger">error</span>
                                    @endif
                                </td>
                                <td>{{ $log->duration_ms ?? '—' }}</td>
                                <td>
                                    @if (is_array($log->tools_invoked) && count($log->tools_invoked))
                                        {{ implode(', ', $log->tools_invoked) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><small class="text-danger">{{ \Illuminate\Support\Str::limit($log->error_summary, 120) }}</small></td>
                                <td><small>{{ $log->ip_address }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No rows.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
@endsection
