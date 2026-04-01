<?php

namespace App\Services\Help;

use App\Models\HelpEmbedding;

class HelpAssistantService
{
    public function __construct(
        private HelpOpenRouterClient $openRouter,
    ) {}

    /**
     * @return array{answer: string, sources: list<array{title: string, path: string, heading: ?string}>, not_documented: bool}
     */
    public function answer(string $userMessage, string $locale): array
    {
        $locale = $locale === 'id' ? 'id' : 'en';

        $embeddingModel = (string) config('services.openrouter.embedding_model');
        $chatModel = (string) config('services.openrouter.chat_model');
        $threshold = (float) config('help.similarity_threshold');
        $topK = (int) config('help.top_k');

        $queryVectors = $this->openRouter->createEmbeddings($embeddingModel, [trim($userMessage)]);
        $queryEmbedding = $queryVectors[0] ?? [];

        $candidates = HelpEmbedding::query()->get(['id', 'source_path', 'heading', 'locale', 'content', 'embedding']);

        if ($candidates->isEmpty()) {
            return [
                'answer' => $this->noIndexMessage($locale),
                'sources' => [],
                'not_documented' => true,
            ];
        }

        $scored = [];
        foreach ($candidates as $row) {
            /** @var array<int, float> $emb */
            $emb = $row->embedding;
            $score = HelpVector::cosineSimilarity($queryEmbedding, $emb);
            if ($row->locale === $locale) {
                $score += 0.04;
            } elseif ($row->locale === 'both') {
                $score += 0.02;
            }
            $scored[] = ['row' => $row, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, $topK);
        $best = $top[0]['score'] ?? 0.0;

        if ($best < $threshold) {
            return [
                'answer' => $this->notDocumentedMessage($locale),
                'sources' => [],
                'not_documented' => true,
            ];
        }

        $contextParts = [];
        $sources = [];
        foreach ($top as $item) {
            if ($item['score'] < $threshold) {
                break;
            }
            $row = $item['row'];
            $contextParts[] = $row->content;
            $sources[] = [
                'title' => basename((string) $row->source_path),
                'path' => $row->source_path,
                'heading' => $row->heading,
            ];
        }

        if ($contextParts === []) {
            return [
                'answer' => $this->notDocumentedMessage($locale),
                'sources' => [],
                'not_documented' => true,
            ];
        }

        $sources = $this->uniqueSources($sources);

        $context = implode("\n\n---\n\n", $contextParts);
        $system = $this->systemPrompt($locale);
        $user = "CONTEXT (Sarang ERP documentation excerpts — do not invent steps beyond this):\n\n".$context
            ."\n\nUSER QUESTION:\n".$userMessage;

        $answer = $this->openRouter->chatCompletion($chatModel, [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], 0.2);

        return [
            'answer' => $answer,
            'sources' => $sources,
            'not_documented' => false,
        ];
    }

    private function systemPrompt(string $locale): string
    {
        $lang = $locale === 'id' ? 'Indonesian (Bahasa Indonesia)' : 'English';

        return <<<PROMPT
You are the in-app HELP assistant for Sarang ERP only.
Answer using ONLY the CONTEXT provided. Scope: how-to steps and where to find features in this application.
If CONTEXT does not contain enough detail, say briefly that it is not documented here and avoid inventing menus, buttons, or field names.
Do not give general tax/legal advice; you may describe how the application records something if CONTEXT says so.
Respond in {$lang}.
End with a short "Sources:" line listing the Source filenames from CONTEXT.
PROMPT;
    }

    private function noIndexMessage(string $locale): string
    {
        return $locale === 'id'
            ? 'Indeks bantuan belum dibuat. Jalankan `php artisan help:reindex` di server (setelah OPENROUTER_API_KEY diatur).'
            : 'The help index is empty. Ask an administrator to run `php artisan help:reindex` (OPENROUTER_API_KEY must be set).';
    }

    private function notDocumentedMessage(string $locale): string
    {
        return $locale === 'id'
            ? 'Tidak ada dokumentasi yang cukup relevan untuk pertanyaan ini di indeks Panduan. Periksa menu Dokumentasi/manual di repositori atau hubungi tim IT.'
            : 'No sufficiently relevant documentation was found in the help index for this question. Check the manuals in the repository or contact your IT team.';
    }

    /**
     * @param  list<array{title: string, path: string, heading: ?string}>  $sources
     * @return list<array{title: string, path: string, heading: ?string}>
     */
    private function uniqueSources(array $sources): array
    {
        $seen = [];
        $out = [];
        foreach ($sources as $s) {
            $k = $s['path'].'|'.($s['heading'] ?? '');
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $s;
        }

        return $out;
    }
}
