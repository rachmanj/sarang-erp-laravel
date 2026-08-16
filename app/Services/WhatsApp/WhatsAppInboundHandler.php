<?php

namespace App\Services\WhatsApp;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\PurchaseService;

class WhatsAppInboundHandler
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    public function handle(array $message): string
    {
        $sender = $message['sender'] ?? $message['from'] ?? '';
        $body = trim((string) ($message['body'] ?? $message['message'] ?? ''));

        if ($body === '') {
            return '';
        }

        $normalizedSender = $this->normalizePhoneNumber($sender);

        if (! preg_match('/^(APPROVE|SETUJU|REJECT|TOLAK)\s+(PO[-\w]+)/i', $body, $matches)) {
            return '';
        }

        $action = strtoupper($matches[1]);
        $orderNo = $matches[2];

        $isApprove = in_array($action, ['APPROVE', 'SETUJU'], true);
        $isReject = in_array($action, ['REJECT', 'TOLAK'], true);

        $user = User::query()
            ->where('whatsapp_number', $normalizedSender)
            ->first();

        if (! $user) {
            return 'Pengirim tidak dikenal. Nomor WhatsApp Anda belum terdaftar di sistem.';
        }

        $purchaseOrder = PurchaseOrder::query()
            ->where('order_no', $orderNo)
            ->first();

        if (! $purchaseOrder) {
            return 'PO tidak ditemukan: '.$orderNo;
        }

        try {
            if ($isApprove) {
                $this->purchaseService->approvePurchaseOrder(
                    $purchaseOrder->id,
                    $user->id,
                    'Disetujui via WhatsApp'
                );

                return 'PO '.$orderNo.' berhasil disetujui.';
            }

            if ($isReject) {
                $this->purchaseService->rejectPurchaseOrder(
                    $purchaseOrder->id,
                    $user->id,
                    'Ditolak via WhatsApp'
                );

                return 'PO '.$orderNo.' berhasil ditolak.';
            }
        } catch (\Exception $exception) {
            if (str_contains($exception->getMessage(), 'No pending approval')) {
                return 'Tidak ada approval pending untuk Anda pada PO '.$orderNo;
            }

            return 'Gagal memproses PO '.$orderNo.': '.$exception->getMessage();
        }

        return '';
    }

    public function normalizePhoneNumber(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if (str_starts_with($digits, '08')) {
            $digits = '628'.substr($digits, 2);
        }

        return $digits;
    }
}
