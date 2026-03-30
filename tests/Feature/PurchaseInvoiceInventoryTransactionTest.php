<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseInvoiceInventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post']);
        $this->actingAs($user);
    }

    /**
     * @return array{0: int, 1: int} [itemId, warehouseId]
     */
    private function createTestInventoryItem(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'TEST-PI-'.uniqid(),
            'name' => 'Test PI Item',
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

    public function test_post_sets_purchase_invoice_line_id_and_second_post_does_not_duplicate(): void
    {
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        [$itemId, $warehouseId] = $this->createTestInventoryItem();
        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');

        $maxIdBefore = (int) DB::table('purchase_invoices')->max('id');

        $resp = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'payment_method' => 'cash',
            'cash_account_id' => $cashAccountId,
            'description' => 'Test PI inventory line id',
            'lines' => [
                [
                    'inventory_item_id' => $itemId,
                    'qty' => 1,
                    'unit_price' => 100000,
                    'warehouse_id' => $warehouseId,
                ],
            ],
        ]);
        $resp->assertRedirect();
        $resp->assertSessionHasNoErrors();

        $maxIdAfter = (int) DB::table('purchase_invoices')->max('id');
        $this->assertGreaterThan($maxIdBefore, $maxIdAfter);
        $invoiceId = $maxIdAfter;

        $lineId = (int) DB::table('purchase_invoice_lines')->where('invoice_id', $invoiceId)->value('id');
        $this->assertGreaterThan(0, $lineId);

        $this->post('/purchase-invoices/'.$invoiceId.'/post')->assertRedirect();

        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoiceId, 'status' => 'posted']);

        $txCount = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoiceId)
            ->where('transaction_type', 'purchase')
            ->count();
        $this->assertSame(1, $txCount);

        $this->assertDatabaseHas('inventory_transactions', [
            'purchase_invoice_line_id' => $lineId,
            'reference_type' => 'purchase_invoice',
            'reference_id' => $invoiceId,
        ]);

        $this->post('/purchase-invoices/'.$invoiceId.'/post')->assertRedirect();

        $txCountAfterSecondPost = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoiceId)
            ->where('transaction_type', 'purchase')
            ->count();

        $this->assertSame(1, $txCountAfterSecondPost);
    }
}
