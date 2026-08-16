<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\DailyReportService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDailyReportWhatsApp extends Command
{
    protected $signature = 'whatsapp:send-daily-report';

    protected $description = 'Kirim laporan harian penjualan dan keuangan ke pemilik via WhatsApp';

    public function handle(DailyReportService $dailyReportService, WhatsAppService $whatsappService): int
    {
        $ownerPhone = (string) config('whatsapp.owner_phone');

        if ($ownerPhone === '' || ! $whatsappService->isEnabled()) {
            return self::SUCCESS;
        }

        $report = $dailyReportService->buildDailyReport();
        $dateLabel = Carbon::today()->locale('id')->translatedFormat('d F Y');

        $message = implode("\n", [
            'Laporan Harian '.$dateLabel,
            '',
            'Penjualan hari ini: Rp '.number_format($report['sales_today'], 0, ',', '.'),
            'Saldo Kas/Bank: Rp '.number_format($report['cash_bank_balance'], 0, ',', '.'),
            'PO menunggu persetujuan: '.$report['po_pending_count'],
            'Invoice jatuh tempo: '.$report['overdue_invoice_count'],
        ]);

        $whatsappService->send($ownerPhone, $message, 'daily_report');

        return self::SUCCESS;
    }
}
