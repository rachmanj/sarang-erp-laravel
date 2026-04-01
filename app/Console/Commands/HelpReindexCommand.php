<?php

namespace App\Console\Commands;

use App\Models\HelpEmbedding;
use App\Services\Help\HelpManualChunker;
use App\Services\Help\HelpOpenRouterClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HelpReindexCommand extends Command
{
    protected $signature = 'help:reindex';

    protected $description = 'Rebuild help_embeddings from docs/manuals (requires OPENROUTER_API_KEY)';

    public function handle(HelpOpenRouterClient $openRouter): int
    {
        $chunker = new HelpManualChunker(
            (string) config('help.manuals_path'),
            (string) config('help.navigation_json'),
        );

        $this->info('Collecting manual chunks…');
        $chunks = $chunker->collectAllChunks();
        $this->info(count($chunks).' chunks.');

        if ($chunks === []) {
            $this->warn('No chunks found. Check docs/manuals and help-navigation.json.');

            return self::FAILURE;
        }

        $embeddingModel = (string) config('services.openrouter.embedding_model');
        $batchSize = 12;

        DB::table('help_embeddings')->truncate();

        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        try {
            for ($i = 0; $i < count($chunks); $i += $batchSize) {
                $batch = array_slice($chunks, $i, $batchSize);
                $inputs = array_map(function (array $c) {
                    return mb_substr($c['content'], 0, 30000);
                }, $batch);

                $vectors = $openRouter->createEmbeddings($embeddingModel, $inputs);

                foreach ($batch as $j => $chunk) {
                    HelpEmbedding::create([
                        'chunk_key' => $chunk['chunk_key'],
                        'source_path' => $chunk['source_path'],
                        'heading' => $chunk['heading'],
                        'locale' => $chunk['locale'],
                        'content' => $chunk['content'],
                        'embedding' => $vectors[$j],
                    ]);
                    $bar->advance();
                }
            }
        } catch (\Throwable $e) {
            $bar->finish();
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
