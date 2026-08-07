@extends('layouts.main')

@section('title_page')
    ERP Parameter Details
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('erp-parameters.index') }}">ERP Parameters</a></li>
    <li class="breadcrumb-item active">{{ $erpParameter->parameter_name }}</li>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $erpParameter->parameter_name }}</h3>
                            <div class="card-tools">
                                <a href="{{ route('erp-parameters.edit', $erpParameter) }}"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="{{ route('erp-parameters.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to List
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">Category</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $erpParameter->category)) }}</td>
                                </tr>
                                <tr>
                                    <th>Parameter Key</th>
                                    <td><code>{{ $erpParameter->parameter_key }}</code></td>
                                </tr>
                                <tr>
                                    <th>Parameter Name</th>
                                    <td>{{ $erpParameter->parameter_name }}</td>
                                </tr>
                                <tr>
                                    <th>Data Type</th>
                                    <td><span class="badge badge-info">{{ $erpParameter->data_type }}</span></td>
                                </tr>
                                <tr>
                                    <th>Value</th>
                                    <td>
                                        @if ($erpParameter->data_type === 'boolean')
                                            <span
                                                class="badge badge-{{ $erpParameter->parameter_value ? 'success' : 'danger' }}">
                                                {{ $erpParameter->parameter_value ? 'Yes' : 'No' }}
                                            </span>
                                        @elseif($erpParameter->data_type === 'json')
                                            <pre class="mb-0">{{ json_encode(json_decode($erpParameter->parameter_value), JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            {{ $erpParameter->parameter_value }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span
                                            class="badge badge-{{ $erpParameter->is_active ? 'success' : 'secondary' }}">
                                            {{ $erpParameter->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($erpParameter->description)
                                    <tr>
                                        <th>Description</th>
                                        <td>{{ $erpParameter->description }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Last Updated</th>
                                    <td>{{ $erpParameter->updated_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
