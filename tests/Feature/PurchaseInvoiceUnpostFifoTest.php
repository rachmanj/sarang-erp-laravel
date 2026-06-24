<?php

namespace Tests\Feature;

use App\Models\Accounting\PurchaseInvoice;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\PurchaseInvoiceUnpostService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseInvoiceUnpostFifoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['ap.invoices.view', 'ap.invoices.create', 'ap.invoices.post', 'ap.invoices.delete']);
        $this->actingAs($user);
    }

    private function createFifoItem(): InventoryItem
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->where('is_active', true)->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        return InventoryItem::query()->create([
            'code' => 'TEST-UNPOST-'.uniqid(),
            'name' => 'Test Unpost Item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 90000,
            'selling_price' => 120000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);
    }

    private function postDirectPurchaseInvoice(InventoryItem $item, float $qty, float $unitPrice): PurchaseInvoice
    {
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $vendorId = (int) DB::table('business_partners')->where('partner_type', 'supplier')->orderBy('id')->value('id');
        $cashAccountId = (int) DB::table('accounts')->where('code', '1.1.1.01')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');

        $response = $this->post('/purchase-invoices', [
            'date' => now()->toDateString(),
            'business_partner_id' => $vendorId,
            'company_entity_id' => $entityId,
            'payment_method' => 'cash',
            'cash_account_id' => $cashAccountId,
            'description' => 'FIFO unpost test',
            'lines' => [
                [
                    'inventory_item_id' => $item->id,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'warehouse_id' => $warehouseId,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $invoice = PurchaseInvoice::query()->latest('id')->firstOrFail();
        $this->post('/purchase-invoices/'.$invoice->id.'/post')->assertRedirect();

        return $invoice->fresh(['lines']);
    }

    public function test_unposting_second_fifo_purchase_does_not_create_broken_adjustments(): void
    {
        $item = $this->createFifoItem();

        $this->postDirectPurchaseInvoice($item, 4, 90000);
        $secondInvoice = $this->postDirectPurchaseInvoice($item, 16, 93000);

        app(PurchaseInvoiceUnpostService::class)->unpost($secondInvoice);

        $item->refresh();
        $this->assertSame(4, (int) $item->current_stock);

        app(InventoryService::class)->calculateUnitCost($item);

        $this->assertSame(0, InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $secondInvoice->id)
            ->where('transaction_type', 'adjustment')
            ->count());
    }

    public function test_unpost_returns_error_when_stock_was_partially_consumed(): void
    {
        $item = $this->createFifoItem();
        $invoice = $this->postDirectPurchaseInvoice($item, 10, 90000);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'transaction_type' => 'sale',
            'quantity' => -1,
            'unit_cost' => 90000,
            'total_cost' => -90000,
            'reference_type' => 'delivery_order_line',
            'reference_id' => 999001,
            'transaction_date' => now()->toDateString(),
            'warehouse_id' => Warehouse::query()->value('id'),
            'created_by' => null,
        ]);

        $response = $this->post('/purchase-invoices/'.$invoice->id.'/unpost');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Cannot unpost invoice', session('error'));
        $this->assertStringContainsString('9', session('error'));
        $invoice->refresh();
        $this->assertSame('posted', $invoice->status);
    }

    public function test_repair_restores_inventory_after_failed_legacy_reversal(): void
    {
        $item = $this->createFifoItem();
        $invoice = $this->postDirectPurchaseInvoice($item, 16, 93000);

        $purchase = InventoryTransaction::query()
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->where('transaction_type', 'purchase')
            ->firstOrFail();

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'transaction_type' => 'adjustment',
            'quantity' => -16,
            'unit_cost' => $purchase->unit_cost,
            'total_cost' => -((float) $purchase->total_cost),
            'reference_type' => 'purchase_invoice',
            'reference_id' => $invoice->id,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Reversal of purchase invoice #'.$invoice->invoice_no,
            'warehouse_id' => $purchase->warehouse_id,
            'created_by' => null,
        ]);

        $purchase->delete();

        $this->expectException(\Exception::class);
        app(InventoryService::class)->calculateUnitCost($item->fresh());

        $messages = app(PurchaseInvoiceUnpostService::class)->repairBrokenDirectPurchaseReversal($invoice->fresh(['lines.inventoryItem', 'lines.warehouse']));

        $this->assertNotEmpty($messages);
        $item->refresh();
        $this->assertSame(16, (int) $item->current_stock);
        app(InventoryService::class)->calculateUnitCost($item);
    }
}
