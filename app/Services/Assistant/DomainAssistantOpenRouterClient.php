<?php

namespace App\Services\Assistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DomainAssistantOpenRouterClient
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    public function __construct(
        private ?string $apiKey,
        private string $siteUrl,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    public function chatCompletionWithTools(
        string $model,
        array $messages,
        array $tools,
        float $temperature = 0.2,
    ): array {
        $this->requireKey();

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ];
        if ($tools !== []) {
            $payload['tools'] = $tools;
        }

        $response = $this->http()
            ->post(self::BASE_URL.'/chat/completions', $payload);

        $response->throw();

        /** @var array<string, mixed> */
        return $response->json();
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.2): array
    {
        $this->requireKey();

        $response = $this->http()
            ->post(self::BASE_URL.'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

        $response->throw();

        /** @var array<string, mixed> */
        return $response->json();
    }

    private function http()
    {
        $timeout = max(30, (int) config('services.openrouter.timeout', 240));
        $connectTimeout = max(5, (int) config('services.openrouter.connect_timeout', 30));

        return Http::timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'HTTP-Referer' => $this->siteUrl,
                'Content-Type' => 'application/json',
            ]);
    }

    private function requireKey(): void
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new \RuntimeException('OPENROUTER_API_KEY is not configured.');
        }
    }

    private function isRetryableTransferFailure(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        $msg = $e->getMessage();

        return str_contains($msg, 'cURL error 28')
            || str_contains($msg, 'Operation timed out')
            || str_contains($msg, 'Connection timed out');
    }
}
