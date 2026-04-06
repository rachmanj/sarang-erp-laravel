<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseInvoiceDateValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @param  array<int, string>  $extraPermissions
     */
    private function actingAsPiCreator(array $extraPermissions = []): User
    {
        $perms = array_merge(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post'], $extraPermissions);
        $user = User::factory()->create();
        $user->givePermissionTo($perms);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array{entityId: int, vendorId: int, cashAccountId: int, itemId: int, warehouseId: int}
     */
    private function createInventoryContext(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'TEST-PI-DATE-'.uniqid(),
            'name' => 'Test PI Date Item',
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

        return [
            'entityId' => (int) DB::table('company_entities')->where('code', '71')->value('id'),
            'vendorId' => (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id'),
            'cashAccountId' => (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id'),
            'itemId' => (int) $item->id,
            'warehouseId' => (int) $warehouse->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function buildStorePayload(array $ctx, array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-04-01',
            'business_partner_id' => $ctx['vendorId'],
            'company_entity_id' => $ctx['entityId'],
            'payment_method' => 'cash',
            'cash_account_id' => $ctx['cashAccountId'],
            'description' => 'Date validation test',
            'lines' => [
                [
                    'inventory_item_id' => $ctx['itemId'],
                    'qty' => 1,
                    'unit_price' => 100000,
                    'warehouse_id' => $ctx['warehouseId'],
                ],
            ],
        ], $overrides);
    }

    public function test_store_rejects_future_invoice_date_without_exception(): void
    {
        $this->actingAsPiCreator();
        $ctx = $this->createInventoryContext();

        $response = $this->from('/purchase-invoices/create')->post('/purchase-invoices', $this->buildStorePayload($ctx, [
            'date' => '2026-09-01',
        ]));

        $response->assertSessionHasErrors('date');
    }

    public function test_store_allows_future_date_when_opening_balance(): void
    {
        $this->actingAsPiCreator();
        $ctx = $this->createInventoryContext();

        $response = $this->post('/purchase-invoices', $this->buildStorePayload($ctx, [
            'date' => '2026-09-01',
            'is_opening_balance' => '1',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_store_allows_future_date_with_future_date_permission(): void
    {
        $this->actingAsPiCreator(['ap.invoices.future_date']);
        $ctx = $this->createInventoryContext();

        $response = $this->post('/purchase-invoices', $this->buildStorePayload($ctx, [
            'date' => '2026-09-01',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_update_draft_rejects_future_invoice_date_without_exception(): void
    {
        $this->actingAsPiCreator();
        $ctx = $this->createInventoryContext();

        $create = $this->post('/purchase-invoices', $this->buildStorePayload($ctx));
        $create->assertRedirect();
        $create->assertSessionHasNoErrors();

        $invoiceId = (int) DB::table('purchase_invoices')->max('id');

        $payload = $this->buildStorePayload($ctx, ['date' => '2026-09-01']);
        $response = $this->from('/purchase-invoices/'.$invoiceId.'/edit')->put('/purchase-invoices/'.$invoiceId, $payload);

        $response->assertSessionHasErrors('date');
    }

    public function test_update_draft_allows_future_date_with_future_date_permission(): void
    {
        $this->actingAsPiCreator(['ap.invoices.future_date']);
        $ctx = $this->createInventoryContext();

        $create = $this->post('/purchase-invoices', $this->buildStorePayload($ctx));
        $create->assertRedirect();
        $invoiceId = (int) DB::table('purchase_invoices')->max('id');

        $payload = $this->buildStorePayload($ctx, ['date' => '2026-09-01']);
        $response = $this->put('/purchase-invoices/'.$invoiceId, $payload);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }
}
