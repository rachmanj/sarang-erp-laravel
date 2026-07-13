<?php

namespace Tests\Feature;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerAddress;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryOrderAddressSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->firstOrFail();
    }

    public function test_addresses_endpoint_returns_partner_addresses(): void
    {
        $partner = BusinessPartner::query()->customers()->firstOrFail();

        $address = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'shipping',
            'address_line_1' => 'Jl. Endpoint Test 99',
            'city' => 'Jakarta',
            'phone' => '021-8887777',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('business_partners.addresses', $partner));

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $address->id,
                'address_type' => 'shipping',
                'phone' => '021-8887777',
            ]);
    }

    public function test_delivery_order_update_saves_selected_business_partner_address(): void
    {
        ['deliveryOrder' => $deliveryOrder, 'line' => $line] = $this->createDraftDeliveryOrder();

        $address = BusinessPartnerAddress::create([
            'business_partner_id' => $deliveryOrder->business_partner_id,
            'address_type' => 'warehouse',
            'address_line_1' => 'Warehouse DO Selection Street',
            'city' => 'Surabaya',
            'phone' => '031-1234567',
            'is_primary' => false,
        ]);

        $response = $this->actingAs($this->user)->patch(route('delivery-orders.update', $deliveryOrder), [
            'business_partner_address_id' => $address->id,
            'delivery_address' => $address->full_address,
            'delivery_contact_person' => 'Warehouse PIC',
            'delivery_phone' => $address->phone,
            'planned_delivery_date' => $deliveryOrder->planned_delivery_date->format('Y-m-d'),
            'delivery_method' => 'own_fleet',
            'logistics_cost' => 0,
            'lines' => [
                [
                    'id' => $line->id,
                    'ordered_qty' => 2,
                ],
            ],
        ]);

        $deliveryOrder->refresh();

        $response->assertRedirect(route('delivery-orders.show', $deliveryOrder));
        $this->assertSame($address->id, $deliveryOrder->business_partner_address_id);
        $this->assertSame($address->full_address, $deliveryOrder->delivery_address);
        $this->assertSame('031-1234567', $deliveryOrder->delivery_phone);
    }

    /**
     * @return array{deliveryOrder: DeliveryOrder, line: DeliveryOrderLine}
     */
    private function createDraftDeliveryOrder(): array
    {
        $bpId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouse = Warehouse::query()->firstOrFail();
        $category = ProductCategory::query()->firstOrFail();

        $item = InventoryItem::query()->create([
            'code' => 'T-DO-ADDR-'.uniqid(),
            'name' => 'DO Address Test Item',
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
            'order_no' => 'T-SO-ADDR-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'processing',
            'approval_status' => 'approved',
            'order_type' => 'item',
            'total_amount' => 20000,
            'created_by' => $this->user->id,
        ]);

        $revenueAccountId = (int) DB::table('accounts')->where('code', '4.1.1.01')->value('id');

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $revenueAccountId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'qty' => 4,
            'delivered_qty' => 0,
            'pending_qty' => 4,
            'unit_price' => 5000,
            'amount' => 20000,
        ]);

        $deliveryOrder = DeliveryOrder::query()->create([
            'do_number' => 'T-DO-ADDR-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $bpId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Old delivery address',
            'planned_delivery_date' => now()->toDateString(),
            'delivery_method' => 'own_fleet',
            'status' => 'draft',
            'approval_status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $line = DeliveryOrderLine::query()->create([
            'delivery_order_id' => $deliveryOrder->id,
            'sales_order_line_id' => $soLine->id,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => 2,
            'picked_qty' => 0,
            'unit_price' => 5000,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        return ['deliveryOrder' => $deliveryOrder, 'line' => $line];
    }
}
