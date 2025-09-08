@extends('layouts.main')

@section('title_page')
    Periods
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Periods</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-6">
                    @if (session('success'))
                        <script>
                            toastr.success(@json(session('success')));
                        </script>
                    @endif
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Period Close ({{ $year }})</h3>
                            <form method="get" class="form-inline">
                                <input type="number" name="year" class="form-control form-control-sm mr-2"
                                    value="{{ $year }}" min="2000" max="2100">
                                <button class="btn btn-sm btn-primary" type="submit">Go</button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Month</th>
                                        <th>Status</th>
                                        <th style="width: 240px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($periods as $p)
                                        <tr>
                                            <td>{{ str_pad($p['month'], 2, '0', STR_PAD_LEFT) }} / {{ $p['year'] }}</td>
                                            <td>
                                                @if ($p['is_closed'])
                                                    <span class="badge badge-danger">Closed</span>
                                                @else
                                                    <span class="badge badge-success">Open</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('periods.close')
                                                    @if (!$p['is_closed'])
                                                        <form method="post" action="{{ route('periods.close') }}"
                                                            class="d-inline period-action-form" data-action="close">
                                                            @csrf
                                                            <input type="hidden" name="year" value="{{ $p['year'] }}">
                                                            <input type="hidden" name="month" value="{{ $p['month'] }}">
                                                            <button class="btn btn-xs btn-danger action-close"
                                                                type="submit">Close</button>
                                                        </form>
                                                    @else
                                                        <form method="post" action="{{ route('periods.open') }}"
                                                            class="d-inline period-action-form" data-action="open">
                                                            @csrf
                                                            <input type="hidden" name="year" value="{{ $p['year'] }}">
                                                            <input type="hidden" name="month" value="{{ $p['month'] }}">
                                                            <button class="btn btn-xs btn-secondary action-open"
                                                                type="submit">Re-open</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $(document).on('submit', '.period-action-form', function(e) {
                e.preventDefault();
                const form = this;
                const isClose = $(form).data('action') === 'close';
                Swal.fire({
                    title: isClose ? 'Close this period?' : 'Re-open this period?',
                    text: isClose ? 'Posting into this month will be blocked.' :
                        'Posting into this month will be allowed.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: isClose ? 'Yes, close it' : 'Yes, re-open',
                    cancelButtonText: 'Cancel'
                }).then((res) => {
                    if (res.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
