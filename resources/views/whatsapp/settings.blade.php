@extends('layouts.main')

@section('title_page', 'Pengaturan WhatsApp')

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item active">Pengaturan WhatsApp</li>
@endsection

@section('content')
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Konfigurasi Gateway
                        </h3>
                    </div>
                    <form action="{{ route('whatsapp.settings.update') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="provider">Penyedia Gateway</label>
                                <select name="provider" id="provider" class="form-control @error('provider') is-invalid @enderror" required>
                                    <option value="fonnte" @selected(old('provider', $settings['provider']) === 'fonnte')>Fonnte</option>
                                </select>
                                @error('provider')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="api_key">API Key</label>
                                <input type="password" name="api_key" id="api_key"
                                    class="form-control @error('api_key') is-invalid @enderror"
                                    value="{{ old('api_key', $settings['api_key']) }}"
                                    autocomplete="off">
                                @error('api_key')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Token autentikasi dari penyedia gateway.</small>
                            </div>

                            <div class="form-group">
                                <label for="sender_number">Nomor Pengirim</label>
                                <input type="text" name="sender_number" id="sender_number"
                                    class="form-control @error('sender_number') is-invalid @enderror"
                                    value="{{ old('sender_number', $settings['sender_number']) }}"
                                    placeholder="628xxxxxxxxxx">
                                @error('sender_number')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="owner_phone">Nomor Pemilik / Approver</label>
                                <input type="text" name="owner_phone" id="owner_phone"
                                    class="form-control @error('owner_phone') is-invalid @enderror"
                                    value="{{ old('owner_phone', $settings['owner_phone']) }}"
                                    placeholder="628xxxxxxxxxx">
                                @error('owner_phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Nomor yang menerima notifikasi persetujuan PO.</small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="module_enabled"
                                        name="module_enabled" value="1"
                                        @checked(old('module_enabled', $settings['module_enabled']))>
                                    <label class="custom-control-label" for="module_enabled">Aktifkan Modul WhatsApp</label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="daily_report_time">Waktu Laporan Harian</label>
                                    <input type="time" name="daily_report_time" id="daily_report_time"
                                        class="form-control @error('daily_report_time') is-invalid @enderror"
                                        value="{{ old('daily_report_time', $settings['daily_report_time']) }}">
                                    @error('daily_report_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="poll_interval">Interval Polling (detik)</label>
                                    <input type="number" name="poll_interval" id="poll_interval"
                                        class="form-control @error('poll_interval') is-invalid @enderror"
                                        value="{{ old('poll_interval', $settings['poll_interval']) }}"
                                        min="10" max="3600">
                                    @error('poll_interval')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="expiry">Tanggal Kedaluwarsa Modul</label>
                                <input type="date" name="expiry" id="expiry"
                                    class="form-control @error('expiry') is-invalid @enderror"
                                    value="{{ old('expiry', $settings['expiry']) }}">
                                @error('expiry')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Kosongkan jika modul tidak memiliki batas waktu.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Kirim Pesan Uji Coba
                        </h3>
                    </div>
                    <form action="{{ route('whatsapp.settings.test') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="test_phone">Nomor Tujuan Uji Coba</label>
                                <input type="text" name="test_phone" id="test_phone"
                                    class="form-control @error('test_phone') is-invalid @enderror"
                                    value="{{ old('test_phone', $settings['owner_phone']) }}"
                                    placeholder="628xxxxxxxxxx" required>
                                @error('test_phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <p class="text-muted small mb-0">
                                Pesan uji coba akan dikirim melalui antrian. Pastikan modul WhatsApp sudah diaktifkan.
                            </p>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fab fa-whatsapp mr-1"></i> Kirim Pesan Uji Coba
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
@endsection
