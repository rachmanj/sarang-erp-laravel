@extends('layouts.main')

@section('title_page')
    Customer API Keys — {{ $businessPartner->code }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('business_partners.index') }}">Business Partners</a></li>
    <li class="breadcrumb-item"><a href="{{ route('business_partners.show', $businessPartner) }}">{{ $businessPartner->code }}</a></li>
    <li class="breadcrumb-item active">API Keys</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">API keys — {{ $businessPartner->name }}</h3>
                <div class="card-tools">
                    <a href="{{ route('business_partners.show', $businessPartner) }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to partner
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <script>
                        toastr.success(@json(session('success')));
                    </script>
                @endif
                @if (session('new_api_token'))
                    <div class="alert alert-warning">
                        <strong>Copy this token now.</strong> It will not be shown again.<br>
                        <code class="user-select-all">{{ session('new_api_token') }}</code>
                    </div>
                @endif

                <h5 class="mb-3">Generate new key</h5>
                <form method="post" action="{{ route('admin.customers.api-keys.store', $businessPartner) }}"
                    class="mb-4">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4">
                            <label for="name">Label</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required maxlength="255" placeholder="e.g. Production portal">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label for="expires_at">Expires at (optional)</label>
                            <input type="date" name="expires_at" id="expires_at"
                                class="form-control @error('expires_at') is-invalid @enderror"
                                value="{{ old('expires_at') }}">
                            @error('expires_at')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group col-md-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Generate</button>
                        </div>
                    </div>
                </form>

                <h5 class="mb-3">Active keys</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Created</th>
                                <th>Last used</th>
                                <th>Expires</th>
                                <th style="width:100px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($keys as $key)
                                <tr>
                                    <td>{{ $key->name }}</td>
                                    <td>{{ $key->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                    <td>{{ $key->last_used_at ? $key->last_used_at->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}</td>
                                    <td>{{ $key->expires_at ? $key->expires_at->timezone(config('app.timezone'))->format('Y-m-d') : 'Never' }}</td>
                                    <td>
                                        <form method="post"
                                            action="{{ route('admin.customers.api-keys.destroy', [$businessPartner, $key]) }}"
                                            onsubmit="return confirm('Revoke this API key?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-xs">Revoke</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">No API keys yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
