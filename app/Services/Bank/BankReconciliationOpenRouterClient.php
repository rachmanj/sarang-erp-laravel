<?php

namespace App\Services\Bank;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BankReconciliationOpenRouterClient
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    public function __construct(
        private ?string $apiKey,
        private string $siteUrl,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $plugins
     * @return array<string, mixed>
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.1, array $plugins = []): array
    {
        $this->requireKey();

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'response_format' => ['type' => 'json_object'],
        ];

        if ($plugins !== []) {
            $payload['plugins'] = $plugins;
        }

        $response = $this->http()
            ->post(self::BASE_URL.'/chat/completions', $payload);

        $response->throw();

        /** @var array<string, mixed> */
        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function chatCompletionWithPdf(
        string $model,
        string $systemPrompt,
        string $userPrompt,
        string $filename,
        string $absolutePath,
        ?string $pdfEngine = null,
    ): array {
        $pdfEngine ??= (string) config('services.bank_reconciliation.pdf_engine', 'pdf-text');
        $fileData = 'data:application/pdf;base64,'.base64_encode((string) file_get_contents($absolutePath));

        return $this->chatCompletion($model, [
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $userPrompt],
                    [
                        'type' => 'file',
                        'file' => [
                            'filename' => $filename,
                            'file_data' => $fileData,
                        ],
                    ],
                ],
            ],
        ], 0.1, [
            [
                'id' => 'file-parser',
                'pdf' => [
                    'engine' => $pdfEngine,
                ],
            ],
        ]);
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
