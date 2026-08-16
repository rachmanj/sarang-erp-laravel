<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Support\EnvFileUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class WhatsAppSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:whatsapp.settings');
    }

    public function edit(): View
    {
        return view('whatsapp.settings', [
            'settings' => [
                'provider' => config('whatsapp.provider'),
                'api_key' => config('whatsapp.api_key'),
                'sender_number' => config('whatsapp.sender_number'),
                'owner_phone' => config('whatsapp.owner_phone'),
                'module_enabled' => config('whatsapp.module_enabled'),
                'daily_report_time' => config('whatsapp.daily_report_time'),
                'poll_interval' => config('whatsapp.poll_interval'),
                'expiry' => config('whatsapp.expiry'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'sender_number' => ['nullable', 'string', 'max:30'],
            'owner_phone' => ['nullable', 'string', 'max:30'],
            'module_enabled' => ['nullable', 'boolean'],
            'daily_report_time' => ['nullable', 'string', 'max:10'],
            'poll_interval' => ['nullable', 'integer', 'min:10', 'max:3600'],
            'expiry' => ['nullable', 'date'],
        ]);

        try {
            EnvFileUpdater::update([
                'WA_PROVIDER' => $data['provider'],
                'WA_API_KEY' => $data['api_key'] ?? '',
                'WA_SENDER_NUMBER' => $data['sender_number'] ?? '',
                'WA_OWNER_PHONE' => $data['owner_phone'] ?? '',
                'WA_MODULE_ENABLED' => $request->boolean('module_enabled'),
                'WA_DAILY_REPORT_TIME' => $data['daily_report_time'] ?? '08:00',
                'WA_POLL_INTERVAL' => $data['poll_interval'] ?? 60,
                'WA_EXPIRY' => $data['expiry'] ?? '',
            ]);

            Artisan::call('config:clear');

            return back()->with('success', 'Pengaturan WhatsApp berhasil disimpan.');
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'Gagal menyimpan pengaturan: '.$exception->getMessage());
        }
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:30'],
        ]);

        if (! config('whatsapp.module_enabled')) {
            return back()->with('error', 'Modul WhatsApp belum diaktifkan.');
        }

        SendWhatsAppMessage::dispatch(
            $data['test_phone'],
            'Pesan uji coba dari Sarang ERP. Integrasi WhatsApp berfungsi.',
            'test'
        );

        return back()->with('success', 'Pesan uji coba telah dikirim ke antrian.');
    }
}
