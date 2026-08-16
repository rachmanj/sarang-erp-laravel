<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $to,
        public string $message,
        public string $messageType = 'text',
        public ?string $relatedEntityType = null,
        public ?int $relatedEntityId = null
    ) {}

    public function handle(WhatsAppGatewayInterface $gateway): void
    {
        $fromNumber = (string) config('whatsapp.sender_number');

        try {
            $result = $gateway->sendMessage($this->to, $this->message);
            $body = $result['body'] ?? [];
            $gatewayMessageId = is_array($body)
                ? ($body['id'] ?? $body['message_id'] ?? null)
                : null;

            WhatsAppMessage::query()->create([
                'direction' => 'out',
                'gateway_message_id' => $gatewayMessageId,
                'to_number' => $this->to,
                'from_number' => $fromNumber,
                'body' => $this->message,
                'message_type' => $this->messageType,
                'status' => ($result['success'] ?? false) ? 'sent' : 'failed',
                'related_entity_type' => $this->relatedEntityType,
                'related_entity_id' => $this->relatedEntityId,
                'error' => ($result['success'] ?? false)
                    ? null
                    : json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $exception) {
            WhatsAppMessage::query()->create([
                'direction' => 'out',
                'to_number' => $this->to,
                'from_number' => $fromNumber,
                'body' => $this->message,
                'message_type' => $this->messageType,
                'status' => 'failed',
                'related_entity_type' => $this->relatedEntityType,
                'related_entity_id' => $this->relatedEntityId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
