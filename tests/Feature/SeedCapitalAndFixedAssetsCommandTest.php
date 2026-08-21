<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeedCapitalAndFixedAssetsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const DESCRIPTIONS = [
        'Opening Balance - Modal Disetor PT',
        'Opening Balance - Modal Penyertaan CV',
        'Opening Balance - Aset Tetap PT',
        'Opening Balance - Aset Tetap CV',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_command_is_idempotent_and_posts_balanced_journals(): void
    {
        $ptEntityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $cvEntityId = (int) DB::table('company_entities')->where('code', '72')->value('id');

        $this->assertSame(0, Artisan::call('accounts:seed-capital-fixed-assets'));
        $this->assertSame(0, Artisan::call('accounts:seed-capital-fixed-assets'));

        $this->assertDatabaseHas('accounts', [
            'code' => '3.1.3',
            'name' => 'Modal/Penyertaan CV',
            'type' => 'net_assets',
            'is_postable' => true,
        ]);

        $this->assertSame(4, DB::table('journals')
            ->where('source_type', 'manual_journal')
            ->whereIn('description', self::DESCRIPTIONS)
            ->count());

        $entityByDescription = [
            'Opening Balance - Modal Disetor PT' => $ptEntityId,
            'Opening Balance - Modal Penyertaan CV' => $cvEntityId,
            'Opening Balance - Aset Tetap PT' => $ptEntityId,
            'Opening Balance - Aset Tetap CV' => $cvEntityId,
        ];

        foreach ($entityByDescription as $description => $entityId) {
            $journal = DB::table('journals')
                ->where('source_type', 'manual_journal')
                ->where('description', $description)
                ->where('company_entity_id', $entityId)
                ->first();

            $this->assertNotNull($journal, "Missing journal: {$description}");
            $this->assertSame('2026-01-01', $journal->date);

            $lines = DB::table('journal_lines')->where('journal_id', $journal->id)->get();
            $debit = $lines->sum(fn ($line) => (float) $line->debit);
            $credit = $lines->sum(fn ($line) => (float) $line->credit);

            $this->assertSame(round($debit, 2), round($credit, 2), "Journal not balanced: {$description}");
        }
    }

    public function test_dry_run_does_not_create_journals(): void
    {
        $this->assertSame(0, Artisan::call('accounts:seed-capital-fixed-assets', ['--dry-run' => true]));

        $this->assertSame(0, DB::table('journals')
            ->where('source_type', 'manual_journal')
            ->whereIn('description', self::DESCRIPTIONS)
            ->count());
    }
}
