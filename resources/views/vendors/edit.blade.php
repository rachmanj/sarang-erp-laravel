@extends('layouts.main')

@section('title_page')
    Edit Supplier
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Edit Supplier</h3>
                </div>
                <form method="post" action="{{ route('vendors.update', $vendor->id) }}">
                    @csrf
                    @method('PATCH')
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-3"><label>Code</label><input name="code" class="form-control"
                                    value="{{ $vendor->code }}" required></div>
                            <div class="form-group col-md-5"><label>Name</label><input name="name" class="form-control"
                                    value="{{ $vendor->name }}" required></div>
                            <div class="form-group col-md-2"><label>Email</label><input name="email" class="form-control"
                                    value="{{ $vendor->email }}"></div>
                            <div class="form-group col-md-2"><label>Phone</label><input name="phone" class="form-control"
                                    value="{{ $vendor->phone }}"></div>
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary">Save</button><a
                            href="{{ route('vendors.index') }}" class="btn btn-secondary ml-2">Cancel</a></div>
                </form>
            </div>
        </div>
    </section>
@endsection
