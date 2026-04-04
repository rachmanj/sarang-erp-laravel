<?php

namespace App\Services\Help;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class HelpOpenRouterClient
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    public function __construct(
        private ?string $apiKey,
        private string $siteUrl,
    ) {}

    /**
     * @param  list<string>  $inputs
     * @return list<array<int, float>>
     */
    public function createEmbeddings(string $model, array $inputs): array
    {
        $this->requireKey();

        return $this->postEmbeddingsWithRetry($model, $inputs);
    }

    /**
     * @param  list<string>  $inputs
     * @return list<array<int, float>>
     */
    private function postEmbeddingsWithRetry(string $model, array $inputs): array
    {
        $attempts = max(1, (int) config('services.openrouter.embedding_retries', 2));
        $lastException = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $response = $this->http()
                    ->post(self::BASE_URL.'/embeddings', [
                        'model' => $model,
                        'input' => $inputs,
                    ]);

                $response->throw();

                /** @var array{data: list<array{embedding: array<int, float>}>} $json */
                $json = $response->json();
                $out = [];
                foreach ($json['data'] as $item) {
                    $out[] = array_values($item['embedding']);
                }

                return $out;
            } catch (\Throwable $e) {
                $lastException = $e;
                if (! $this->isRetryableTransferFailure($e) || $i >= $attempts - 1) {
                    throw $e;
                }
                usleep(500_000);
            }
        }

        throw $lastException ?? new \RuntimeException('OpenRouter embeddings request failed.');
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

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.2): string
    {
        $this->requireKey();

        $response = $this->http()
            ->post(self::BASE_URL.'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

        $response->throw();

        /** @var array{choices: list<array{message: array{content: string}}>} $json */
        $json = $response->json();

        return trim($json['choices'][0]['message']['content'] ?? '');
    }

    /**
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function http()
    {
        $timeout = max(30, (int) config('services.openrouter.timeout', 240));
        $connectTimeout = max(5, (int) config('services.openrouter.connect_timeout', 30));

        return Http::timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withHeaders($this->headers());
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'HTTP-Referer' => $this->siteUrl,
            'Content-Type' => 'application/json',
        ];
    }

    private function requireKey(): void
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new \RuntimeException('OPENROUTER_API_KEY is not configured.');
        }
    }
}
