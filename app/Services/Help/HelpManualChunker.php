<?php

namespace App\Services\Help;

class HelpManualChunker
{
    private const SKIP_BASENAMES = [
        'inventory-manual-coverage-analysis.md',
    ];

    public function __construct(
        private string $manualsPath,
        private string $navigationJsonPath,
    ) {}

    /**
     * @return list<array{chunk_key: string, source_path: string, heading: ?string, locale: string, content: string}>
     */
    public function collectAllChunks(): array
    {
        $chunks = [];

        foreach ($this->chunkMarkdownFiles() as $c) {
            $chunks[] = $c;
        }

        foreach ($this->chunkNavigationJson() as $c) {
            $chunks[] = $c;
        }

        return $chunks;
    }

    /**
     * @return list<array{chunk_key: string, source_path: string, heading: ?string, locale: string, content: string}>
     */
    private function chunkMarkdownFiles(): array
    {
        if (! is_dir($this->manualsPath)) {
            return [];
        }

        $paths = glob($this->manualsPath.DIRECTORY_SEPARATOR.'*.md') ?: [];
        $out = [];

        foreach ($paths as $fullPath) {
            $basename = basename($fullPath);
            if (in_array($basename, self::SKIP_BASENAMES, true)) {
                continue;
            }
            if ($basename === 'help-navigation.json') {
                continue;
            }

            $relative = 'docs/manuals/'.$basename;
            $locale = $this->detectLocaleFromFilename($basename);
            $body = (string) file_get_contents($fullPath);
            $sections = $this->splitByH2($body);

            foreach ($sections as $idx => $section) {
                $heading = $section['heading'];
                $text = $section['content'];
                if (trim(strip_tags($text)) === '') {
                    continue;
                }

                $chunkKey = hash('sha256', $relative.'|'.$idx.'|'.$heading);
                $out[] = [
                    'chunk_key' => $chunkKey,
                    'source_path' => $relative,
                    'heading' => $heading,
                    'locale' => $locale,
                    'content' => $this->buildChunkBody($relative, $heading, $text),
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array{heading: string, content: string}>
     */
    private function splitByH2(string $markdown): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $markdown) ?: [];
        $sections = [];
        $currentHeading = 'Introduction';
        $buffer = [];

        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.+)$/', $line, $m)) {
                if ($buffer !== []) {
                    $sections[] = [
                        'heading' => $currentHeading,
                        'content' => implode("\n", $buffer),
                    ];
                }
                $currentHeading = trim($m[1]);
                $buffer = [$line];
            } else {
                $buffer[] = $line;
            }
        }

        if ($buffer !== []) {
            $sections[] = [
                'heading' => $currentHeading,
                'content' => implode("\n", $buffer),
            ];
        }

        return $sections;
    }

    private function buildChunkBody(string $sourcePath, string $heading, string $text): string
    {
        return "Source: {$sourcePath}\nSection: {$heading}\n\n".trim($text);
    }

    private function detectLocaleFromFilename(string $basename): string
    {
        if (preg_match('/(^|-)id\.md$/', $basename) === 1 || str_contains($basename, '-manual-id')) {
            return 'id';
        }

        return 'en';
    }

    /**
     * @return list<array{chunk_key: string, source_path: string, heading: ?string, locale: string, content: string}>
     */
    private function chunkNavigationJson(): array
    {
        if (! is_readable($this->navigationJsonPath)) {
            return [];
        }

        $raw = (string) file_get_contents($this->navigationJsonPath);
        /** @var array{entries?: list<array<string, mixed>>} $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $entries = $data['entries'] ?? [];
        $out = [];

        foreach ($entries as $i => $entry) {
            $id = is_string($entry['id'] ?? null) ? $entry['id'] : 'entry-'.$i;
            $lines = [];
            if (! empty($entry['title_en'])) {
                $lines[] = 'Title (EN): '.$entry['title_en'];
            }
            if (! empty($entry['title_id'])) {
                $lines[] = 'Title (ID): '.$entry['title_id'];
            }
            if (! empty($entry['menu_path_en'])) {
                $lines[] = 'Menu path (EN): '.$entry['menu_path_en'];
            }
            if (! empty($entry['menu_path_id'])) {
                $lines[] = 'Menu path (ID): '.$entry['menu_path_id'];
            }
            if (! empty($entry['keywords_en'])) {
                $lines[] = 'Keywords (EN): '.implode(', ', (array) $entry['keywords_en']);
            }
            if (! empty($entry['keywords_id'])) {
                $lines[] = 'Keywords (ID): '.implode(', ', (array) $entry['keywords_id']);
            }
            if (! empty($entry['notes'])) {
                $lines[] = 'Notes: '.$entry['notes'];
            }

            $content = "Source: docs/manuals/help-navigation.json\nNavigation index\n\n".implode("\n", $lines);
            $title = is_string($entry['title_en'] ?? null) ? $entry['title_en'] : (is_string($entry['title_id'] ?? null) ? $entry['title_id'] : 'Navigation');

            $out[] = [
                'chunk_key' => hash('sha256', 'help-navigation.json|'.$id),
                'source_path' => 'docs/manuals/help-navigation.json',
                'heading' => $title,
                'locale' => 'both',
                'content' => $content,
            ];
        }

        return $out;
    }
}
