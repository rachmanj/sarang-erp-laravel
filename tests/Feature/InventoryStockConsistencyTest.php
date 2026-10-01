<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DeliveryService;
use App\Services\InventoryService;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryStockConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->actor = User::factory()->create();
        $this->actingAs($this->actor);
    }

    private function ledgerStockSum(int $itemId): float
    {
        return (float) DB::table('inventory_transactions')
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    private function warehouseStockSum(int $itemId): float
    {
        return (float) DB::table('inventory_warehouse_stock')
            ->where('item_id', $itemId)
            ->sum('quantity_on_hand');
    }

    private function assertStockConsistent(int $itemId): void
    {
        $ledger = $this->ledgerStockSum($itemId);
        $warehouse = $this->warehouseStockSum($itemId);

        $this->assertEqualsWithDelta(
            $ledger,
            $warehouse,
            0.0001,
            sprintf(
                'Stock inconsistent for item %d: SUM(inventory_transactions.quantity)=%s, SUM(inventory_warehouse_stock.quantity_on_hand)=%s, diff=%s',
                $itemId,
                $ledger,
                $warehouse,
                $ledger - $warehouse
            )
        );
    }

    /**
     * @return array{item: InventoryItem, warehouse: Warehouse}
     */
    private function createInventoryItemWithWarehouse(): array
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        $item = InventoryItem::query()->create([
            'code' => 'T-INV-STK-'.uniqid(),
            'name' => 'Stock Consistency Test Item',
            'category_id' => $category->id,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'pcs',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 1000,
            'selling_price' => 1200,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);

        return ['item' => $item, 'warehouse' => $warehouse];
    }

    /**
     * @return array{deliveryOrder: DeliveryOrder, item: InventoryItem, warehouse: Warehouse}
     */
    private function createPendingDeliveryOrder(InventoryItem $item, Warehouse $warehouse, int $orderedQty): array
    {
        $bpId = (int) DB::table('business_partners')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

        $so = SalesOrder::query()->create([
            'order_no' => 'T-SO-STK-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'processing',
            'approval_status' => 'approved',
            'total_amount' => 100000,
            'created_by' => $this->actor->id,
        ]);

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $revenueAccountId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'qty' => $orderedQty,
            'delivered_qty' => 0,
            'pending_qty' => $orderedQty,
            'unit_price' => 10000,
            'amount' => $orderedQty * 10000,
        ]);

        $deliveryOrder = DeliveryOrder::query()->create([
            'do_number' => 'T-DO-STK-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Test delivery address',
            'planned_delivery_date' => now()->toDateString(),
            'status' => 'draft',
            'approval_status' => 'pending',
            'created_by' => $this->actor->id,
        ]);

        DeliveryOrderLine::query()->create([
            'delivery_order_id' => $deliveryOrder->id,
            'sales_order_line_id' => $soLine->id,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => $orderedQty,
            'picked_qty' => 0,
            'unit_price' => 10000,
            'amount' => $orderedQty * 10000,
            'status' => 'pending',
        ]);

        return [
            'deliveryOrder' => $deliveryOrder,
            'item' => $item,
            'warehouse' => $warehouse,
        ];
    }

    public function test_case_a_purchase_via_inventory_service_keeps_ledger_and_warehouse_stock_aligned(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();
        $inventoryService = app(InventoryService::class);

        $inventoryService->processPurchaseTransaction(
            $item->id,
            25,
            1000.0,
            'purchase_invoice',
            1,
            'Test purchase for stock invariant',
            $warehouse->id
        );

        $this->assertSame(25.0, $this->ledgerStockSum($item->id));
        $this->assertSame(25.0, $this->warehouseStockSum($item->id));
        $this->assertStockConsistent($item->id);
    }

    public function test_case_b_delivery_order_approval_within_stock_keeps_ledger_and_warehouse_aligned(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();
        $inventoryService = app(InventoryService::class);
        $deliveryService = app(DeliveryService::class);

        $inventoryService->processPurchaseTransaction(
            $item->id,
            10,
            1000.0,
            'purchase_invoice',
            2,
            'Stock for DO approval',
            $warehouse->id
        );

        ['deliveryOrder' => $deliveryOrder] = $this->createPendingDeliveryOrder($item, $warehouse, 3);

        $deliveryService->approveDeliveryOrder($deliveryOrder->id, $this->actor->id, 'Approve within stock');

        $this->assertSame('approved', $deliveryOrder->fresh()->approval_status);
        $this->assertSame(7.0, $this->ledgerStockSum($item->id));
        $this->assertSame(7.0, $this->warehouseStockSum($item->id));
        $this->assertStockConsistent($item->id);
    }

    /**
     * Sengaja mereproduksi skenario produksi (beli 2, DO 8): invarian buku besar = stok gudang dan stok tidak negatif.
     */
    public function test_case_c_delivery_order_exceeding_stock_must_not_break_stock_invariants(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();
        $inventoryService = app(InventoryService::class);
        $deliveryService = app(DeliveryService::class);

        $inventoryService->processPurchaseTransaction(
            $item->id,
            2,
            1000.0,
            'purchase_invoice',
            3,
            'Purchase 2 units before oversize DO',
            $warehouse->id
        );

        ['deliveryOrder' => $deliveryOrder] = $this->createPendingDeliveryOrder($item, $warehouse, 8);

        $saleCountBefore = InventoryTransaction::query()
            ->where('item_id', $item->id)
            ->where('transaction_type', 'sale')
            ->count();

        $exceptionMessage = null;

        try {
            $deliveryService->approveDeliveryOrder($deliveryOrder->id, $this->actor->id, 'Oversize DO');
            $this->fail('Expected Insufficient stock exception when approving DO for 8 units with ledger stock 2.');
        } catch (\Exception $e) {
            $exceptionMessage = $e->getMessage();
            $this->assertStringContainsString('Insufficient stock', $exceptionMessage);
        }

        $saleCountAfter = InventoryTransaction::query()
            ->where('item_id', $item->id)
            ->where('transaction_type', 'sale')
            ->count();

        $this->assertSame($saleCountBefore, $saleCountAfter, 'No new sale transaction should be recorded when approval fails.');
        $this->assertSame('pending', $deliveryOrder->fresh()->approval_status);

        $ledger = $this->ledgerStockSum($item->id);
        $warehouseQty = $this->warehouseStockSum($item->id);

        $this->assertGreaterThanOrEqual(0, $ledger, 'Ledger stock must not be negative.');
        $this->assertGreaterThanOrEqual(0, $warehouseQty, 'Warehouse stock must not be negative.');
        $this->assertStockConsistent($item->id);
    }

    public function test_case_d_stock_adjustment_via_inventory_service_should_keep_ledger_and_warehouse_aligned(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();
        $inventoryService = app(InventoryService::class);

        $inventoryService->processPurchaseTransaction(
            $item->id,
            5,
            1000.0,
            'purchase_invoice',
            4,
            'Baseline stock before adjustment',
            $warehouse->id
        );

        $inventoryService->processAdjustmentTransaction(
            $item->id,
            3,
            1000.0,
            'Positive adjustment test'
        );

        $this->assertStockConsistent($item->id);
    }

    public function test_case_e_stock_transfer_via_inventory_service_should_keep_ledger_and_warehouse_aligned(): void
    {
        ['item' => $fromItem, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();
        ['item' => $toItem] = $this->createInventoryItemWithWarehouse();
        $inventoryService = app(InventoryService::class);

        $inventoryService->processPurchaseTransaction(
            $fromItem->id,
            10,
            1000.0,
            'purchase_invoice',
            5,
            'Stock for transfer source',
            $warehouse->id
        );

        $inventoryService->processTransferTransaction(
            $fromItem->id,
            $toItem->id,
            4,
            1000.0,
            'Transfer invariant test'
        );

        $this->assertStockConsistent($fromItem->id);
        $this->assertStockConsistent($toItem->id);
    }

    public function test_store_with_initial_stock_via_http_keeps_ledger_and_warehouse_aligned(): void
    {
        $this->seed(UnitOfMeasureSeeder::class);

        $user = User::query()->where('username', 'superadmin')->firstOrFail();
        $categoryId = (int) DB::table('product_categories')->value('id');
        $warehouseId = (int) Warehouse::query()->value('id');
        $baseUnitId = UnitOfMeasure::query()->where('is_active', true)->value('id');

        $code = 'T-INIT-STK-'.uniqid();

        $response = $this->actingAs($user)->post(route('inventory.store'), [
            'code' => $code,
            'name' => 'Initial Stock HTTP Test Item',
            'category_id' => $categoryId,
            'default_warehouse_id' => $warehouseId,
            'base_unit_id' => $baseUnitId,
            'purchase_price' => 5000,
            'selling_price' => 6000,
            'item_type' => 'item',
            'valuation_method' => 'fifo',
            'is_active' => 'on',
            'initial_stock' => 15,
        ]);

        $item = InventoryItem::query()->where('code', $code)->firstOrFail();
        $response->assertRedirect(route('inventory.show', $item->id));

        $this->assertSame(15.0, $this->ledgerStockSum($item->id));
        $this->assertStockConsistent($item->id);
    }

    public function test_adjust_stock_without_warehouse_id_uses_default_warehouse_and_stays_consistent(): void
    {
        $user = User::query()->where('username', 'superadmin')->firstOrFail();
        ['item' => $item, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();

        $response = $this->actingAs($user)->post(route('inventory.adjust-stock', $item->id), [
            'adjustment_type' => 'increase',
            'quantity' => 7,
            'unit_cost' => 1000,
            'notes' => 'Adjust without explicit warehouse',
        ]);

        $response->assertRedirect();
        $this->assertSame(7.0, $this->ledgerStockSum($item->id));
        $this->assertSame(7.0, (float) DB::table('inventory_warehouse_stock')
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('quantity_on_hand'));
        $this->assertStockConsistent($item->id);
    }

    public function test_remove_purchase_inventory_transaction_reduces_ledger_and_warehouse_stock(): void
    {
        ['item' => $item, 'warehouse' => $warehouse] = $this->createInventoryItemWithWarehouse();
        $inventoryService = app(InventoryService::class);

        $transaction = $inventoryService->processPurchaseTransaction(
            $item->id,
            12,
            1000.0,
            'purchase_invoice',
            99,
            'Purchase to remove',
            $warehouse->id
        );

        $this->assertSame(12.0, $this->ledgerStockSum($item->id));
        $this->assertStockConsistent($item->id);

        $inventoryService->removePurchaseInventoryTransaction($transaction);

        $this->assertSame(0.0, $this->ledgerStockSum($item->id));
        $this->assertSame(0.0, $this->warehouseStockSum($item->id));
        $this->assertStockConsistent($item->id);
    }
}
