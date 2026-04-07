<?php

namespace Tests\Feature;

use App\Models\BusinessPartner;
use App\Models\User;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessPartnerAccountStatementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['business_partners.view']);
        $this->actingAs($user);
    }

    public function test_account_statement_returns_json_for_ajax_requests(): void
    {
        $partner = BusinessPartner::query()->first();
        $this->assertNotNull($partner);

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/business-partners/'.$partner->id.'/account-statement?start_date=2026-01-01&end_date=2026-12-31&page=1&per_page=25');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $data = $response->json();
        $this->assertArrayHasKey('opening_balance', $data);
        $this->assertArrayHasKey('closing_balance', $data);
        $this->assertArrayHasKey('total_debits', $data);
        $this->assertArrayHasKey('total_credits', $data);
        $this->assertArrayHasKey('transactions', $data);
        $this->assertArrayHasKey('pagination', $data);
    }

    public function test_journal_history_url_redirects_to_account_statement_preserving_query_string(): void
    {
        $partner = BusinessPartner::query()->first();
        $this->assertNotNull($partner);

        $response = $this->get('/business-partners/'.$partner->id.'/journal-history?page=2&per_page=10');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/business-partners/'.$partner->id.'/account-statement', $location);
        $this->assertStringContainsString('page=2', $location);
        $this->assertStringContainsString('per_page=10', $location);
    }

    public function test_account_statement_one_row_per_journal_on_partner_account(): void
    {
        $partner = BusinessPartner::query()->where('partner_type', 'supplier')->first();
        $this->assertNotNull($partner);

        $account = $partner->getAccountOrDefault();
        $this->assertNotNull($account);

        $otherAccountId = (int) DB::table('accounts')->where('id', '!=', $account->id)->value('id');

        $service = app(PostingService::class);
        $service->postJournal([
            'date' => now()->toDateString(),
            'description' => 'Statement test journal',
            'source_type' => 'test',
            'source_id' => 1,
            'lines' => [
                ['account_id' => $account->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $otherAccountId, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/business-partners/'.$partner->id.'/account-statement?start_date='.now()->startOfYear()->toDateString().'&end_date='.now()->endOfYear()->toDateString().'&page=1&per_page=25');

        $response->assertOk();
        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, count($data['transactions']));
        $row = collect($data['transactions'])->firstWhere('description', 'Statement test journal');
        $this->assertNotNull($row);
        $this->assertEquals(1000.0, (float) $row['debit']);
        $this->assertEquals(0.0, (float) $row['credit']);
        $this->assertSame('Test Posting', $row['document_type']);
    }

    public function test_account_statement_csv_export_returns_csv(): void
    {
        $partner = BusinessPartner::query()->first();
        $this->assertNotNull($partner);

        $response = $this->get('/business-partners/'.$partner->id.'/account-statement/export?start_date=2026-01-01&end_date=2026-12-31&format=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Partner Code', $response->getContent());
    }

    public function test_account_statement_pdf_export_returns_pdf(): void
    {
        $partner = BusinessPartner::query()->first();
        $this->assertNotNull($partner);

        $response = $this->get('/business-partners/'.$partner->id.'/account-statement/export?start_date=2026-01-01&end_date=2026-12-31&format=pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
