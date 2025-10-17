<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DocumentRelationshipCacheService;

class CacheDocumentRelationships extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:cache-relationships 
                            {action : The action to perform (warm|clear|stats)}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage document relationship caching';

    protected DocumentRelationshipCacheService $cacheService;

    public function __construct(DocumentRelationshipCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $force = $this->option('force');

        switch ($action) {
            case 'warm':
                return $this->warmCache($force);
            case 'clear':
                return $this->clearCache($force);
            case 'stats':
                return $this->showStats();
            default:
                $this->error('Invalid action. Use: warm, clear, or stats');
                return 1;
        }
    }

    /**
     * Warm up the cache.
     */
    protected function warmCache(bool $force): int
    {
        if (!$force && !$this->confirm('This will warm up the document relationship cache. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Warming up document relationship cache...');

        try {
            $this->cacheService->warmUpCache();
            $this->info('Cache warming completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Cache warming failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clear the cache.
     */
    protected function clearCache(bool $force): int
    {
        if (!$force && !$this->confirm('This will clear all document relationship caches. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Clearing document relationship caches...');

        try {
            $this->cacheService->clearAllCaches();
            $this->info('Cache clearing completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Cache clearing failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show cache statistics.
     */
    protected function showStats(): int
    {
        $this->info('Document Relationship Cache Statistics:');
        $this->line('');

        try {
            $stats = $this->cacheService->getCacheStats();

            $this->table(
                ['Setting', 'Value'],
                [
                    ['Cache Prefix', $stats['cache_prefix']],
                    ['Cache TTL', $stats['cache_ttl'] . ' seconds'],
                    ['Cache Driver', $stats['driver']],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to retrieve cache statistics: ' . $e->getMessage());
            return 1;
        }
    }
}
