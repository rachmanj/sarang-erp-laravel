<?php

namespace App\Console\Commands;

use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppGatewayInterface;
use App\Services\WhatsApp\WhatsAppInboundHandler;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;

class PollWhatsAppMessages extends Command
{
    protected $signature = 'whatsapp:poll-messages';

    protected $description = 'Poll Fonnte for inbound WhatsApp messages and process approval commands';

    public function handle(
        WhatsAppGatewayInterface $gateway,
        WhatsAppInboundHandler $handler,
        WhatsAppService $whatsappService
    ): int {
        if (! $whatsappService->isEnabled()) {
            return self::SUCCESS;
        }

        $messages = $gateway->fetchNewMessages();
        $senderNumber = (string) config('whatsapp.sender_number');

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $gatewayMessageId = $message['id'] ?? $message['message_id'] ?? null;

            if ($gatewayMessageId === null || $gatewayMessageId === '') {
                continue;
            }

            if (WhatsAppMessage::query()
                ->where('gateway_message_id', $gatewayMessageId)
                ->where('direction', 'in')
                ->exists()) {
                continue;
            }

            $fromRaw = (string) ($message['sender'] ?? $message['from'] ?? '');
            $body = (string) ($message['message'] ?? $message['body'] ?? '');
            $fromNumber = $handler->normalizePhoneNumber($fromRaw);

            $inbound = WhatsAppMessage::query()->create([
                'direction' => 'in',
                'gateway_message_id' => $gatewayMessageId,
                'to_number' => $senderNumber,
                'from_number' => $fromNumber,
                'body' => $body,
                'message_type' => 'text',
                'status' => 'received',
            ]);

            $reply = $handler->handle([
                'id' => $gatewayMessageId,
                'sender' => $fromRaw,
                'from' => $fromRaw,
                'body' => $body,
                'message' => $body,
            ]);

            if ($reply !== '') {
                $whatsappService->send($fromNumber, $reply);
            }

            $inbound->update(['status' => 'processed']);
        }

        return self::SUCCESS;
    }
}
