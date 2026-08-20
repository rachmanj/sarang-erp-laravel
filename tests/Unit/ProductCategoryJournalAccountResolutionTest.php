<?php

namespace Tests\Unit;

use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesInvoiceLine;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalBuilders\DeliveryOrderJournalBuilder;
use App\Services\Accounting\JournalBuilders\DirectSalesInvoiceJournalBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductCategoryJournalAccountResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    private function accountId(string $code): int
    {
        return (int) DB::table('accounts')->where('code', $code)->value('id');
    }

    private function createDeliveredOrderForItem(InventoryItem $item): DeliveryOrder
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 5000,
            'total_cost' => 50000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $userId,
        ]);

        $toolsRevenueId = $this->accountId('4.1.1.11');

        $so = SalesOrder::query()->create([
            'order_no' => 'T-CAT-SO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
            'total_amount' => 20000,
            'created_by' => $userId,
        ]);

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $toolsRevenueId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'qty' => 2,
            'delivered_qty' => 0,
            'pending_qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
        ]);

        $do = DeliveryOrder::query()->create([
            'do_number' => 'T-CAT-DO-'.uniqid(),
            'sales_order_id' => $so->id,
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'warehouse_id' => $warehouse->id,
            'delivery_address' => 'Test address',
            'planned_delivery_date' => now()->toDateString(),
            'actual_delivery_date' => now()->toDateString(),
            'status' => 'delivered',
            'approval_status' => 'approved',
            'created_by' => $userId,
        ]);

        DeliveryOrderLine::query()->create([
            'delivery_order_id' => $do->id,
            'sales_order_line_id' => $soLine->id,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => 2,
            'delivered_qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
            'status' => 'delivered',
        ]);

        return $do;
    }

    private function createInventoryItem(?int $categoryId): InventoryItem
    {
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');

        return InventoryItem::query()->create([
            'code' => 'T-CAT-ITEM-'.uniqid(),
            'name' => 'Category resolution test item',
            'category_id' => $categoryId,
            'default_warehouse_id' => $warehouse->id,
            'unit_of_measure' => 'EA',
            'purchase_currency_id' => $currencyId,
            'selling_currency_id' => $currencyId,
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'valuation_method' => 'fifo',
            'item_type' => 'item',
            'is_active' => true,
        ]);
    }

    public function test_delivery_order_journal_resolves_tools_category_revenue_and_cogs_accounts(): void
    {
        $toolsCategory = ProductCategory::query()->where('code', 'TOOLS')->firstOrFail();
        $item = $this->createInventoryItem($toolsCategory->id);
        $do = $this->createDeliveredOrderForItem($item);

        $draft = app(DeliveryOrderJournalBuilder::class)->buildRevenueRecognition($do->fresh());

        $toolsRevenueId = $this->accountId('4.1.1.11');
        $toolsCogsId = $this->accountId('5.1.11');

        $revenueLine = collect($draft->lines)->first(
            fn (array $line) => $line['credit'] > 0 && str_contains($line['memo'], 'Revenue from DO')
        );
        $cogsLine = collect($draft->lines)->first(
            fn (array $line) => $line['debit'] > 0 && str_contains($line['memo'], 'COGS for DO')
        );

        $this->assertNotNull($revenueLine);
        $this->assertNotNull($cogsLine);
        $this->assertSame($toolsRevenueId, $revenueLine['account_id']);
        $this->assertSame($toolsCogsId, $cogsLine['account_id']);
    }

    public function test_delivery_order_journal_credits_tools_category_inventory_account_on_cogs_release(): void
    {
        $toolsCategory = ProductCategory::query()->where('code', 'TOOLS')->firstOrFail();
        $item = $this->createInventoryItem($toolsCategory->id);
        $do = $this->createDeliveredOrderForItem($item);

        $draft = app(DeliveryOrderJournalBuilder::class)->buildRevenueRecognition($do->fresh());

        $toolsInventoryId = $this->accountId('1.1.3.01.11');

        $inventoryReleaseLine = collect($draft->lines)->first(
            fn (array $line) => $line['credit'] > 0 && str_contains($line['memo'], 'Release inventory')
        );

        $this->assertNotNull($inventoryReleaseLine);
        $this->assertSame($toolsInventoryId, $inventoryReleaseLine['account_id']);
    }

    public function test_delivery_order_journal_falls_back_to_parent_inventory_account_when_item_has_no_category(): void
    {
        $item = $this->createInventoryItem(null);
        $do = $this->createDeliveredOrderForItem($item);

        $draft = app(DeliveryOrderJournalBuilder::class)->buildRevenueRecognition($do->fresh());

        $parentInventoryId = $this->accountId('1.1.3.01');

        $inventoryReleaseLine = collect($draft->lines)->first(
            fn (array $line) => $line['credit'] > 0 && str_contains($line['memo'], 'Release inventory')
        );

        $this->assertNotNull($inventoryReleaseLine);
        $this->assertSame($parentInventoryId, $inventoryReleaseLine['account_id']);
    }

    public function test_delivery_order_journal_falls_back_to_stationery_when_item_has_no_category(): void
    {
        $item = $this->createInventoryItem(null);
        $do = $this->createDeliveredOrderForItem($item);

        $draft = app(DeliveryOrderJournalBuilder::class)->buildRevenueRecognition($do->fresh());

        $stationeryRevenueId = $this->accountId('4.1.1.01');
        $stationeryCogsId = $this->accountId('5.1.01');

        $revenueLine = collect($draft->lines)->first(
            fn (array $line) => $line['credit'] > 0 && str_contains($line['memo'], 'Revenue from DO')
        );
        $cogsLine = collect($draft->lines)->first(
            fn (array $line) => $line['debit'] > 0 && str_contains($line['memo'], 'COGS for DO')
        );

        $this->assertNotNull($revenueLine);
        $this->assertNotNull($cogsLine);
        $this->assertSame($stationeryRevenueId, $revenueLine['account_id']);
        $this->assertSame($stationeryCogsId, $cogsLine['account_id']);
    }

    public function test_direct_sales_invoice_journal_resolves_tools_category_cogs_account(): void
    {
        $toolsCategory = ProductCategory::query()->where('code', 'TOOLS')->firstOrFail();
        $item = $this->createInventoryItem($toolsCategory->id);
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');
        $toolsRevenueId = $this->accountId('4.1.1.11');
        $toolsCogsId = $this->accountId('5.1.11');

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 5000,
            'total_cost' => 50000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $userId,
        ]);

        $invoice = SalesInvoice::query()->create([
            'invoice_no' => 'T-CAT-DSI-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'is_direct_sale' => true,
            'payment_method' => 'credit',
            'status' => 'draft',
            'total_amount' => 20000,
            'created_by' => $userId,
        ]);

        SalesInvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'account_id' => $toolsRevenueId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'description' => $item->name,
            'qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
            'wtax_rate' => 0,
        ]);

        $draft = app(DirectSalesInvoiceJournalBuilder::class)->build($invoice->fresh());

        $cogsLine = collect($draft->lines)->first(
            fn (array $line) => $line['debit'] > 0 && str_contains($line['memo'], 'COGS - Direct sale')
        );

        $this->assertNotNull($cogsLine);
        $this->assertSame($toolsCogsId, $cogsLine['account_id']);

        $toolsInventoryId = $this->accountId('1.1.3.01.11');
        $inventoryReleaseLine = collect($draft->lines)->first(
            fn (array $line) => $line['credit'] > 0 && str_contains($line['memo'], 'Release inventory')
        );

        $this->assertNotNull($inventoryReleaseLine);
        $this->assertSame($toolsInventoryId, $inventoryReleaseLine['account_id']);
    }

    public function test_direct_sales_invoice_journal_falls_back_to_stationery_cogs_when_item_has_no_category(): void
    {
        $item = $this->createInventoryItem(null);
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');
        $stationeryRevenueId = $this->accountId('4.1.1.01');
        $stationeryCogsId = $this->accountId('5.1.01');

        InventoryTransaction::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'transaction_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 5000,
            'total_cost' => 50000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $userId,
        ]);

        $invoice = SalesInvoice::query()->create([
            'invoice_no' => 'T-CAT-DSI-FB-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'is_direct_sale' => true,
            'payment_method' => 'credit',
            'status' => 'draft',
            'total_amount' => 20000,
            'created_by' => $userId,
        ]);

        SalesInvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'account_id' => $stationeryRevenueId,
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'description' => $item->name,
            'qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
            'wtax_rate' => 0,
        ]);

        $draft = app(DirectSalesInvoiceJournalBuilder::class)->build($invoice->fresh());

        $cogsLine = collect($draft->lines)->first(
            fn (array $line) => $line['debit'] > 0 && str_contains($line['memo'], 'COGS - Direct sale')
        );

        $this->assertNotNull($cogsLine);
        $this->assertSame($stationeryCogsId, $cogsLine['account_id']);

        $parentInventoryId = $this->accountId('1.1.3.01');
        $inventoryReleaseLine = collect($draft->lines)->first(
            fn (array $line) => $line['credit'] > 0 && str_contains($line['memo'], 'Release inventory')
        );

        $this->assertNotNull($inventoryReleaseLine);
        $this->assertSame($parentInventoryId, $inventoryReleaseLine['account_id']);
    }
}
