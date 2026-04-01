<?php

namespace App\Services\Help;

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

        $response = Http::timeout(120)
            ->withHeaders($this->headers())
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
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.2): string
    {
        $this->requireKey();

        $response = Http::timeout(120)
            ->withHeaders($this->headers())
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
