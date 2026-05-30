<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesReceiptAvailableInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo('ar.receipts.create');
        $this->actingAs($user);
    }

    public function test_available_invoices_are_filtered_by_company_entity(): void
    {
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityPt = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $entityCv = (int) DB::table('company_entities')->where('code', '72')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoicePt = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-ENTITY-PT-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityPt,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 1000,
            'total_amount_foreign' => 1000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceCv = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-ENTITY-CV-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityCv,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 2000,
            'total_amount_foreign' => 2000,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/sales-receipts/available-invoices?'.http_build_query([
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityPt,
        ]));

        $response->assertOk();
        $ids = collect($response->json('invoices'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains((int) $invoicePt, $ids);
        $this->assertNotContains((int) $invoiceCv, $ids);
    }

    public function test_store_rejects_invoice_from_different_company_entity(): void
    {
        $cashId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityPt = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $entityCv = (int) DB::table('company_entities')->where('code', '72')->value('id');
        $currencyId = (int) DB::table('currencies')->orderBy('id')->value('id');

        $invoiceCv = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => 'SI-WRONG-ENTITY-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityCv,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 500,
            'total_amount_foreign' => 500,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->from('/sales-receipts/create')->post('/sales-receipts', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityPt,
            'lines' => [
                ['account_id' => $cashId, 'amount' => 100],
            ],
            'allocations' => [
                ['invoice_id' => $invoiceCv, 'amount' => 100],
            ],
        ]);

        $response->assertRedirect('/sales-receipts/create');
        $response->assertSessionHasErrors('allocations');
    }
}
