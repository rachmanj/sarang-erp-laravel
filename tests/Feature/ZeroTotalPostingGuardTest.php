<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ZeroTotalPostingGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function actingAsPurchaseUser(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post', 'accounts.view']);
        $this->actingAs($user);
    }

    private function actingAsSalesUser(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['ar.invoices.view', 'ar.invoices.create', 'ar.invoices.post']);
        $this->actingAs($user);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createTestInventoryItem(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'TEST-ZT-'.uniqid(),
            'name' => 'Test Zero Total Item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        return [(int) $item->id, (int) $warehouse->id];
    }

    private function assertZeroTotalPostGuard(int $invoiceId, string $postUrl, string $journalSourceType): void
    {
        $journalCountBefore = DB::table('journals')
            ->where('source_type', $journalSourceType)
            ->where('source_id', $invoiceId)
            ->count();

        $postResp = $this->post($postUrl);

        $postResp->assertRedirect();
        $postResp->assertStatus(302);
        $postResp->assertSessionHas('error');
        $error = session('error');
        $this->assertIsString($error);
        $this->assertStringContainsString('Total faktur Rp 0', $error);

        $journalCountAfter = DB::table('journals')
            ->where('source_type', $journalSourceType)
            ->where('source_id', $invoiceId)
            ->count();
        $this->assertSame($journalCountBefore, $journalCountAfter);
    }

    public function test_credit_purchase_invoice_with_hundred_percent_discount_cannot_post(): void
    {
        $this->actingAsPurchaseUser();

        $expenseId = (int) DB::table('accounts')->where('code', '6.2.1')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');

        $resp = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'payment_method' => 'credit',
            'description' => 'PI zero total credit',
            'lines' => [
                [
                    'account_id' => $expenseId,
                    'description' => 'Free item',
                    'qty' => 1,
                    'unit_price' => 10000,
                    'discount_amount' => 10000,
                ],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'total_amount' => 0, 'status' => 'draft']);

        $this->assertZeroTotalPostGuard($invoiceId, '/purchase-invoices/'.$invoiceId.'/post', 'purchase_invoice');

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'status' => 'draft']);
    }

    public function test_cash_direct_purchase_invoice_with_hundred_percent_discount_cannot_post(): void
    {
        $this->actingAsPurchaseUser();

        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $businessPartnerId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->value('id');
        [$itemId, $warehouseId] = $this->createTestInventoryItem();
        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');

        $resp = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $businessPartnerId,
            'company_entity_id' => $entityId,
            'payment_method' => 'cash',
            'cash_account_id' => $cashAccountId,
            'description' => 'PI zero total cash',
            'lines' => [
                [
                    'inventory_item_id' => $itemId,
                    'qty' => 1,
                    'unit_price' => 10000,
                    'discount_amount' => 10000,
                    'warehouse_id' => $warehouseId,
                ],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'total_amount' => 0, 'status' => 'draft']);

        $this->assertZeroTotalPostGuard($invoiceId, '/purchase-invoices/'.$invoiceId.'/post', 'purchase_invoice');

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'status' => 'draft']);
    }

    public function test_sales_invoice_with_hundred_percent_discount_cannot_post(): void
    {
        $this->actingAsSalesUser();

        $revenueId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');

        $resp = $this->post('/sales-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'description' => 'SI zero total',
            'lines' => [
                [
                    'account_id' => $revenueId,
                    'description' => 'Free sale',
                    'qty' => 1,
                    'unit_price' => 10000,
                    'discount_amount' => 10000,
                ],
            ],
        ]);
        $resp->assertRedirect();
        $invoiceId = (int) basename(parse_url((string) $resp->headers->get('Location'), PHP_URL_PATH));

        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'total_amount' => 0, 'status' => 'draft']);

        $this->assertZeroTotalPostGuard($invoiceId, '/sales-invoices/'.$invoiceId.'/post', 'sales_invoice');

        $this->assertDatabaseHas('sales_invoices', ['id' => $invoiceId, 'status' => 'draft']);
    }
}
