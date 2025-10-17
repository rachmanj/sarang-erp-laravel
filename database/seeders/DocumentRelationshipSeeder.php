<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\DocumentRelationshipService;

class DocumentRelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Initializing document relationships...');
        
        $relationshipService = app(DocumentRelationshipService::class);
        $relationshipService->initializeExistingRelationships();
        
        $this->command->info('Document relationships initialized successfully!');
    }
}