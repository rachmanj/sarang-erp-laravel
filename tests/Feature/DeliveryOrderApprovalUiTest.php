<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeliveryOrderApprovalUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function ensurePermission(string $name): void
    {
        Permission::findOrCreate($name);
    }

    /**
     * @return array{deliveryOrder: DeliveryOrder, item: InventoryItem}
     */
    private function createPendingDeliveryOrder(int $orderedQty = 4): array
    {
        $bpId = (int) DB::table('business_partners')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $userId = (int) DB::table('users')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouse = Warehouse::query()->firstOrFail();

        $category = ProductCategory::query()->firstOrFail();

        $item = InventoryItem::query()->create([
            'code' => 'T-ITEM-'.uniqid(),
            'name' => 'Test Stock Item',
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

        $so = SalesOrder::query()->create([
            'order_no' => 'T-SO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'processing',
            'approval_status' => 'approved',
            'total_amount' => 100000,
            'created_by' => $userId,
        ]);

        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

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
            'do_number' => 'T-DO-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Test delivery address',
            'planned_delivery_date' => now()->toDateString(),
            'status' => 'draft',
            'approval_status' => 'pending',
            'created_by' => $userId,
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

        return ['deliveryOrder' => $deliveryOrder, 'item' => $item];
    }

    public function test_approve_button_disabled_when_user_lacks_permission(): void
    {
        $this->ensurePermission('sales-orders.view');

        ['deliveryOrder' => $deliveryOrder] = $this->createPendingDeliveryOrder();

        $user = User::factory()->create();
        $user->givePermissionTo('sales-orders.view');
        $this->actingAs($user);

        $response = $this->get(route('delivery-orders.show', $deliveryOrder));

        $response->assertOk();
        $response->assertSee('Approval unavailable:', false);
        $response->assertSee('You do not have permission to approve delivery orders.', false);
        $response->assertSee('disabled', false);
    }

    public function test_approve_button_disabled_when_stock_is_insufficient(): void
    {
        $this->ensurePermission('sales-orders.view');
        $this->ensurePermission('sales-orders.approve');

        ['deliveryOrder' => $deliveryOrder, 'item' => $item] = $this->createPendingDeliveryOrder(4);

        $user = User::factory()->create();
        $user->givePermissionTo(['sales-orders.view', 'sales-orders.approve']);
        $this->actingAs($user);

        $response = $this->get(route('delivery-orders.show', $deliveryOrder));

        $response->assertOk();
        $response->assertSee('Insufficient stock for '.$item->name, false);
        $response->assertSee('Available: 0, Required: 4', false);
        $response->assertSee('disabled', false);
    }

    public function test_approve_button_enabled_when_user_has_permission_and_stock_is_sufficient(): void
    {
        $this->ensurePermission('sales-orders.view');
        $this->ensurePermission('sales-orders.approve');

        ['deliveryOrder' => $deliveryOrder, 'item' => $item] = $this->createPendingDeliveryOrder(4);

        $user = User::factory()->create();
        $user->givePermissionTo(['sales-orders.view', 'sales-orders.approve']);
        $this->actingAs($user);

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $deliveryOrder->warehouse_id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 1000,
            'total_cost' => 10000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $response = $this->get(route('delivery-orders.show', $deliveryOrder));

        $response->assertOk();
        $response->assertDontSee('Approval unavailable:', false);
        $response->assertSee('title="Approve this delivery order"', false);
        $response->assertDontSee('Insufficient stock for '.$item->name, false);
    }
}
