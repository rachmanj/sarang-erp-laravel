<?php

namespace Tests\Feature;

use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentNumberingResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        config(['accounting.start_date' => '2020-01-01']);
    }

    public function test_generate_number_uses_max_of_counter_and_existing_documents_when_counter_lags(): void
    {
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $year = (int) now()->format('Y');
        $date = now()->toDateString();
        $insertedNumber = sprintf('71%02d1200007', $year % 100);

        DB::table('document_sequences')->insert([
            'company_entity_id' => $entityId,
            'document_type' => 'journal_entity_'.$entityId,
            'document_code' => '12',
            'year' => $year,
            'year_month' => sprintf('%04d00', $year),
            'last_sequence' => 5,
            'current_number' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journals')->insert([
            'date' => $date,
            'description' => 'Inserted outside numbering',
            'journal_no' => $insertedNumber,
            'company_entity_id' => $entityId,
            'source_type' => 'external',
            'source_id' => 0,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(DocumentNumberingService::class);
        $nextNumber = $service->generateNumber('journal', $date, [
            'company_entity_id' => $entityId,
        ]);

        $this->assertSame(sprintf('71%02d1200008', $year % 100), $nextNumber);
        $this->assertSame(
            8,
            (int) DB::table('document_sequences')
                ->where('company_entity_id', $entityId)
                ->where('document_code', '12')
                ->where('year', $year)
                ->value('current_number')
        );
    }

    public function test_post_journal_succeeds_after_journal_number_inserted_outside_application(): void
    {
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $year = (int) now()->format('Y');
        $date = now()->toDateString();

        DB::table('document_sequences')->insert([
            'company_entity_id' => $entityId,
            'document_type' => 'journal_entity_'.$entityId,
            'document_code' => '12',
            'year' => $year,
            'year_month' => sprintf('%04d00', $year),
            'last_sequence' => 3,
            'current_number' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journals')->insert([
            'date' => $date,
            'description' => 'Manual SQL journal',
            'journal_no' => sprintf('71%02d1200005', $year % 100),
            'company_entity_id' => $entityId,
            'source_type' => 'external',
            'source_id' => 0,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $postingService = app(PostingService::class);
        $journalId = $postingService->postJournal([
            'date' => $date,
            'description' => 'After external insert',
            'source_type' => 'test',
            'source_id' => 99,
            'company_entity_id' => $entityId,
            'lines' => [
                ['account_id' => $this->accountId('1.1.2.01'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('4.1.1.01'), 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $journalNo = DB::table('journals')->where('id', $journalId)->value('journal_no');
        $this->assertSame(sprintf('71%02d1200006', $year % 100), $journalNo);
    }

    public function test_generate_number_unchanged_when_counter_matches_highest_used(): void
    {
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $year = (int) now()->format('Y');
        $date = now()->toDateString();

        DB::table('document_sequences')->insert([
            'company_entity_id' => $entityId,
            'document_type' => 'journal_entity_'.$entityId,
            'document_code' => '12',
            'year' => $year,
            'year_month' => sprintf('%04d00', $year),
            'last_sequence' => 4,
            'current_number' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journals')->insert([
            'date' => $date,
            'description' => 'Aligned journal',
            'journal_no' => sprintf('71%02d1200004', $year % 100),
            'company_entity_id' => $entityId,
            'source_type' => 'external',
            'source_id' => 0,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(DocumentNumberingService::class);
        $nextNumber = $service->generateNumber('journal', $date, [
            'company_entity_id' => $entityId,
        ]);

        $this->assertSame(sprintf('71%02d1200005', $year % 100), $nextNumber);
    }

    private function accountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }
}
