<?php

namespace Tests\Feature;

use App\Models\BusinessPartner;
use App\Models\CustomerApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/invoices')->assertStatus(401);
    }

    public function test_index_rejects_invalid_token(): void
    {
        $this->getJson('/api/v1/invoices', [
            'Authorization' => 'Bearer invalid-token-that-does-not-exist',
        ])->assertStatus(401);
    }

    public function test_index_rejects_expired_token(): void
    {
        $partner = BusinessPartner::query()->customers()->active()->firstOrFail();
        $created = CustomerApiKey::createForPartner($partner, 'expired', now()->subDay());
        $plain = $created['plain_text_token'];

        $this->getJson('/api/v1/invoices', [
            'Authorization' => 'Bearer '.$plain,
        ])->assertStatus(401);
    }

    public function test_index_rejects_suspended_customer(): void
    {
        $partner = BusinessPartner::query()->customers()->active()->firstOrFail();
        $partner->update(['status' => 'suspended']);
        $created = CustomerApiKey::createForPartner($partner, 'x');
        $plain = $created['plain_text_token'];

        $this->getJson('/api/v1/invoices', [
            'Authorization' => 'Bearer '.$plain,
        ])->assertStatus(401);
    }

    public function test_index_rejects_supplier_partner_key(): void
    {
        $supplierId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        $this->assertGreaterThan(0, $supplierId);

        $plain = 'supplier-secret-token-test-only';
        CustomerApiKey::query()->create([
            'business_partner_id' => $supplierId,
            'name' => 'Supplier key',
            'token' => hash('sha256', $plain),
            'expires_at' => null,
        ]);

        $this->getJson('/api/v1/invoices', [
            'Authorization' => 'Bearer '.$plain,
        ])->assertStatus(401);
    }

    public function test_index_returns_own_invoices_and_supports_filters(): void
    {
        $partner = BusinessPartner::query()->customers()->active()->firstOrFail();
        $created = CustomerApiKey::createForPartner($partner, 'portal');
        $plain = $created['plain_text_token'];

        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $accountId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');
        $this->assertGreaterThan(0, $accountId);

        $invoiceNo = 'API-INV-'.uniqid();

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => $invoiceNo,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'terms_days' => 30,
            'business_partner_id' => $partner->id,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'description' => 'API test invoice',
            'total_amount' => 250,
            'total_amount_foreign' => 250,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'item_code' => 'SKU1',
            'item_name' => 'Widget',
            'description' => 'Widget line',
            'qty' => 2,
            'unit_price' => 125,
            'amount' => 250,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/invoices?status=posted', [
            'Authorization' => 'Bearer '.$plain,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.invoice_no', $invoiceNo);

        $filteredOut = $this->getJson('/api/v1/invoices?status=draft', [
            'Authorization' => 'Bearer '.$plain,
        ]);
        $filteredOut->assertOk();
        $this->assertCount(0, $filteredOut->json('data'));
    }

    public function test_show_returns_invoice_with_lines(): void
    {
        $partner = BusinessPartner::query()->customers()->active()->firstOrFail();
        $created = CustomerApiKey::createForPartner($partner, 'portal');
        $plain = $created['plain_text_token'];

        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $accountId = (int) DB::table('accounts')->where('code', '4.1.1')->value('id');

        $invoiceNo = 'API-DTL-'.uniqid();

        $invoiceId = DB::table('sales_invoices')->insertGetId([
            'invoice_no' => $invoiceNo,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'terms_days' => 30,
            'business_partner_id' => $partner->id,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'description' => 'Detail test',
            'total_amount' => 100,
            'total_amount_foreign' => 100,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_invoice_lines')->insert([
            'invoice_id' => $invoiceId,
            'account_id' => $accountId,
            'item_code' => 'X',
            'item_name' => 'Item',
            'description' => 'One line',
            'qty' => 1,
            'unit_price' => 100,
            'amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/invoices/'.$invoiceNo, [
            'Authorization' => 'Bearer '.$plain,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.invoice_no', $invoiceNo);
        $response->assertJsonPath('data.lines.0.qty', 1);
        $response->assertJsonPath('data.lines.0.total', 100);
    }

    public function test_show_returns_404_for_other_customer_invoice(): void
    {
        $partnerA = BusinessPartner::query()->customers()->active()->orderBy('id')->firstOrFail();
        $partnerB = BusinessPartner::query()->customers()->active()->where('id', '!=', $partnerA->id)->first();
        if ($partnerB === null) {
            $partnerB = BusinessPartner::query()->create([
                'code' => 'CUST-API-'.substr(uniqid(), -8),
                'name' => 'Second API Test Customer',
                'partner_type' => 'customer',
                'status' => 'active',
            ]);
        }

        $created = CustomerApiKey::createForPartner($partnerA, 'a');
        $plain = $created['plain_text_token'];

        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');

        $invoiceNo = 'API-OTHER-'.uniqid();

        DB::table('sales_invoices')->insert([
            'invoice_no' => $invoiceNo,
            'date' => now()->toDateString(),
            'business_partner_id' => $partnerB->id,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 50,
            'total_amount_foreign' => 50,
            'status' => 'posted',
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/invoices/'.$invoiceNo, [
            'Authorization' => 'Bearer '.$plain,
        ])->assertStatus(404);
    }
}
