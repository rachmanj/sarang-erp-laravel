<?php

namespace App\Services\WhatsApp;

interface WhatsAppGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $to, string $message): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchNewMessages(): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendInteractiveApproval(string $to, array $payload): array;
}
