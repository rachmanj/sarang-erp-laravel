<?php

namespace Tests\Feature;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesOrderCreateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function userWithCreatePermission(): User
    {
        Permission::findOrCreate('sales-orders.create');
        $user = User::factory()->create();
        $user->givePermissionTo('sales-orders.create');

        return $user;
    }

    /**
     * @return array{salesOrder: SalesOrder, entityId: int}
     */
    private function createSalesOrderWithLine(int $companyEntityId): array
    {
        $bpId = (int) DB::table('business_partners')->value('id');
        $userId = (int) DB::table('users')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');
        $warehouseId = (int) DB::table('warehouses')->value('id');
        $revenueAccountId = (int) DB::table('accounts')->where('is_postable', 1)->value('id');

        $this->assertGreaterThan(0, $bpId);
        $this->assertGreaterThan(0, $companyEntityId);
        $this->assertGreaterThan(0, $revenueAccountId);

        $salesOrder = SalesOrder::query()->create([
            'order_no' => 'T-SO-INV-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $bpId,
            'company_entity_id' => $companyEntityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouseId,
            'status' => 'processing',
            'total_amount' => 50000,
            'created_by' => $userId,
        ]);

        SalesOrderLine::query()->create([
            'order_id' => $salesOrder->id,
            'account_id' => $revenueAccountId,
            'item_code' => 'T-LINE',
            'item_name' => 'Test line item',
            'description' => 'Test line item',
            'qty' => 1,
            'delivered_qty' => 0,
            'pending_qty' => 1,
            'unit_price' => 50000,
            'amount' => 50000,
        ]);

        return ['salesOrder' => $salesOrder, 'entityId' => $companyEntityId];
    }

    public function test_create_invoice_from_sales_order_renders_with_company_entities(): void
    {
        $entityId = (int) DB::table('company_entities')->where('code', '72')->value('id');
        $entityName = (string) DB::table('company_entities')->where('id', $entityId)->value('name');

        ['salesOrder' => $salesOrder] = $this->createSalesOrderWithLine($entityId);

        $response = $this->actingAs($this->userWithCreatePermission())
            ->get(route('sales-orders.create-invoice', $salesOrder->id));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertIsString($content);

        $this->assertStringContainsString('PT Cahaya Sarange Jaya', $content);
        $this->assertStringContainsString('CV Cahaya Saranghae', $content);
        $this->assertStringContainsString($entityName, $content);

        $this->assertMatchesRegularExpression(
            '/value="'.$entityId.'"[^>]*\sselected/',
            $content,
            'Sales Order company entity option should be pre-selected.'
        );

        $this->assertStringNotContainsString('Undefined variable', $content);
    }
}
