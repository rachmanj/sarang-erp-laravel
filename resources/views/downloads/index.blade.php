@extends('layouts.main')

@section('title_page')
    Downloads
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Downloads</li>
@endsection

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Generated PDFs</h4>
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Size (KB)</th>
                    <th>Modified</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $f)
                    <tr>
                        <td>{{ $f['name'] }}</td>
                        <td>{{ number_format($f['size'] / 1024, 2) }}</td>
                        <td>{{ $f['modified'] }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ $f['url'] }}" target="_blank">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No files yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
