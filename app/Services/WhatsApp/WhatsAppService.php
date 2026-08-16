<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppMessage;
use App\Models\PurchaseOrder;
use Carbon\Carbon;

class WhatsAppService
{
    public function isEnabled(): bool
    {
        if (! config('whatsapp.module_enabled')) {
            return false;
        }

        $expiry = config('whatsapp.expiry');

        if ($expiry === null || $expiry === '') {
            return true;
        }

        return Carbon::parse($expiry)->endOfDay()->isFuture();
    }

    public function send(
        string $to,
        string $message,
        string $messageType = 'text',
        ?string $relatedEntityType = null,
        ?int $relatedEntityId = null
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        SendWhatsAppMessage::dispatch(
            $to,
            $message,
            $messageType,
            $relatedEntityType,
            $relatedEntityId
        );
    }

    public function notifyPurchaseOrderPendingApproval(PurchaseOrder $purchaseOrder): void
    {
        $ownerPhone = config('whatsapp.owner_phone');

        if ($ownerPhone === null || $ownerPhone === '') {
            return;
        }

        $purchaseOrder->loadMissing('businessPartner');

        $supplierName = $purchaseOrder->businessPartner?->name ?? '-';
        $totalAmount = number_format((float) $purchaseOrder->total_amount, 0, ',', '.');

        $message = implode("\n", [
            'Permintaan Persetujuan Purchase Order',
            'No. PO: '.$purchaseOrder->order_no,
            'Supplier: '.$supplierName,
            'Total: Rp '.$totalAmount,
            '',
            'Balas APPROVE '.$purchaseOrder->order_no.' untuk setujui',
            'Balas REJECT '.$purchaseOrder->order_no.' untuk tolak',
        ]);

        $this->send(
            $ownerPhone,
            $message,
            'approval_request',
            PurchaseOrder::class,
            $purchaseOrder->id
        );
    }
}
