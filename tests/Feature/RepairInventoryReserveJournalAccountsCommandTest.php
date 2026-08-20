<?php

namespace Tests\Feature;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderLine;
use App\Models\InventoryItem;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairInventoryReserveJournalAccountsCommandTest extends TestCase
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

    /**
     * @return array{
     *     reserveJournalId: int,
     *     cogsJournalLineId: int,
     *     inventoryParentId: int,
     *     toolsInventoryId: int,
     *     doId: int
     * }
     */
    private function seedReserveJournalAndMisPostedCogsRelease(): array
    {
        $toolsCategory = ProductCategory::query()->where('code', 'TOOLS')->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $inventoryParentId = $this->accountId('1.1.3.01');
        $dalamPerjalananId = $this->accountId('1.1.3.02');
        $toolsInventoryId = $this->accountId('1.1.3.01.11');

        $item = InventoryItem::query()->create([
            'code' => 'T-REPAIR-INV-'.uniqid(),
            'name' => 'Repair inventory reserve test item',
            'category_id' => $toolsCategory->id,
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

        $so = SalesOrder::query()->create([
            'order_no' => 'T-REPAIR-INV-SO-'.uniqid(),
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
            'account_id' => $this->accountId('4.1.1.11'),
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
            'do_number' => 'T-REPAIR-INV-DO-'.uniqid(),
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
            'account_id' => $this->accountId('4.1.1.11'),
            'item_code' => $item->code,
            'item_name' => $item->name,
            'ordered_qty' => 2,
            'delivered_qty' => 2,
            'unit_price' => 10000,
            'amount' => 20000,
            'status' => 'delivered',
        ]);

        $reserveJournalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => "Reservation - DO {$do->do_number}",
            'source_type' => DeliveryOrder::class,
            'source_id' => $do->id,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $reserveJournalId,
                'account_id' => $inventoryParentId,
                'debit' => 10000,
                'credit' => 0,
                'memo' => "Reserve inventory for DO {$do->do_number} - {$item->name}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_id' => $reserveJournalId,
                'account_id' => $dalamPerjalananId,
                'debit' => 0,
                'credit' => 10000,
                'memo' => "Release from available stock - DO {$do->do_number} - {$item->name}",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $cogsJournalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => "Revenue Recognition - DO {$do->do_number}",
            'source_type' => DeliveryOrder::class,
            'source_id' => $do->id,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cogsJournalLineId = DB::table('journal_lines')->insertGetId([
            'journal_id' => $cogsJournalId,
            'account_id' => $inventoryParentId,
            'debit' => 0,
            'credit' => 10000,
            'memo' => "Release reserved inventory - DO {$do->do_number} - {$item->name}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'reserveJournalId' => $reserveJournalId,
            'cogsJournalLineId' => $cogsJournalLineId,
            'inventoryParentId' => $inventoryParentId,
            'toolsInventoryId' => $toolsInventoryId,
            'doId' => $do->id,
        ];
    }

    public function test_command_deletes_reserve_journal_and_repoints_cogs_release_to_category_leaf(): void
    {
        $ctx = $this->seedReserveJournalAndMisPostedCogsRelease();

        $exitCode = Artisan::call('inventory:repair-reserve-journal-accounts');

        $this->assertSame(0, $exitCode);
        $this->assertNull(DB::table('journals')->where('id', $ctx['reserveJournalId'])->first());
        $this->assertSame(
            0,
            (int) DB::table('journal_lines')->where('journal_id', $ctx['reserveJournalId'])->count()
        );

        $accountId = (int) DB::table('journal_lines')
            ->where('id', $ctx['cogsJournalLineId'])
            ->value('account_id');

        $this->assertSame($ctx['toolsInventoryId'], $accountId);
    }

    public function test_dry_run_does_not_modify_data(): void
    {
        $ctx = $this->seedReserveJournalAndMisPostedCogsRelease();

        Artisan::call('inventory:repair-reserve-journal-accounts', ['--dry-run' => true]);

        $this->assertNotNull(DB::table('journals')->where('id', $ctx['reserveJournalId'])->first());
        $this->assertSame(
            $ctx['inventoryParentId'],
            (int) DB::table('journal_lines')->where('id', $ctx['cogsJournalLineId'])->value('account_id')
        );
    }

    public function test_second_run_is_idempotent(): void
    {
        $ctx = $this->seedReserveJournalAndMisPostedCogsRelease();

        Artisan::call('inventory:repair-reserve-journal-accounts');
        Artisan::call('inventory:repair-reserve-journal-accounts');

        $accountId = (int) DB::table('journal_lines')
            ->where('id', $ctx['cogsJournalLineId'])
            ->value('account_id');

        $this->assertSame($ctx['toolsInventoryId'], $accountId);
        $this->assertNull(DB::table('journals')->where('id', $ctx['reserveJournalId'])->first());
    }

    public function test_reversal_line_with_five_digit_id_repoints_to_original_account(): void
    {
        $inventoryParentId = $this->accountId('1.1.3.01');
        $toolsInventoryId = $this->accountId('1.1.3.01.11');
        $originalLineId = 18587;

        $journalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => 'Test journal for reversal repair',
            'source_type' => DeliveryOrder::class,
            'source_id' => 1,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('journal_lines')->insert([
            'id' => $originalLineId,
            'journal_id' => $journalId,
            'account_id' => $toolsInventoryId,
            'debit' => 5000,
            'credit' => 0,
            'memo' => 'Original COGS release line',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reversalLineId = DB::table('journal_lines')->insertGetId([
            'journal_id' => $journalId,
            'account_id' => $inventoryParentId,
            'debit' => 0,
            'credit' => 5000,
            'memo' => "Reversal of line {$originalLineId}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('inventory:repair-reserve-journal-accounts');

        $this->assertSame(
            $toolsInventoryId,
            (int) DB::table('journal_lines')->where('id', $reversalLineId)->value('account_id')
        );
    }

    public function test_cogs_release_repoints_when_item_name_contains_dash_separator(): void
    {
        $toolsCategory = ProductCategory::query()->where('code', 'TOOLS')->firstOrFail();
        $warehouse = Warehouse::query()->firstOrFail();
        $currencyId = (int) DB::table('currencies')->value('id');
        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        $customerId = (int) DB::table('business_partners')->where('partner_type', 'customer')->value('id');
        $userId = (int) DB::table('users')->orderBy('id')->value('id');

        $inventoryParentId = $this->accountId('1.1.3.01');
        $toolsInventoryId = $this->accountId('1.1.3.01.11');
        $itemName = 'Welding Gloves Wipro - Set';

        $item = InventoryItem::query()->create([
            'code' => 'T-REPAIR-DASH-'.uniqid(),
            'name' => $itemName,
            'category_id' => $toolsCategory->id,
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

        $so = SalesOrder::query()->create([
            'order_no' => 'T-REPAIR-DASH-SO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $customerId,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
            'total_amount' => 10000,
            'created_by' => $userId,
        ]);

        $soLine = SalesOrderLine::query()->create([
            'order_id' => $so->id,
            'account_id' => $this->accountId('4.1.1.11'),
            'inventory_item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $itemName,
            'qty' => 1,
            'delivered_qty' => 0,
            'pending_qty' => 1,
            'unit_price' => 10000,
            'amount' => 10000,
        ]);

        $do = DeliveryOrder::query()->create([
            'do_number' => 'T-REPAIR-DASH-DO-'.uniqid(),
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
            'account_id' => $this->accountId('4.1.1.11'),
            'item_code' => $item->code,
            'item_name' => $itemName,
            'ordered_qty' => 1,
            'delivered_qty' => 1,
            'unit_price' => 10000,
            'amount' => 10000,
            'status' => 'delivered',
        ]);

        $cogsJournalId = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => "Revenue Recognition - DO {$do->do_number}",
            'source_type' => DeliveryOrder::class,
            'source_id' => $do->id,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cogsJournalLineId = DB::table('journal_lines')->insertGetId([
            'journal_id' => $cogsJournalId,
            'account_id' => $inventoryParentId,
            'debit' => 0,
            'credit' => 5000,
            'memo' => "Release reserved inventory - DO {$do->do_number} - {$itemName}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('inventory:repair-reserve-journal-accounts');

        $this->assertSame(
            $toolsInventoryId,
            (int) DB::table('journal_lines')->where('id', $cogsJournalLineId)->value('account_id')
        );
    }
}
