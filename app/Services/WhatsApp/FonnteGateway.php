<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class FonnteGateway implements WhatsAppGatewayInterface
{
    private const SEND_URL = 'https://api.fonnte.com/send';

    private const GET_MESSAGES_URL = 'https://api.fonnte.com/get-messages';

    public function sendMessage(string $to, string $message): array
    {
        $response = Http::withHeaders([
            'Authorization' => config('whatsapp.api_key'),
        ])->post(self::SEND_URL, [
            'target' => $to,
            'message' => $message,
        ]);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? ['raw' => $response->body()],
        ];
    }

    public function fetchNewMessages(): array
    {
        $response = Http::withHeaders([
            'Authorization' => config('whatsapp.api_key'),
        ])->get(self::GET_MESSAGES_URL);

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        $messages = $payload['data'] ?? $payload['messages'] ?? $payload;

        return is_array($messages) ? $messages : [];
    }

    public function sendInteractiveApproval(string $to, array $payload): array
    {
        $message = $payload['message'] ?? '';

        if ($message === '' && isset($payload['title'], $payload['body'])) {
            $message = trim($payload['title']."\n\n".$payload['body']);
        }

        return $this->sendMessage($to, $message);
    }
}
