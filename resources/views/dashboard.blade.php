@extends('layouts.main')

@section('title_page')
    Dashboard
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ \DB::table('accounts')->count() }}</h3>
                    <p>Accounts</p>
                </div>
                <div class="icon"><i class="fas fa-book"></i></div>
                <a href="{{ url('journals/manual/create') }}" class="small-box-footer">Manual Journal <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ \DB::table('journals')->count() }}</h3>
                    <p>Journals</p>
                </div>
                <div class="icon"><i class="fas fa-book-open"></i></div>
                <a href="{{ url('reports/gl-detail') }}" class="small-box-footer">GL Detail <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ \DB::table('projects')->count() }}</h3>
                    <p>Projects</p>
                </div>
                <div class="icon"><i class="fas fa-project-diagram"></i></div>
                <a href="{{ url('reports/trial-balance') }}" class="small-box-footer">Trial Balance <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ \DB::table('customers')->count() + \DB::table('vendors')->count() }}</h3>
                    <p>Parties</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="#" class="small-box-footer">&nbsp;</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if (session('status'))
        <script>
            toastr.success(@json(session('status')));
        </script>
    @endif
@endpush
