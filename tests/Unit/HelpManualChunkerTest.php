<?php

namespace Tests\Unit;

use App\Services\Help\HelpManualChunker;
use PHPUnit\Framework\TestCase;

class HelpManualChunkerTest extends TestCase
{
    public function test_collects_non_empty_chunks_when_manuals_exist(): void
    {
        $root = dirname(__DIR__, 2);
        $chunker = new HelpManualChunker(
            $root.'/docs/manuals',
            $root.'/docs/manuals/help-navigation.json',
        );

        $chunks = $chunker->collectAllChunks();
        $this->assertNotEmpty($chunks);
        $this->assertArrayHasKey('chunk_key', $chunks[0]);
        $this->assertArrayHasKey('content', $chunks[0]);
    }
}
